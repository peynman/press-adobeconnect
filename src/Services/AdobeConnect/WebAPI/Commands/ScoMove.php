<?php

namespace Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Commands;

use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Command;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Converter\Converter;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers\StatusValidate;

/**
 * Move a SCO to other folder
 *
 * More Info see {@link https://helpx.adobe.com/adobe-connect/webservices/sco-move.html}
 */
class ScoMove extends Command
{
    /**
     * @var int
     */
    protected $scoId;

    /**
     * @var int
     */
    protected $folderId;

    /**
     * @param int $scoId
     * @param int $folderId
     */
    public function __construct($scoId, $folderId)
    {
        $this->scoId = $scoId;
        $this->folderId = $folderId;
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
                'action' => 'sco-move',
                'sco-id' => $this->scoId,
                'folder-id' => $this->folderId,
                'session' => $this->client->getSession()
            ])
        );

        StatusValidate::validate($response['status']);

        return true;
    }
}
