<?php

namespace Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Commands;

use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Command;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\ArrayableInterface;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Converter\Converter;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers\StatusValidate;

/**
 * Updates the principal's permissions to access a SCO or the access mode if the acl-id is a Meeting
 *
 * More info see {@link https://helpx.adobe.com/adobe-connect/webservices/permissions-update.html}
 * For SCO access mode info see
 * {@link https://helpx.adobe.com/adobe-connect/webservices/common-xml-elements-attributes.html#permission_id}
 */
class PermissionUpdate extends Command
{
    /**
     * @var array
     */
    protected $parameters;

    /**
     * @param ArrayableInterface $permission
     */
    public function __construct(ArrayableInterface $permission)
    {
        $this->parameters = [
            'action' => 'permissions-update',
        ];

        $this->parameters += $permission->toArray();
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    protected function process()
    {
        $response = Converter::convert(
            $this->client->doGet(
                $this->parameters + ['session' => $this->client->getSession()]
            )
        );
        StatusValidate::validate($response['status']);
        return true;
    }
}
