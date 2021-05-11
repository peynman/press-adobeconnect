<?php

namespace Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Commands;

use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Command;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Converter\Converter;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers\StatusValidate;

/**
 * Deletes one or more objects (SCOs).
*
* More info see {@link https://helpx.adobe.com/adobe-connect/webservices/sco-delete.html}
 */
class ScoDelete extends Command
{
    /**
     * @var int
     */
    protected $scoId;

    /**
     *
     * @param int $scoId The SCO ID or Folder ID
     */
    public function __construct($scoId)
    {
        $this->scoId = $scoId;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    protected function process()
    {
        $response = Converter::convert(
            $this->client->doGet([
                'action' => 'sco-delete',
                'sco-id' => $this->scoId,
                'session' => $this->client->getSession()
            ])
        );

        StatusValidate::validate($response['status']);

        return true;
    }
}
