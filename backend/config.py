import json
import os
from copy import deepcopy

DEFAULT_RPC_ENDPOINTS = [
    'https://dero-api.mysrv.cloud/json_rpc',
    'http://51.178.176.109:10102/json_rpc',
    'http://dero-node-altctrl-sg.mysrv.cloud:10102/json_rpc'
]


DEFAULT_CONFIG = {
    "db": {
        "host": "mariadb",
        "user": "appuser",
        "password": "apppass",
        "database": "appdb",
    },
    "rpc_endpoints": DEFAULT_RPC_ENDPOINTS,
    "poll_interval": 30,
    "log_level": "INFO",
    "retention_weeks": 4,
    "block_time_seconds": 18,
}


def _load_from_file(path: str) -> dict:
    if not os.path.exists(path):
        return {}
    try:
        with open(path) as handle:
            data = json.load(handle)
        if isinstance(data, dict):
            return data
    except Exception:
        pass
    return {}


def _apply_environment(cfg: dict) -> None:
    cfg["db"]["host"] = os.getenv("DB_HOST", cfg["db"]["host"])
    cfg["db"]["user"] = os.getenv("DB_USER", cfg["db"]["user"])
    cfg["db"]["password"] = os.getenv("DB_PASSWORD", cfg["db"]["password"])
    cfg["db"]["database"] = os.getenv("DB_NAME", cfg["db"]["database"])

    rpc_env = os.getenv("DERO_RPC_ENDPOINTS")
    if rpc_env:
        endpoints = [item.strip() for item in rpc_env.split(",") if item.strip()]
        if endpoints:
            cfg["rpc_endpoints"] = endpoints

    poll_env = os.getenv("POLL_INTERVAL")
    if poll_env and poll_env.isdigit():
        cfg["poll_interval"] = int(poll_env)

    log_level = os.getenv("LOG_LEVEL")
    if log_level:
        cfg["log_level"] = log_level.upper()

    retention_env = os.getenv("RETENTION_WEEKS")
    if retention_env:
        try:
            cfg["retention_weeks"] = float(retention_env)
        except ValueError:
            pass

    block_time_env = os.getenv("BLOCK_TIME_SECONDS")
    if block_time_env:
        try:
            cfg["block_time_seconds"] = float(block_time_env)
        except ValueError:
            pass


def _merge_dict(base: dict, override: dict) -> dict:
    for key, value in override.items():
        if isinstance(value, dict) and isinstance(base.get(key), dict):
            base[key] = _merge_dict(base[key], value)
        else:
            base[key] = value
    return base


def load_config() -> dict:
    cfg = deepcopy(DEFAULT_CONFIG)
    config_path = os.getenv("CONFIG_FILE", "/app/config.json")
    file_cfg = _load_from_file(config_path)
    cfg = _merge_dict(cfg, file_cfg)
    _apply_environment(cfg)
    return cfg
