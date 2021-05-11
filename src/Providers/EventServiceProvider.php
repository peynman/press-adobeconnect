<?php

namespace Larapress\AdobeConnect\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // sync adobe connect servers on
        'Larapress\CRUD\Events\CRUDUpdated' => [
            'Larapress\AdobeConnect\Services\AdobeConnect\SyncACMeetingOnProductEvent',
        ],
        'Larapress\CRUD\Events\CRUDCreated' => [
            'Larapress\AdobeConnect\Services\AdobeConnect\SyncACMeetingOnProductEvent',
        ],
    ];


    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
