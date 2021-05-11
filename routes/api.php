<?php

use Illuminate\Support\Facades\Route;
use Larapress\AdobeConnect\Services\AdobeConnect\AdobeConnectController;

Route::middleware(config('larapress.crud.middlewares'))
    ->prefix(config('larapress.crud.prefix'))
    ->group(function () {
        AdobeConnectController::registerRoutes();
    });
