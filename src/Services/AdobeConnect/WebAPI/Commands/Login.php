<?php

namespace Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Commands;

use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Command;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Converter\Converter;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Exceptions\NoDataException;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers\StatusValidate;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers\HeaderParse;

/**
 * Call the Login action and save the session cookie.
 *
 * More info see {@link https://helpx.adobe.com/content/help/en/adobe-connect/webservices/login.html}
 */
class Login extends Command
{
    /**
     * @var array
     */
    protected $parameters;

    /**
     * @param string $login
     * @param string $password
     */
    public function __construct($login, $password)
    {
        $this->parameters = [
            'action' => 'login',
            'login' => (string) $login,
            'password' => (string) $password
        ];
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    protected function process()
    {
        $response = $this->client->doGet($this->parameters);
        $responseConverted = Converter::convert($response);

        try {
            StatusValidate::validate($responseConverted['status']);
        } catch (NoDataException $e) { // Invalid Login
            $this->client->setSession('');
            return false;
        }
        $cookieHeader = HeaderParse::parse($response->getHeader('Set-Cookie'));
        $this->client->setSession($cookieHeader[0]['BREEZESESSION']);
        return true;
    }
}
