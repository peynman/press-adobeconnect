<?php

namespace Larapress\AdobeConnect\Services\AdobeConnect\WebAPI;

use ReflectionClass;
use ReflectionException;
use BadMethodCallException;
use DomainException;
use UnexpectedValueException;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Connection\ConnectionInterface;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Connection\ResponseInterface;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Entities\SCO;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Entities\SCORecord;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Entities\Permission;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Entities\Principal;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Entities\CommonInfo;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Helpers\StringCaseTransform as SCT;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\ArrayableInterface as Arrayable;

/**
 * The Client to Adobe Connect API
 *
 * @method bool login(string $login, string $password) Login in the Service.
 * @method bool logout() Ends the service session
 * @method CommonInfo commonInfo(string $domain = '') Gets the Common Info
 * @method SCO scoInfo(int $scoId) Gets the info about a SCO
 * @method SCO scoCreate(Arrayable $sco) Create a SCO
 * @method bool scoUpdate(Arrayable $sco) Update a SCO
 * @method bool scoDelete(int $scoId) Delete a SCO or a Folder
 * @method bool scoMove(int $scoId, int $folderId) Move the SCO to other Folder
 * @method SCO[] scoContents(int $scoId, Arrayable $filter = null, Arrayable $sorter = null)
 *     Gets the SCO Contents from a folder or from other SCO
 * @method SCORecord[] listRecordings(int $folderId) Provides a list of recordings for a specified folder or SCO
 * @method Principal principalInfo(int $principalId) Gets the info about an user or group
 * @method Principal principalCreate(Arrayable $principal) Create a Principal.
 * @method bool principalUpdate(Arrayable $principal) Update a Principal.
 * @method bool principalDelete(int $principalId) Remove one principal, either user or group
 * @method Principal[] principalList(int $groupId = 0, Arrayable $filter = null, Arrayable $sorter = null)
 *     Provides a complete list of users and groups, including primary groups.
 * @method bool userUpdatePassword(int $userId, string $newPassword, string $oldPassword = '')
 *     Changes user???s password
 * @method bool groupMembershipUpdate(int $groupId, int $principalId, bool $isMember)
 *     Add or remove a principal from a group
 * @method bool permissionUpdate(Arrayable $permission)
 *     Updates the principal's permissions to access a SCO or the access mode if the acl-id is a Meeting
 * @method Principal[] permissionsInfo(int $aclId, Arrayable $filter, Arrayable $sorter)
 *     Gets a list of principals who have permissions to act on a SCO, Principal or Account
 * @method Permission permissionInfoFromPrincipal(int $aclId, int $principalId)
 *     Gets the Principal's permission in a SCO, Principal or Account
 * @method bool meetingFeatureUpdate(int $accountId, string $featureId, bool $enable) Set a feature
 * @method bool aclFieldUpdate(int $aclId, string $fieldId, mixed $value, Arrayable $extraParams = null)
 *     Updates the passed in Field for the specified ACL
 * @method bool recordingPasscode(int $scoId, string $passcode) Set the passcode on a Recording and turned into public
 * @method int|null scoUpload(int $folderId, string $resourceName, \resource|\SplFileInfo $file)
 *     Uploads a file and then builds the file
 */
class Client
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var string The Session Cookie
     */
    protected $sessionCookie = '';

    /**
     * @param ConnectionInterface $connection The Connection handler
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Gets the session string
     *
     * @return string
     */
    public function getSession()
    {
        return $this->sessionCookie;
    }

    /**
     * Set the session string
     *
     * @param string $session
     */
    public function setSession($session = '')
    {
        $this->sessionCookie = $session;
    }

    /**
     * Instantiates the Command and execute it.
     *
     * @param string $commandName
     * @param array $arguments
     * @return mixed
     * @throws ReflectionException
     */
    public function __call($commandName, array $arguments = [])
    {
        $className = '\\Larapress\\ECommerce\\Services\\AdobeConnect\\WebAPI\\Commands\\' . SCT::toUpperCamelCase($commandName);

        if (!class_exists($className)) {
            throw new BadMethodCallException(sprintf('"%s" is not defined as command', $commandName));
        }

        $reflection = new ReflectionClass($className);

        if (!$reflection->isSubclassOf(Command::class)) {
            throw new DomainException(sprintf('"%s" is not a valid command', $className));
        }

        return $reflection
            ->newInstanceArgs($arguments)
            ->setClient($this)
            ->execute();
    }

    /**
     * Makes a GET request
     *
     * @param array $parameters
     * @return ResponseInterface
     * @throws UnexpectedValueException
     */
    public function doGet(array $parameters)
    {
        return $this->connection->get($parameters);
    }

    /**
     * Makes a POST request
     *
     * @param array $postParams
     * @param array $queryParams
     * @return ResponseInterface
     * @throws UnexpectedValueException
     */
    public function doPost(array $postParams, array $queryParams = [])
    {
        return $this->connection->post($postParams, $queryParams);
    }
}
