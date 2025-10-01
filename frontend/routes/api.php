<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiChartsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/charts/avg_difficulty_per_last_n_days', [ApiChartsController::class, 'get_avg_difficulty_per_last_n_days'])
    ->name('api.charts.avg_difficulty_per_last_n_days');

Route::get('/charts/get_top_miners_in_last_24h', [ApiChartsController::class, 'get_top_miners_in_last_24h'])
    ->name('api.charts.get_top_miners_in_last_24h');

Route::get('/charts/get_wallet_daily_gain', [ApiChartsController::class, 'get_wallet_daily_gain'])
    ->name('api.charts.get_wallet_daily_gain');

Route::get('/charts/get_wallet_24h_gain', [ApiChartsController::class, 'get_wallet_24h_gain'])
    ->name('api.charts.get_wallet_24h_gain');

Route::get('/charts/get_wallet_compute_power', [ApiChartsController::class, 'get_wallet_compute_power'])
    ->name('api.charts.get_wallet_compute_power');

Route::get('/charts/get_wallet_hourly_gain', [ApiChartsController::class, 'get_wallet_hourly_gain'])
    ->name('api.charts.get_wallet_hourly_gain');

Route::get('/charts/get_hourly_difficulty', [ApiChartsController::class, 'get_hourly_difficulty'])
    ->name('api.charts.get_hourly_difficulty');