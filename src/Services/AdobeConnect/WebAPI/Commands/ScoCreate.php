<?php

namespace Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Commands;

use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Command;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\ArrayableInterface;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Entities\SCO;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Converter\Converter;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers\StatusValidate;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers\SetEntityAttributes as FillObject;

/**
 * Create a SCO.
 *
 * More info see {@link https://helpx.adobe.com/adobe-connect/webservices/sco-update.html}
 */
class ScoCreate extends Command
{
    /**
     * @var array
     */
    protected $parameters;

    /**
     * @param ArrayableInterface $sco
     */
    public function __construct(ArrayableInterface $sco)
    {
        $this->parameters = [
            'action' => 'sco-update',
        ];

        $this->parameters += $sco->toArray();
    }

    /**
     * @inheritdoc
     *
     * @return SCO
     */
    protected function process()
    {
        // Create a SCO only in a folder
        if (isset($this->parameters['sco-id'])) {
            unset($this->parameters['sco-id']);
        }

        $response = Converter::convert(
            $this->client->doGet(
                $this->parameters + ['session' => $this->client->getSession()]
            )
        );

        StatusValidate::validate($response['status']);

        $sco = new SCO();
        FillObject::setAttributes($sco, $response['sco']);
        return $sco;
    }
}
