<?php

namespace Larapress\AdobeConnect\Providers;

use Illuminate\Support\ServiceProvider;
use Larapress\AdobeConnect\Commands\ACCreateProductType;
use Larapress\AdobeConnect\Commands\ACSyncronizeLiveEvents;
use Larapress\AdobeConnect\Services\AdobeConnect\AdobeConnectService;
use Larapress\AdobeConnect\Services\AdobeConnect\IAdobeConnectService;

class PackageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(IAdobeConnectService::class, AdobeConnectService::class);
        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'larapress');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

        $this->publishes([
            __DIR__.'/../../config/adobeconnect.php' => config_path('larapress/adobeconnect.php'),
        ], ['config', 'larapress', 'larapress-adobeconnect']);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ACSyncronizeLiveEvents::class,
                ACCreateProductType::class,
            ]);
        }
    }
}
