<?php

return [
    'rpc_endpoints' => collect(explode(',', env('DERO_RPC_ENDPOINTS', 'https://dero-api.mysrv.cloud/json_rpc,http://51.178.176.109:10102/json_rpc,http://dero-node-altctrl-sg.mysrv.cloud:10102/json_rpc')))
        ->map(fn ($value) => trim($value))
        ->filter()
        ->values()
        ->all(),

    'sync_lag_threshold' => (int) env('DERO_SYNC_LAG_THRESHOLD', 10000),
];

