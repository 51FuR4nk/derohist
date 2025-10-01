<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ChainRepositoryInterface;

class MinersController extends Controller
{
    public function index(Request $request, ChainRepositoryInterface $chain) {
        $selectedAddress = $request->query('address');

        if ($selectedAddress && ! preg_match('/^dero[a-z0-9]{62}$/', $selectedAddress)) {
            $selectedAddress = null;
        }

        $summary = $chain->get_miners_last_24h_summary('UTC');

        $miners = $summary['records']->map(function ($miner) use ($selectedAddress) {
            $miner->own = $selectedAddress && $miner->address === $selectedAddress;
            return $miner;
        });

        $maxDominance = $miners->max('dominance') ?? 0;

        return view('miners', [
            'miners' => $miners,
            'max_dominance' => $maxDominance,
            'selected_address' => $selectedAddress,
            'total_active_miners' => $summary['total_active'],
        ]);
    }
}
