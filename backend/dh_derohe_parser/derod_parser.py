#!/usr/bin/env python3
import json
import requests


class DerodParser():

    def __init__(self, rpc_server):
        self.rpc_server = rpc_server

    def generic_call(self, method, params=None):
        headers = {'Content-Type': 'application/json'}
        body = {"jsonrpc": "2.0",
                "id": "1",
                "method": method,
                "params": params}
        try:
            r = requests.post(self.rpc_server, json=body,
                              headers=headers, timeout=(9, 120))
        except:
            return None
        return r

    def get_block(self, height):
        result = self.generic_call("DERO.GetBlock", {"height": height})
        return json.loads(result.text)

    def get_info(self):
        result = self.generic_call("DERO.GetInfo")
        return json.loads(result.text)

    def get_height(self):
        data = self.generic_call("DERO.GetHeight")
        return json.loads(data.text)['result']['height']
    
    def get_transactions(self, hashes):
        result = self.generic_call("DERO.GetTransaction", {"txs_hashes": hashes})
        return json.loads(result.text)

    def get_encrypted_balance(self, address):
        data = self.generic_call('DERO.GetEncryptedBalance', {"address": address, "topoheight": -1})
        return json.loads(data.text)
