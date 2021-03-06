<?php

namespace Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Commands;

use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Command;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Converter\Converter;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers\ValueTransform as VT;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers\StatusValidate;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers\SetEntityAttributes as FillObject;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Entities\CommonInfo as CommonInfoEntity;

/**
 * Gets the common info
 *
 * More info see {@link https://helpx.adobe.com/adobe-connect/webservices/common-info.html#common_info}
 */
class ScoShortcuts extends Command
{
    /**
     * @var string
     */
    protected $domain = '';

    /**
     * @param string $domain
     */
    public function __construct($domain = '')
    {
        $this->domain = $domain;
    }

    /**
     * @inheritdoc
     *
     * @return CommonInfoEntity
     */
    protected function process()
    {

        $parameters = [
            'action' => 'sco-shortcuts'
        ];

        $response = Converter::convert(
            $this->client->doGet($parameters + ['session' => $this->client->getSession()])
        );
        StatusValidate::validate($response['status']);
        return $response['shortcuts'];
    }
}
