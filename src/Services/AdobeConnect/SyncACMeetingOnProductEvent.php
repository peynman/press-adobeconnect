<?php

namespace Larapress\AdobeConnect\Services\AdobeConnect;

use Illuminate\Contracts\Queue\ShouldQueue;
use Larapress\CRUD\Events\CRUDVerbEvent;
use Larapress\AdobeConnect\CRUD\ProductCRUDProvider;
use Larapress\AdobeConnect\Services\AdobeConnect\IAdobeConnectService;

class SyncACMeetingOnProductEvent implements ShouldQueue
{
    /** @var IAdobeConnectService */
    private $service;

    public function __construct(IAdobeConnectService $service)
    {
        $this->service = $service;
    }

    public function handle(CRUDVerbEvent $event)
    {
        if ($event->providerClass === config('larapress.ecommerce.routes.products.provider')) {
            $actypename = config('larapress.adobeconnect.product_typename');
            $model = $event->getModel();
            if (isset($model->data['types'][$actypename]['servers'])) {
                $this->service->createMeetingForProduct($model);
            }
        }
    }
}
