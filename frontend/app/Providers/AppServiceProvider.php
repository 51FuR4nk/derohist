<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Codenixsv\CoinGeckoApi\CoinGeckoClient;
use App\ChainRepositoryInterface;
use App\DeroChainRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ChainRepositoryInterface::class, DeroChainRepository::class);

        $this->app->singleton(CoinGeckoClient::class, function() {
            return new CoinGeckoClient();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (app()->environment('production') && str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        if (! app()->runningInConsole()) {
            View::composer('*', function ($view) {
                $threshold = (int) config('dero.sync_lag_threshold', 10000);

                /** @var \App\ChainRepositoryInterface $repository */
                $repository = app(ChainRepositoryInterface::class);

                $view->with('syncStatus', $repository->get_sync_status($threshold));
            });
        }
    }
}
