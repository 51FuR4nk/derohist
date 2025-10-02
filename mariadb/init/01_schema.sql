-- Schema derived from backend Python access patterns
CREATE DATABASE IF NOT EXISTS appdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE appdb;

CREATE TABLE IF NOT EXISTS chain (
    height BIGINT UNSIGNED PRIMARY KEY,
    depth INT UNSIGNED NULL,
    difficulty BIGINT UNSIGNED NULL,
    hash CHAR(64) NOT NULL,
    topoheight BIGINT UNSIGNED NULL,
    major_version SMALLINT NULL,
    minor_version SMALLINT NULL,
    nonce BIGINT UNSIGNED NULL,
    orphan_status TINYINT NULL,
    syncblock TINYINT NULL,
    sideblock TINYINT NULL,
    txcount INT UNSIGNED NULL,
    reward DECIMAL(20, 8) NULL,
    tips VARCHAR(128) NULL,
    timestamp DATETIME NOT NULL,
    KEY idx_chain_timestamp (timestamp),
    KEY idx_chain_topoheight (topoheight)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blockchain_transactions (
    hash CHAR(64) PRIMARY KEY,
    height BIGINT UNSIGNED NOT NULL,
    fees DECIMAL(20, 8) DEFAULT 0,
    ignored TINYINT NULL,
    in_pool TINYINT NULL,
    reward DECIMAL(20, 8) NULL,
    sc_id VARCHAR(128) NULL,
    signer VARCHAR(128) NULL,
    txtype VARCHAR(32) NULL,
    ring_size INT UNSIGNED NULL,
    KEY idx_transactions_height (height),
    CONSTRAINT fk_transactions_chain FOREIGN KEY (height) REFERENCES chain(height) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blockchain_tx_address (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    height BIGINT UNSIGNED NOT NULL,
    address VARCHAR(120) NOT NULL,
    hash CHAR(64) NOT NULL,
    UNIQUE KEY uq_tx_address (address, hash),
    KEY idx_tx_addr_height (height),
    KEY idx_tx_addr_address (address),
    KEY idx_tx_addr_address_height (address, height),
    CONSTRAINT fk_tx_addr_chain FOREIGN KEY (height) REFERENCES chain(height) ON DELETE CASCADE,
    CONSTRAINT fk_tx_addr_transaction FOREIGN KEY (hash) REFERENCES blockchain_transactions(hash) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deducted_transaction (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    height BIGINT UNSIGNED NOT NULL,
    address VARCHAR(120) NOT NULL,
    UNIQUE KEY uq_deducted (height, address),
    KEY idx_deducted_height (height),
    CONSTRAINT fk_deducted_chain FOREIGN KEY (height) REFERENCES chain(height) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS miners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    height BIGINT UNSIGNED NOT NULL,
    address VARCHAR(120) NOT NULL,
    miniblock INT UNSIGNED DEFAULT 0,
    fees DECIMAL(20, 8) DEFAULT 0,
    KEY idx_miners_height (height),
    KEY idx_miners_address (address),
    KEY idx_miners_address_height (address, height),
    CONSTRAINT fk_miners_chain FOREIGN KEY (height) REFERENCES chain(height) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS address_balance (
    address VARCHAR(120) PRIMARY KEY,
    balance TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
