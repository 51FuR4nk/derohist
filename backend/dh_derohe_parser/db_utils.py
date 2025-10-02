#!/usr/bin/env python3
from collections import Counter
from datetime import datetime

import mariadb


class DeroDB():

    def __init__(self, db_user, db_password, db_host, db_name):
        self.conn = mariadb.connect(user=db_user, password=db_password, host=db_host, database=db_name)
        self.cursor = self.conn.cursor()
        self.table_chain = "chain"
        self.table_reward = "reward"
        self.table_miners = "miners"
                
    def write_chain(self, data):
        sql = '''INSERT INTO chain(height, depth, difficulty, hash, topoheight, major_version,
                                minor_version, nonce, orphan_status, syncblock, sideblock, txcount,
                                reward, tips, timestamp)
                 VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'''
        time = datetime.fromtimestamp(data['timestamp']//1000)
        self.cursor.execute(sql, (data["height"], data["depth"], int(data["difficulty"]), data["hash"],
                                  data["topoheight"], data["major_version"], data["minor_version"],
                                  data["nonce"], data["orphan_status"], data["syncblock"],
                                  data["sideblock"], data["txcount"], data["reward"], data["tips"][0],
                                  time))
        self.conn.commit()
        return self.cursor.lastrowid

    def write_transaction(self, data):
        sql = '''INSERT INTO blockchain_transactions(hash, height, fees, ignored, in_pool, reward, sc_id, signer, txtype, ring_size)
                 VALUES(?,?,?,?,?,?,?,?,?,?)'''
        self.cursor.execute(sql, (data["hash"], data["height"], float(data["fees"]),
                                  data["ignored"], data["in_pool"], data["reward"], data["sc_id"], 
                                  data["signer"], data["txtype"], data['ring_size']))
        self.conn.commit()
        return self.cursor.lastrowid

    def write_tx_address(self, height, address, tx_hash):
        sql = '''INSERT INTO blockchain_tx_address(height, address, hash)
                 VALUES(?,?,?)'''
        self.cursor.execute(sql, (height, address, tx_hash))
        self.conn.commit()
        return self.cursor.lastrowid

    def write_deducted_transaction(self, height, address):
        sql = '''INSERT INTO deducted_transaction(height, address)
                 VALUES(?,?)'''
        self.cursor.execute(sql, (height, address))
        self.conn.commit()
        return self.cursor.lastrowid

    def write_miners(self, height, miners, fees):
        if not miners:
            return None

        distribution = Counter(miners)
        if not distribution:
            return None

        fee_unit = 0.0
        try:
            fee_unit = float(fees) / 10.0
        except (TypeError, ValueError):
            fee_unit = 0.0

        payload = []
        for address, count in distribution.items():
            payload.append((height, address, int(count), fee_unit * count))

        self.cursor.executemany(
            'INSERT INTO miners(height, address, miniblock, fees) VALUES (?, ?, ?, ?)',
            payload,
        )
        self.conn.commit()
        return self.cursor.lastrowid if payload else None

    def get_chain(self):
        sql = '''SELECT * FROM chain'''
        self.cursor.execute(sql)
        data = self.cursor.fetchone()
        if data:
            return data[0]
        return None

    def get_chain_item_by_height(self, height):
        sql = '''SELECT * FROM chain WHERE height = ?'''
        self.cursor.execute(sql, (height,))
        data = self.cursor.fetchone()
        if data:
            return data[0]
        return None

    def get_chain_item_by_date(self, date_start, date_end, account=None):
        params = []
        clauses = []

        if date_start is not None:
            clauses.append("timestamp > ?")
            params.append(date_start)
        if date_end is not None:
            clauses.append("timestamp < ?")
            params.append(date_end)

        sql = 'SELECT * FROM chain'
        if clauses:
            sql += ' WHERE ' + ' AND '.join(clauses)

        if account is not None:
            sql += ' AND account = ?' if clauses else ' WHERE account = ?'
            params.append(account)

        self.cursor.execute(sql, tuple(params))
        data = self.cursor.fetchall()
        if data:
            return data
        return None

    def get_chain_max_height(self, account=None):
        sql = '''SELECT max(height) FROM chain'''
        params = []
        if account is not None:
            sql += ' WHERE account = ?'
            params.append(account)
        self.cursor.execute(sql, tuple(params))
        data = self.cursor.fetchone()
        if data:
            return data[0]
        return None

    def query(self, sql):
        self.cursor.execute(sql)
        data = self.cursor.fetchall()
        if data:
            return data
        return None
    
    def update(self, sql):
        self.cursor.execute(sql)
        self.conn.commit()

    def write_address_balance(self, address, balance):
        sql = '''INSERT INTO address_balance(address, balance)
                 VALUES(?,?)'''
        self.cursor.execute(sql, (address, balance))
        self.conn.commit()
        return self.cursor.lastrowid

    def update_address_balance(self, address, balance):
        sql = '''UPDATE address_balance
                 SET balance = ?
                 WHERE address = ?
              '''
        self.cursor.execute(sql, (balance, address))
        self.conn.commit()
    
    def get_address_balance(self, address):
        sql = '''SELECT address, balance 
                 FROM address_balance
                 WHERE address = '{}' 
              '''.format(address)
        self.cursor.execute(sql)
        data = self.cursor.fetchall()
        if data:
            return data[0]
        return None

    def purge_before_height(self, min_height):
        if min_height <= 0:
            return

        self.cursor.execute("DELETE FROM chain WHERE height < ?", (min_height,))
        self.conn.commit()
