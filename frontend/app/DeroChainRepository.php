<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DeroChainRepository implements ChainRepositoryInterface {

    public function get_avg_difficulty_per_last_n_days(int $days = 7, $tz = 'UTC') {
        DB::statement("SET time_zone='+00:00';");

        $startUtc = Carbon::now('UTC')->subDays($days - 1)->startOfDay();

        return DB::table('chain')
            ->selectRaw('DATE(timestamp) as date, AVG(difficulty)/1000000 as difficulty')
            ->where('timestamp', '>=', $startUtc->format('Y-m-d H:i:s'))
            ->groupBy('date')
            ->orderBy('date', 'DESC')
            ->get();
    }

    public function get_top_miners_in_last_24h(string $address, int $limit = 10, $tz = 'UTC') {
        DB::statement("SET time_zone='+00:00';");

        $windowStart = Carbon::now('UTC')->subDay();

        $data = DB::table('chain')
            ->selectRaw('address, 100*(
                SUM(miniblock)/(SELECT SUM(miniblock) FROM chain JOIN miners ON chain.height=miners.height
                WHERE timestamp >= "' . $windowStart->format('Y-m-d H:i:s') . '")
            ) as perc')
            ->join('miners', 'miners.height', '=', 'chain.height')
            ->where('timestamp', '>=', $windowStart->format('Y-m-d H:i:s'))
            ->groupBy('address')
            ->orderBy('perc', 'DESC')
            ->get();
        
        $data = $data->map(function($d, $i) use ($address) {
            $d->own = false;

            if ($address == $d->address) {
                $d->own = true;
            }

            $d->address_full = $d->address;
            $d->address      = substr($d->address, -7);
            $d->perc         = round($d->perc, 2);
            $d->pos          = $i;

            return $d;
        });

        if ($this->address_is_whitelisted($address)) {
            $tmp = $data->where('address_full', $address)->first();

            if ( ($tmp) && ($tmp->pos >= $limit) ) {
                $tmp2 = $data[$limit-1];
                $data[$limit-1] = $tmp;
                $data[$tmp->pos] = $tmp2;
            }
        } 

        $tops = $data->take($limit);

        $tops = $tops->map(function($d) {
            $d->min_height = Cache::rememberForever('miners.min_height.' . $d->address_full, function() use ($d) {
                return DB::table('miners')
                    ->select('height')
                    ->where('address', $d->address_full)
                    ->orderBy('height')
                    ->first()->height;
            });

            return $d;
        });

        return [
            'total' => $data->count(),
            'top'   => $tops,
        ];
    }

    public function get_miners_last_24h_summary(string $tz = 'UTC', int $limit = 300) {
        $windowStart = Carbon::now('UTC')->subDay()->format('Y-m-d H:i:s');
        $cacheKey = sprintf('miners.summary.%s.%s', strtolower($tz), $limit);

        $payload = Cache::remember($cacheKey, 120, function () use ($windowStart, $limit) {
            DB::statement("SET time_zone='+00:00';");

            $baseQuery = DB::table('miners')
                ->join('chain', 'chain.height', '=', 'miners.height')
                ->where('chain.timestamp', '>=', $windowStart);

            $totalActive = (clone $baseQuery)
                ->distinct('miners.address')
                ->count('miners.address');

            $totalMiniblocks = (float) (clone $baseQuery)->sum('miners.miniblock');

            $topMiners = (clone $baseQuery)
                ->groupBy('miners.address')
                ->selectRaw('miners.address,
                    SUM(miners.miniblock) as miniblocks,
                    SUM(miners.fees) as fees,
                    SUM(miners.miniblock * (chain.difficulty / 1000)) as weighted_power')
                ->orderByDesc('miniblocks')
                ->limit($limit)
                ->get();

            return [
                'total_active' => $totalActive,
                'total_miniblocks' => $totalMiniblocks,
                'records' => $topMiners,
            ];
        });

        $totalMiniblocks = $payload['total_miniblocks'] ?: 0.0;

        $records = $payload['records']->values()->map(function ($miner, $index) use ($totalMiniblocks) {
            $miniblocks = (int) $miner->miniblocks;
            $weightedPower = (float) $miner->weighted_power;
            $fees = isset($miner->fees) ? (float) $miner->fees : 0.0;

            $gain = ($miniblocks + $fees) * 0.0615;

            $miner->miniblocks = $miniblocks;
            $miner->gain = round($gain, 6);
            $miner->dominance = $totalMiniblocks > 0
                ? round(($miniblocks / $totalMiniblocks) * 100, 2)
                : 0;
            $miner->hashrate = $totalMiniblocks > 0
                ? round(($weightedPower / $totalMiniblocks) / 1000, 2)
                : 0;
            $miner->position = $index + 1;

            return $miner;
        });

        return [
            'total_active' => $payload['total_active'],
            'records' => $records,
        ];
    }

    private function _create_daily_interval_from_now($days, $tz) {
        $tzObject = new \DateTimeZone($tz);

        $reference = Carbon::now($tzObject);

        $startDate = $reference->copy()
            ->subDays($days - 1)
            ->startOfDay();

        $endDate = $reference->copy()
            ->addDay()
            ->startOfDay();

        $interval = \DateInterval::createFromDateString('1 day');

        return new \DatePeriod($startDate, $interval, $endDate);
    }

    private function _create_hourly_interval_from_now(int $hours, string $tz): array {
        $tzObject = new \DateTimeZone($tz);

        $reference = Carbon::now($tzObject)->startOfHour();
        $startDate = $reference->copy()->subHours(max(0, $hours - 1));

        $slots = [];
        for ($i = 0; $i < $hours; $i++) {
            $slots[] = $startDate->copy()->addHours($i);
        }

        return $slots;
    }

    public function address_is_whitelisted(string $address) {
        $whitelist = [
            'dero1qyy39v0xrtqd7clct2e9nqmyc39angzv6g48vhxj0uhx9p5tn9ujsqq6c6295',
            'dero1qy0gwg6kcgl5rzrjvl5hdwhshgwlxvutunp2ufrjdkxl0k02zhmtxqg0x28rn',
        ];

        return in_array($address, $whitelist);
    }

    public function get_wallet_daily_gain(string $address, int $days = 10, string $tz = 'UTC') {
        $ttl = 300;

        if ($this->address_is_whitelisted($address)) {
            $ttl = 60;
        }

        return \Cache::remember("wallet_daily_gain{$address}.{$tz}.{$days}", $ttl, function () use ($address, $days, $tz) {
            $tzObject = new \DateTimeZone($tz);
            $endLocal = Carbon::now($tzObject)->startOfDay()->addDay();
            $startLocal = $endLocal->copy()->subDays($days);

            $slots = collect();
            for ($cursor = $startLocal->copy(); $cursor->lt($endLocal); $cursor->addDay()) {
                $key = $cursor->format('Y-m-d');
                $slots[$key] = (object) [
                    'date' => $key,
                    'gain' => 0.0,
                ];
            }

            DB::statement("SET time_zone='+00:00';");

            $startUtc = $startLocal->copy()->setTimezone('UTC');
            $endUtc = $endLocal->copy()->setTimezone('UTC');

            $rows = DB::table('miners')
                ->join('chain', 'chain.height', '=', 'miners.height')
                ->select('chain.timestamp', DB::raw('SUM(miners.miniblock) AS miniblocks'), DB::raw('SUM(miners.fees) AS fees'))
                ->where('miners.address', $address)
                ->where('chain.timestamp', '>=', $startUtc->format('Y-m-d H:i:s'))
                ->where('chain.timestamp', '<', $endUtc->format('Y-m-d H:i:s'))
                ->groupBy('chain.height', 'chain.timestamp')
                ->orderBy('chain.timestamp')
                ->get();

            foreach ($rows as $row) {
                $localKey = Carbon::parse($row->timestamp, 'UTC')->setTimezone($tz)->format('Y-m-d');
                if (isset($slots[$localKey])) {
                    $gain = ((float) $row->miniblocks + (float) $row->fees) * 0.0615;
                    $slots[$localKey]->gain += $gain;
                }
            }

            return [
                'updated_at' => Carbon::now($tzObject)->format('Y-m-d H:i:s'),
                'data' => $slots->values(),
            ];
        });
    }


    public function get_wallet_24h_gain(string $address, string $tz = 'UTC') {
        $ttl = 300;

        if ($this->address_is_whitelisted($address)) {
            $ttl = 60;
        }

        return \Cache::remember("wallet_24h_gain{$address}.{$tz}", $ttl, function () use ($address, $tz) {
            $hourly = $this->get_wallet_hourly_gain($address, 24, $tz);

            $total = $hourly['data']->sum(function ($item) {
                return (float) ($item->gain ?? 0.0);
            });

            return [
                'updated_at' => $hourly['updated_at'],
                'data' => collect([(object) ['gain' => $total]]),
            ];
        });
    }


    public function get_wallet_compute_power(string $address, int $days = 10, string $tz = 'UTC') {
        $ttl = 300;

        if ($this->address_is_whitelisted($address)) {
            $ttl = 60;
        }

        return \Cache::remember("wallet_daily_power{$address}.{$tz}.{$days}", $ttl, function () use ($address, $days, $tz) {
            $tzObject = new \DateTimeZone($tz);
            $endLocal = Carbon::now($tzObject)->startOfDay()->addDay();
            $startLocal = $endLocal->copy()->subDays($days);

            $slots = collect();
            for ($cursor = $startLocal->copy(); $cursor->lt($endLocal); $cursor->addDay()) {
                $key = $cursor->format('Y-m-d');
                $slots[$key] = (object) [
                    'date' => $key,
                    'power' => 0.0,
                ];
            }

            DB::statement("SET time_zone='+00:00';");

            $startUtc = $startLocal->copy()->setTimezone('UTC');
            $endUtc = $endLocal->copy()->setTimezone('UTC');

            $addressRows = DB::table('miners')
                ->join('chain', 'chain.height', '=', 'miners.height')
                ->select('chain.timestamp', DB::raw('SUM(miners.miniblock) AS miniblocks'), DB::raw('AVG(chain.difficulty) AS difficulty'))
                ->where('miners.address', $address)
                ->where('chain.timestamp', '>=', $startUtc->format('Y-m-d H:i:s'))
                ->where('chain.timestamp', '<', $endUtc->format('Y-m-d H:i:s'))
                ->groupBy('chain.height', 'chain.timestamp')
                ->orderBy('chain.timestamp')
                ->get();

            $numerator = [];
            foreach ($addressRows as $row) {
                $localKey = Carbon::parse($row->timestamp, 'UTC')->setTimezone($tz)->format('Y-m-d');
                $weighted = (float) $row->miniblocks * ((float) $row->difficulty / 1000.0);
                $numerator[$localKey] = ($numerator[$localKey] ?? 0.0) + $weighted;
            }

            $totalRows = DB::table('miners')
                ->join('chain', 'chain.height', '=', 'miners.height')
                ->select('chain.timestamp', DB::raw('SUM(miners.miniblock) AS total_miniblocks'))
                ->where('chain.timestamp', '>=', $startUtc->format('Y-m-d H:i:s'))
                ->where('chain.timestamp', '<', $endUtc->format('Y-m-d H:i:s'))
                ->groupBy('chain.height', 'chain.timestamp')
                ->orderBy('chain.timestamp')
                ->get();

            $denominator = [];
            foreach ($totalRows as $row) {
                $localKey = Carbon::parse($row->timestamp, 'UTC')->setTimezone($tz)->format('Y-m-d');
                $denominator[$localKey] = ($denominator[$localKey] ?? 0.0) + (float) $row->total_miniblocks;
            }

            foreach ($slots as $key => $slot) {
                $num = $numerator[$key] ?? 0.0;
                $den = $denominator[$key] ?? 0.0;
                $slot->power = $den > 0 ? round($num / $den, 0) : 0.0;
            }

            return [
                'updated_at' => Carbon::now($tzObject)->format('Y-m-d H:i:s'),
                'data' => $slots->values(),
            ];
        });
    }

    public function get_chain_height() {
        $row = DB::table('chain')
            ->select('height')
            ->orderBy('height', 'DESC')
            ->limit(1)
            ->first();

        return $row->height ?? 0;
    }

    public function get_sync_status(int $threshold = 10000): array {
        $localHeight = $this->get_chain_height();
        $networkHeight = Cache::remember('dero.network_height', 60, function () {
            return $this->fetch_network_height();
        });

        $thresholdValue = $threshold > 0 ? $threshold : (int) config('dero.sync_lag_threshold', 10000);
        $lag = null;

        if ($networkHeight !== null) {
            $lag = max(0, $networkHeight - $localHeight);
        }

        return [
            'local_height' => $localHeight,
            'network_height' => $networkHeight,
            'lag' => $lag,
            'threshold' => $thresholdValue,
            'is_syncing' => $lag !== null && $lag >= $thresholdValue,
        ];
    }

    public function get_wallet_hourly_gain(string $address, int $hours = 48, string $tz = 'UTC') {
        $ttl = 300;

        if ($this->address_is_whitelisted($address)) {
            $ttl = 60;
        }
        return \Cache::remember("wallet_hourly_gain{$address}.{$tz}.{$hours}", $ttl, function () use ($address, $hours, $tz) {
            $slots = collect();
            foreach ($this->_create_hourly_interval_from_now($hours, $tz) as $moment) {
                $localKey = $moment->format('Y-m-d H:00');
                $slots[$localKey] = (object) [
                    'date' => $localKey,
                    'gain' => 0.0,
                ];
            }

            DB::statement("SET time_zone='+00:00';");

            $startLocal = Carbon::now($tz)->startOfHour()->subHours(max(0, $hours - 1));
            $endLocal = $startLocal->copy()->addHours($hours);

            $startUtc = $startLocal->copy()->setTimezone('UTC');
            $endUtc = $endLocal->copy()->setTimezone('UTC');

            $subQuery = DB::table('chain')
                ->selectRaw('height, timestamp')
                ->where('timestamp', '>=', $startUtc->format('Y-m-d H:i:s'))
                ->where('timestamp', '<', $endUtc->format('Y-m-d H:i:s'));

            $rows = DB::table(DB::raw('(' . $subQuery->toSql() . ') as c1'))
                ->selectRaw('DATE(timestamp) as date, HOUR(timestamp) as hour, (COALESCE(SUM(miniblock), 0) * 0.0615) + COALESCE(SUM(fees), 0) as gain')
                ->leftJoin('miners', 'c1.height', '=', 'miners.height')
                ->mergeBindings($subQuery)
                ->where('address', $address)
                ->groupBy('date', 'hour')
                ->orderBy('date', 'ASC')
                ->orderBy('hour', 'ASC')
                ->get();

            foreach ($rows as $row) {
                $utcSlot = Carbon::createFromFormat('Y-m-d H', $row->date . ' ' . $row->hour, 'UTC');
                $localKey = $utcSlot->copy()->setTimezone($tz)->format('Y-m-d H:00');
                if (isset($slots[$localKey])) {
                    $slots[$localKey]->gain = (float) $row->gain;
                }
            }

            return [
                'updated_at' => Carbon::now($tz)->format('Y-m-d H:i:s'),
                'data' => $slots->sortKeys()->values(),
            ];
        });
    }

    public function get_hourly_difficulty(int $hours = 48, string $tz = 'UTC') {
        $ttl = 300;

        return \Cache::remember("hourly_difficulty_{$tz}_{$hours}", $ttl, function () use ($hours, $tz) {
            $slots = collect();

            foreach ($this->_create_hourly_interval_from_now($hours, $tz) as $moment) {
                $localKey = $moment->format('Y-m-d H:00');
                $slots[$localKey] = (object) [
                    'date' => $localKey,
                    'gain' => 0.0,
                ];
            }

            DB::statement("SET time_zone='+00:00';");

            $startLocal = Carbon::now($tz)->startOfHour()->subHours(max(0, $hours - 1));
            $endLocal = $startLocal->copy()->addHours($hours);

            $startUtc = $startLocal->copy()->setTimezone('UTC');
            $endUtc = $endLocal->copy()->setTimezone('UTC');

            $data = DB::table('chain')
                ->selectRaw('DATE(timestamp) as date, HOUR(timestamp) as hour, AVG(difficulty)/1000000 as difficulty')
                ->where('timestamp', '>=', $startUtc->format('Y-m-d H:i:s'))
                ->where('timestamp', '<', $endUtc->format('Y-m-d H:i:s'))
                ->groupBy('date', 'hour')
                ->orderBy('date', 'ASC', 'hour', 'ASC')
                ->get();
                
            //dd($data);
            foreach ($data as $row) {
                $utcSlot = Carbon::createFromFormat('Y-m-d H', $row->date . ' ' . $row->hour, 'UTC');
                $localKey = $utcSlot->copy()->setTimezone($tz)->format('Y-m-d H:00');
                if (isset($slots[$localKey])) {
                    $slots[$localKey]->gain = (float) $row->difficulty;
                }
            }

            return [
                'updated_at' => Carbon::now($tz)->format('Y-m-d H:i:s'),
                'data' => $slots->sortKeys()->values(),
            ];
        });
    }

    private function fetch_network_height(): ?int {
        $endpoints = config('dero.rpc_endpoints', []);

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout(6)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($endpoint, [
                    'jsonrpc' => '2.0',
                    'id' => '1',
                    'method' => 'DERO.GetHeight',
                ]);

                if (! $response->ok()) {
                    continue;
                }

                $payload = $response->json();
                if (is_array($payload) && isset($payload['result']['height'])) {
                    return (int) $payload['result']['height'];
                }
            } catch (\Throwable $exception) {
                continue;
            }
        }

        return null;
    }
}
