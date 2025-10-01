#!/usr/bin/env python3
import json
import logging
import time
from typing import Iterable, Optional

import mariadb

from config import load_config
from dh_derohe_parser.db_utils import DeroDB
from dh_derohe_parser.derod_parser import DerodParser

try:
    from systemd import daemon as systemd_daemon  # type: ignore
except ImportError:  # pragma: no cover - optional runtime dependency
    systemd_daemon = None

try:
    from systemd.journal import JournalHandler  # type: ignore
except ImportError:  # pragma: no cover - optional runtime dependency
    JournalHandler = None  # type: ignore


LOGGER = logging.getLogger("derohist.updater")
LOGGER.setLevel(logging.INFO)

if JournalHandler is not None:
    handler: logging.Handler = JournalHandler()
else:
    handler = logging.StreamHandler()
handler.setFormatter(logging.Formatter("[%(levelname)s] %(message)s"))
LOGGER.addHandler(handler)


def get_rpc(endpoints: Iterable[str]) -> Optional[DerodParser]:
    for endpoint in endpoints:
        parser = DerodParser(endpoint)
        try:
            if parser.get_height() is not None:
                LOGGER.info("Using RPC endpoint %s", endpoint)
                return parser
        except Exception:  # pragma: no cover - network call
            LOGGER.warning("RPC endpoint %s is unavailable", endpoint)
            continue
    return None


def update_transactions(db: DeroDB, parser: DerodParser, block_data: dict, tx_payload: dict) -> float:
    hashes = tx_payload.get("tx_hashes", [])
    if not hashes:
        return 0.0
    try:
        response = parser.get_transactions(hashes)
        txs = (response or {}).get("result", {}).get("txs", [])
    except Exception as exc:  # pragma: no cover - network call
        LOGGER.warning("Unable to fetch transactions for block %s: %s", block_data.get("height"), exc)
        return 0.0

    total_fees = 0.0
    for index, item in enumerate(txs):
        tx_hash = hashes[index] if index < len(hashes) else item.get("tx_hash")
        record = {
            "hash": tx_hash,
            "height": block_data.get("height"),
            "fees": float(item.get("fees", 0.0) or 0.0),
            "ignored": item.get("ignored"),
            "in_pool": item.get("in_pool"),
            "reward": item.get("reward"),
            "sc_id": item.get("sc_id"),
            "signer": item.get("signer"),
            "txtype": item.get("txtype"),
            "ring_size": len(item.get("ring", [])[0]) if item.get("ring") else 0,
        }
        total_fees += record["fees"]
        # db.write_transaction(record)
    return total_fees


def sync_chain(db: DeroDB, parser: DerodParser, retention_blocks: Optional[int], endpoints: Iterable[str]) -> DerodParser:
    current_height = parser.get_height() - 10
    if current_height is None:
        LOGGER.warning("RPC did not return a chain height")
        return

    start_height = 0
    if retention_blocks and retention_blocks > 0:
        start_height = max(0, current_height - retention_blocks + 1)

    db_height = db.get_chain_max_height() or 0
    if db_height < start_height - 1:
        db_height = start_height - 1
    diff = current_height - db_height
    if diff <= 0:
        if start_height > 0:
            db.purge_before_height(start_height)
        return

    LOGGER.info("Syncing chain from height %s to %s (diff %s)", db_height, current_height, diff)
    checkpoint = 25
    processed = 0

    consecutive_failures = 0

    for height in range(db_height + 1, current_height + 1):
        raw_data = None
        last_error: Optional[Exception] = None
        for attempt in range(1, 4):
            try:
                raw_data = parser.get_block(height)
                if not raw_data:
                    raise ValueError("Empty response")
                break
            except Exception as exc:  # pragma: no cover - network call
                last_error = exc
                if attempt >= 3:
                    LOGGER.warning("Failed to fetch block %s after %s attempts: %s", height, attempt, exc)
                else:
                    LOGGER.warning(
                        "Failed to fetch block %s (attempt %s/3). Retrying in 1s: %s",
                        height,
                        attempt,
                        exc,
                    )
                    time.sleep(1)

        if raw_data is None:
            if last_error is None:
                LOGGER.warning("Failed to fetch block %s after retries", height)
            consecutive_failures += 1
            if consecutive_failures >= 5:
                LOGGER.warning("Encountered %s consecutive block failures. Rotating RPC endpoint.", consecutive_failures)
                parser = _acquire_rpc(endpoints)
                consecutive_failures = 0
            continue
        consecutive_failures = 0
        if not raw_data or "error" in raw_data:
            LOGGER.warning("Cannot parse block %s", height)
            continue
        block_data = raw_data.get("result", {}).get("block_header", {})
        if not block_data:
            LOGGER.warning("Skipping block %s due to missing header", height)
            continue

        db.write_chain(block_data)

        fees = 0.0
        if block_data.get("txcount", 0) > 0:
            try:
                tx_payload = json.loads(raw_data.get("result", {}).get("json", "{}"))
            except json.JSONDecodeError:
                LOGGER.warning("Invalid JSON payload for block %s", height)
            else:
                fees = update_transactions(db, parser, block_data, tx_payload)

        db.write_miners(block_data.get("height"), block_data.get("miners", []), fees)
        processed += 1
        percentage = processed / diff * 100
        if percentage >= checkpoint:
            LOGGER.info("%.0f%% of block range parsed", percentage)
            checkpoint += 25

    LOGGER.info("Sync completed")

    if start_height > 0:
        LOGGER.info("Purging data below height %s", start_height)
        db.purge_before_height(start_height)

    return parser


