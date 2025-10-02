<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ChainRepositoryInterface;
use Carbon\Carbon;

class ApiChartsController extends Controller
{
    public function get_avg_difficulty_per_last_n_days(Request $request, ChainRepositoryInterface $chain) {
        $validated = $this->validate($request, [
            'tz' => 'required|timezone'
        ]);

        $data = $chain->get_avg_difficulty_per_last_n_days(30, $validated['tz']);

        $today     = $data[0]->difficulty;
        $yesterday = $data[1]->difficulty;
        $diff      = $today - $yesterday;

        $labels = $data->pluck('date')->map(function ($date) use ($validated) {
            return Carbon::createFromFormat('Y-m-d', $date, 'UTC')
                ->setTimezone($validated['tz'])
                ->toIso8601String();
        });

        $data = [
            'labels' => $labels,
            'data'   => $data->pluck('difficulty'),
            'diff'   => round($diff*100/$yesterday, 2),
        ];

        return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function get_top_miners_in_last_24h(Request $request, ChainRepositoryInterface $chain) {
        $validated = $this->validate($request, [
            'tz' => 'required|timezone',
            'address' => 'required|regex:/^dero[a-z0-9]{62}$/'
        ]);

        return $chain->get_top_miners_in_last_24h($validated['address']);
    }

    public function get_active_miners_over_time(Request $request, ChainRepositoryInterface $chain) {
        $validated = $this->validate($request, [
            'tz' => 'required|timezone',
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $days = $validated['days'] ?? 30;

        $series = $chain->get_active_miners_over_time($days, $validated['tz']);

        $labels = $series['data']->pluck('date')->map(function ($date) use ($validated) {
            return Carbon::createFromFormat('Y-m-d', $date, $validated['tz'])
                ->startOfDay()
                ->toIso8601String();
        });

        $data = [
            'labels' => $labels,
            'data' => $series['data']->pluck('active_miners'),
            'updated_at' => Carbon::createFromFormat('Y-m-d H:i:s', $series['updated_at'], $validated['tz'])->format('M d,  H:i'),
        ];

        return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function get_top_miners_positions_over_time(Request $request, ChainRepositoryInterface $chain) {
        $validated = $this->validate($request, [
            'tz' => 'required|timezone',
            'days' => 'nullable|integer|min:1|max:90',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $days = $validated['days'] ?? 30;
        $limit = $validated['limit'] ?? 10;

        $series = $chain->get_top_miners_positions_over_time($days, $limit, $validated['tz']);

        $labels = collect($series['dates'])->map(function ($date) use ($validated) {
            return Carbon::createFromFormat('Y-m-d', $date, $validated['tz'])
                ->startOfDay()
                ->toIso8601String();
        });

        $payloadSeries = collect($series['series'])->map(function ($item) {
            return [
                'address' => $item['address'],
                'label' => $item['label'],
                'positions' => $item['positions'],
                'totals' => $item['totals'],
                'today_position' => $item['today_position'] ?? null,
            ];
        });

        $data = [
            'labels' => $labels,
            'series' => $payloadSeries,
            'updated_at' => Carbon::createFromFormat('Y-m-d H:i:s', $series['updated_at'], $validated['tz'])->format('M d,  H:i'),
        ];

        return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function get_wallet_daily_gain(Request $request, ChainRepositoryInterface $chain) {
        $validated = $this->validate($request, [
            'tz'      => 'required|timezone',
            'address' => 'required|regex:/^dero[a-z0-9]{62}$/'
        ]);

        $series = $chain->get_wallet_daily_gain($validated['address'], 30, $validated['tz']);
        $last24 = $chain->get_wallet_24h_gain($validated['address'], $validated['tz']);

        $items = $series['data'];
        $count = $items->count();

        $todaySlot = $items->last();
        $yesterdaySlot = $count >= 2 ? $items[$count - 2] : (object) ['gain' => 0];

        $todayGain = $last24['data'][0]->gain ?? 0;
        $yesterdayGain = $yesterdaySlot->gain ?? 0;
        $diff = $todayGain - $yesterdayGain;

        $overlay = array_fill(0, $count, null);
        if ($count > 0) {
            $overlay[$count - 1] = $todayGain;
        }
        if ($count > 1) {
            $overlay[$count - 2] = $yesterdayGain;
        }

        $labels = $items->pluck('date')->map(function ($date) use ($validated) {
            return Carbon::createFromFormat('Y-m-d', $date, $validated['tz'])
                ->startOfDay()
                ->toIso8601String();
        });

        $data = [
            'labels'     => $labels,
            'data'       => $items->pluck('gain'),
            'updated_at' => Carbon::createFromFormat('Y-m-d H:i:s', $series['updated_at'], $validated['tz'])->format('M d,  H:i'),
            'diff'       => $yesterdayGain > 0 ? round($diff * 100 / $yesterdayGain, 2) : 0,
            'last_24h'   => $overlay,
        ];

        return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function get_wallet_compute_power(Request $request, ChainRepositoryInterface $chain) {
        $validated = $this->validate($request, [
            'tz'      => 'required|timezone',
            'address' => 'required|regex:/^dero[a-z0-9]{62}$/'
        ]);

        $powerSeries = $chain->get_wallet_compute_power($validated['address'], 30, $validated['tz']);
        $powerItems = $powerSeries['data'];
        $powerCount = $powerItems->count();

        $powerToday = $powerCount ? ($powerItems[$powerCount - 1]->power ?? 0) : 0;
        $powerYesterday = $powerCount > 1 ? ($powerItems[$powerCount - 2]->power ?? 0) : 0;
        $powerDiff = $powerToday - $powerYesterday;

        $powerLabels = $powerItems->pluck('date')->map(function ($date) use ($validated) {
            return Carbon::createFromFormat('Y-m-d', $date, $validated['tz'])
                ->startOfDay()
                ->toIso8601String();
        });

        $data = [
            'labels'     => $powerLabels,
            'data'       => $powerItems->pluck('power'),
            'updated_at' => Carbon::createFromFormat('Y-m-d H:i:s', $powerSeries['updated_at'], $validated['tz'])->format('M d,  H:i'),
            'diff'       => $powerYesterday > 0 ? round($powerDiff * 100 / $powerYesterday, 2) : 0,
        ];

        return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function get_wallet_hourly_gain(Request $request, ChainRepositoryInterface $chain) {
        $validated = $this->validate($request, [
            'tz'      => 'required|timezone',
            'address' => 'required|regex:/^dero[a-z0-9]{62}$/'
        ]);

        $data = $chain->get_wallet_hourly_gain($validated['address'], 48, $validated['tz']);

        $labels = $data['data']->pluck('date')->map(function ($date) use ($validated) {
            return Carbon::createFromFormat('Y-m-d H:i', $date, $validated['tz'])
                ->toIso8601String();
        });

        $data = [
            'labels'     => $labels,
            'data'       => $data['data']->pluck('gain'),
            'updated_at' => Carbon::createFromFormat('Y-m-d H:i:s', $data['updated_at'], $validated['tz'])->format('M d,  H:i'),
        ];

        return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function get_hourly_difficulty(Request $request, ChainRepositoryInterface $chain) {
        $validated = $this->validate($request, [
            'tz'      => 'required|timezone'
        ]);

        $data = $chain->get_hourly_difficulty(48, $validated['tz']);

        $labels = $data['data']->pluck('date')->map(function ($date) use ($validated) {
            return Carbon::createFromFormat('Y-m-d H:i', $date, $validated['tz'])
                ->toIso8601String();
        });

        $data = [
            'labels'     => $labels,
            'data'       => $data['data']->pluck('gain'),
            'updated_at' => Carbon::createFromFormat('Y-m-d H:i:s', $data['updated_at'], $validated['tz'])->format('M d,  H:i'),
        ];

        return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
    }
}
