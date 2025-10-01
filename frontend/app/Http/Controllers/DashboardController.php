<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Codenixsv\CoinGeckoApi\CoinGeckoClient;
use App\ChainRepositoryInterface;

class DashboardController extends Controller
{
    public function index(Request $request, ChainRepositoryInterface $chain, CoinGeckoClient $gecko) {
        $validated = $this->validate($request, [
            'address' => 'nullable|regex:/^dero[a-z0-9]{62}$/'
        ]);
        
        $validated['address'] ??= '';
        
        $miners = $chain->get_top_miners_in_last_24h($validated['address'], 10, 'UTC');

        $price = \Cache::remember('dero.price', 300, function() use ($gecko) {
            return $gecko->simple()->getPrice('dero', 'usd,eur', [
                'include_24hr_change' => 'true',
                'include_24hr_vol'    => 'true',
                'include_market_cap'  => 'true'
            ]);
        });

        $height = $chain->get_chain_height();

        return view('dashboard', [
            'address'                => $validated['address'] ?? null,
            'price'                  => $price,
            'height'                 => $height,
            'active_in_last_24h'     => $miners['total'],
            'top_miners_in_last_24h' => $miners['top'],
        ]);
    }
}