def _connect_database(db_cfg: dict, retry_delay: int = 5) -> DeroDB:
    while True:
        try:
            return DeroDB(db_cfg["user"], db_cfg["password"], db_cfg["host"], db_cfg["database"])
        except mariadb.Error as exc:
            LOGGER.warning("MariaDB connection failed (%s). Retrying in %ss", exc, retry_delay)
            time.sleep(retry_delay)


def _acquire_rpc(endpoints: Iterable[str], retry_delay: int = 30) -> DerodParser:
    while True:
        parser = get_rpc(endpoints)
        if parser is not None:
            return parser
        LOGGER.error("All RPC endpoints unreachable. Retrying in %ss", retry_delay)
        time.sleep(retry_delay)


def main() -> None:
    cfg = load_config()
    log_level = cfg.get("log_level", "INFO")
    try:
        LOGGER.setLevel(getattr(logging, log_level.upper()))
    except AttributeError:
        LOGGER.setLevel(logging.INFO)

    db_cfg = cfg["db"]
    parser = _acquire_rpc(cfg.get("rpc_endpoints", []))
    db = _connect_database(db_cfg)

    retention_blocks: Optional[int] = None
    retention_weeks = cfg.get("retention_weeks", 0)
    block_time = cfg.get("block_time_seconds", 18)
    try:
        retention_weeks = float(retention_weeks)
        block_time = float(block_time)
    except (TypeError, ValueError):
        retention_weeks = 0
        block_time = 18

    if retention_weeks > 0 and block_time > 0:
        seconds = retention_weeks * 7 * 86400
        retention_blocks = max(1, int(seconds / block_time))
        LOGGER.info("Retention window: %.2f weeks (~%s blocks)", retention_weeks, retention_blocks)
    else:
        LOGGER.info("Retention window disabled; keeping full chain history")

    if systemd_daemon is not None:  # pragma: no cover - optional runtime dependency
        systemd_daemon.notify("READY=1")

    poll_interval = cfg.get("poll_interval", 30)
    LOGGER.info("Starting sync loop (interval %ss)", poll_interval)

    while True:
        try:
            parser = sync_chain(db, parser, retention_blocks, cfg.get("rpc_endpoints", []))
        except mariadb.Error as exc:
            LOGGER.exception("Database error: %s", exc)
            db = _connect_database(db_cfg)
        except Exception as exc:  # pragma: no cover - defensive logging
            LOGGER.exception("Sync loop error: %s", exc)
            parser = _acquire_rpc(cfg.get("rpc_endpoints", []))
        time.sleep(poll_interval)


if __name__ == "__main__":  # pragma: no cover
    main()
