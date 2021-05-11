<?php

namespace Larapress\AdobeConnect\Services\AdobeConnect;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Larapress\AdobeConnect\CRUD\CartCRUDProvider;
use Larapress\AdobeConnect\Services\AdobeConnect\IAdobeConnectService;

class AdobeConnectController extends Controller
{
    public static function registerRoutes()
    {
        Route::post(
            '/adobe-connect/verify/{session_id}',
            '\\'.self::class.'@verifyAdobeConnectMeeting'
        )->name('adobe-connect.any.verify');
    }

    public function verifyAdobeConnectMeeting(IAdobeConnectService $service, $session_id)
    {
        return $service->verifyProductMeeting(Auth::user(), $session_id);
    }
}
