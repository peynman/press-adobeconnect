<?php

namespace Larapress\AdobeConnect\Services\AdobeConnect;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Larapress\CRUD\Exceptions\AppException;
use Larapress\ECommerce\Models\Product;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Client;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Connection\Curl\Connection;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Entities\Permission;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Entities\Principal;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Entities\SCO;
use Larapress\AdobeConnect\Services\AdobeConnect\WebAPI\Filter;
use Larapress\Profiles\Models\Filter as FilterModel;
use Illuminate\Support\Str;
use Larapress\AdobeConnect\IECommerceUser;

class AdobeConnectService implements IAdobeConnectService
{
    /** @var Connection */
    protected $connection = null;
    /** @var Client */
    protected $client = null;
    /** @var string */
    protected $username = null;
    /** @var string */
    protected $url = null;

    /**
     * Undocumented function
     *
     * @param [type] $options
     * @return void
     */
    public function connect($url, $username, $password)
    {
        if ($this->url !== $url) {
            $this->url = $url;
            $this->connection = new Connection($url);
            $this->client =  new Client($this->connection);
            $this->client->login($username, $password);
            $this->username = $username;
        }
    }

    /**
     * Undocumented function
     *
     * @param string $username
     * @param string $password
     * @param string $displayName
     * @return bool
     */
    public function syncUserAccount($username, $password, $firstname, $lastname)
    {
        $usernamesuffix = config('larapress.adobeconnect.username_suffix');
        $filter = Filter::instance()->equals('login', $username . $usernamesuffix);
        $existing = $this->client->principalList(0, $filter);
        if (count($existing) === 0) {
            $principal = Principal::instance()
                ->setName($username)
                ->setLogin($username . $usernamesuffix)
                ->setPassword($password)
                ->setFirstName($firstname)
                ->setLastName($lastname)
                ->setHasChildren(false)
                ->setType(Principal::TYPE_USER);

            $obj = $this->client->principalCreate($principal);
        } else {
            $obj = $existing[0];
            $obj->setPassword($password);
            $obj->setFirstName($firstname);
            $obj->setLastName($lastname);
            $this->client->principalUpdate($obj);
            $this->client->userUpdatePassword($obj->getPrincipalId(), $password);
        }

        return $obj;
    }

    /**
     * Undocumented function
     *
     * @param [type] $folderName
     * @param [type] $meetingName
     * @param array $details
     * @return SCO
     */
    public function createOrGetMeeting($folderName, $meetingName, $details = [])
    {
        $folderId = null;
        $ids = $this->client->scoShortcuts();
        foreach ($ids as $scoFolder) {
            if (isset($scoFolder['type']) && $scoFolder['type'] === $folderName) {
                $folderId = $scoFolder['scoId'];
            }
        }

        if (is_null($folderId)) {
            Log::critical('Adobe Connect folder with typ: ' . $folderName . ' not found');
            throw new AppException(AppException::ERR_OBJECT_NOT_FOUND);
        }

        $filter = Filter::instance()->equals('name', $meetingName);
        $scos = $this->client->scoContents($folderId, $filter);

        if (count($scos) === 0) {
            $sco = SCO::instance()
                ->setName($meetingName)
                ->setType(SCO::TYPE_MEETING)
                ->setFolderId($folderId)
                ->setUrlPath($meetingName);

            if (isset($details['start_at'])) {
                $sco->setDateBegin($details['start_at']);
            }

            $sco = $this->client->scoCreate($sco);
        } else {
            $sco = $scos[0];
        }

        if (!is_null($sco)) {
            $principal = $this->getLoggedUserInfo();
            $permission = Permission::instance()
                ->setAclId($sco->getScoId())
                ->setPrincipalId($principal->getPrincipalId())
                ->setPermissionId(Permission::PRINCIPAL_HOST);
            $this->client->permissionUpdate($permission);
        }

        return $sco;
    }

    /**
     * Undocumented function
     *
     * @param IECommerceUser $user
     * @param int $product_id
     * @return /Illuminate/Http/Response
     */
    public function verifyProductMeeting($user, $product_id)
    {
        $usernamesuffix = config('larapress.adobeconnect.username_suffix');
        $product = Product::find($product_id);
        // grap product meeting and connect to AC server
        [$sco, $acServer] = $this->getMeetingForProduct($product);

        $firstname = '???????? ??????????????';
        $lastname = ' ' . $user->id;
        $profile = $user->getProfileAttribute();
        if (!is_null($profile) && isset($profile['data']['values']['display_name'])) {
            $firstname = $profile['data']['values']['display_name'];
            $lastname = ' #' . $user->id;
        }
        /** @var Principal */
        $principal = $this->syncUserAccount($user->name, $user->name . '.' . $product_id, $firstname, $lastname);

        $permission = Permission::instance()
            ->setAclId($sco->getScoId())
            ->setPrincipalId($principal->getPrincipalId())
            ->setPermissionId(Permission::PRINCIPAL_VIEW);
        $this->client->permissionUpdate($permission);

        $sessions = [];
        $this->onEachServerForProduct($product, function ($meetingFolder, $meetingName, $serverData)
        use (&$sessions, $user, $product_id, $usernamesuffix) {
            $this->client->login($user->name . $usernamesuffix, $user->name . '.' . $product_id);
            $sessions[trim($serverData['server'], '/')] = $this->client->getSession();
        });
        return [
            'principal' => $principal->getPrincipalId(),
            'sessions' => $sessions,
            'url' => trim($acServer->data['adobe_connect']['server'], '/') . $sco->getUrlPath(),
            'server' => trim($acServer->data['adobe_connect']['server'], '/')
        ];
    }

    /**
     * Undocumented function
     *
     * @param Product $item
     * @return SCO
     */
    public function getMeetingForProduct($item)
    {
        $actypename = config('larapress.adobeconnect.product_typename');
        $mettingNamePrefix = config('larapress.adobeconnect.meeting_prefix');
        $types = $item->types;
        foreach ($types as $type) {
            // if the product has ac_meeting type
            // its a adobe connect
            if ($type->name === $actypename) {
                $serverIdsList = isset($item->data['types'][$actypename]['servers']) ? $item->data['types'][$actypename]['servers'] : [];
                $serverIds = array_map(function ($item) {
                    return $item['id'];
                }, $serverIdsList);
                $servers = FilterModel::whereIn('id', $serverIds)->get();
                $meetingName = isset($item->data['types'][$actypename]['meeting_name']) && !empty($item->data['types'][$actypename]['meeting_name']) ? $item->data['types'][$actypename]['meeting_name'] : $mettingNamePrefix . $item->id;
                $itemData = $item->data;
                if (!isset($itemData['types'][$actypename]['round_robin'])) {
                    $itemData['types'][$actypename]['round_robin'] = 0;
                }
                $itemData['types'][$actypename]['round_robin'] = $itemData['types'][$actypename]['round_robin'] + 1;
                $item->update([
                    'data' => $itemData,
                ]);

                $round_robin_index = $itemData['types'][$actypename]['round_robin'];
                $serversCount = $servers->count();
                $startIndex = $serversCount > 1 ? $round_robin_index % $serversCount : 0;

                for ($i = $startIndex; $i < $startIndex + $serversCount; $i++) {
                    $targetIndex = $i % $serversCount;
                    $server = $servers[$targetIndex];
                    $meetingFolder = isset($server->data['adobe_connect']['meeting_folder']) && !empty($server->data['adobe_connect']['meeting_folder']) ? $server->data['adobe_connect'] : 'meetings';
                    $this->connect(
                        $server->data['adobe_connect']['server'],
                        $server->data['adobe_connect']['username'],
                        $server->data['adobe_connect']['password']
                    );

                    $folderId = null;
                    $ids = $this->client->scoShortcuts();
                    foreach ($ids as $scoFolder) {
                        if (isset($scoFolder['type']) && $scoFolder['type'] === $meetingFolder) {
                            $folderId = $scoFolder['scoId'];
                        }
                    }

                    if (is_null($folderId)) {
                        Log::critical('Adobe Connect folder with typ: ' . $meetingFolder . ' not found');
                        throw new AppException(AppException::ERR_OBJECT_NOT_FOUND);
                    }

                    $filter = Filter::instance()->equals('name', $meetingName);
                    $scos = $this->client->scoContents($folderId, $filter);

                    if (count($scos) === 0) {
                        throw new AppException(AppException::ERR_OBJECT_NOT_FOUND);
                    }

                    $serverData = $server->data;
                    $maxParticipants = isset($serverData['adobe_connect']['max_participants']) ? intval($serverData['adobe_connect']['max_participants']) : 0;
                    if ($maxParticipants > 0) {
                        $liveUsers = $this->client->reportMeetingSessions($scos[0]->getScoId());
                        $sessionData = $liveUsers['reportMeetingSessions'];
                        if (count($sessionData) > 0) {
                            $numParticipants = intval($sessionData[0]['numParticipants']);
                            if ($numParticipants >= $maxParticipants) {
                                continue;
                            }
                        }
                    }

                    return [$scos[0], $server];
                }
            }
        }

        return null;
    }

    /**
     * Undocumented function
     *
     * @param Product $item
     * @return void
     */
    public function createMeetingForProduct($item)
    {
        $details = [];
        if (isset($item->data['types']['session']['start_at'])) {
            $details['start_at'] = $item->data['types']['session']['start_at'];
        }

        $this->onEachServerForProduct($item, function ($meetingFolder, $meetingName) use ($details) {
            $this->createOrGetMeeting(
                $meetingFolder,
                $meetingName,
                $details
            );
        });
    }

    /**
     * Undocumented function
     *
     * @param Product $item
     * @param callable(meetingFolder, meetingName) $callback
     * @return void
     */
    public function onEachServerForProduct($item, $callback)
    {
        $actypename = config('larapress.adobeconnect.product_typename');
        $serverIdsList = isset($item->data['types'][$actypename]['servers']) ? $item->data['types'][$actypename]['servers'] : [];

        if (count($serverIdsList) == 0) {
            return;
        }

        $serverIds = array_map(function ($item) {
            return $item['id'];
        }, $serverIdsList);
        $servers = FilterModel::whereIn('id', $serverIds)->get();

        $mettingNamePrefix = config('larapress.adobeconnect.meeting_prefix');
        $meetingName = isset($item->data['types'][$actypename]['meeting_name']) && !empty($item->data['types'][$actypename]['meeting_name']) ? $item->data['types'][$actypename]['meeting_name'] : $mettingNamePrefix . $item->id;
        foreach ($servers as $server) {
            $meetingFolder = isset($server->data['adobe_connect']['meeting_folder']) && !empty($server->data['adobe_connect']['meeting_folder']) ? $server->data['adobe_connect'] : 'meetings';

            $this->connect(
                $server->data['adobe_connect']['server'],
                $server->data['adobe_connect']['username'],
                $server->data['adobe_connect']['password']
            );
            $callback($meetingFolder, $meetingName, $server->data['adobe_connect']);
        }
    }

    /**
     * @return Principal
     */
    public function getLoggedUserInfo()
    {
        return $this->getUserInfo($this->username);
    }

    /**
     * Undocumented function
     *
     * @param string $username
     * @return Principal|null
     */
    public function getUserInfo($username)
    {
        $filter = Filter::instance()->equals('login', $username);
        $existing = $this->client->principalList(0, $filter);
        if (count($existing) > 0) {
            return $existing[0];
        }
        return null;
    }

    /**
     * Undocumented function
     *
     * @param string $login
     * @return IProfileUser|null
     */
    public function getUserFromACLogin($login)
    {
        $usernamesuffix = config('larapress.adobeconnect.username_suffix');
        if (Str::endsWith($login, $usernamesuffix)) {
            $class = config('larapress.crud.user.model');
            return call_user_func([$class, 'where'], 'name', substr($login, 0, strlen($login) - strlen($usernamesuffix)))->first();
        }

        return null;
    }

    /**
     * Undocumented function
     *
     * @param string $meetingId
     * @return array
     */
    public function getMeetingRecordings($meetingId)
    {
        $filter = Filter::instance()->equals('icon', 'archive');
        $recordings = $this->client->scoContents($meetingId, $filter);
        return $recordings;
    }

    /**
     * Undocumented function
     *
     * @param string $meetingId
     * @return array
     */
    public function getMeetingSessions($meetingId)
    {
        $details = $this->client->reportMeetingSessions($meetingId);
        return isset($details['reportMeetingSessions']) ? $details['reportMeetingSessions'] : [];
    }

    /**
     * Undocumented function
     *
     * @param string $meetingId
     * @return array
     */
    public function getMeetingAttendance($meetingId)
    {
        $details = $this->client->reportMeetingAttendance($meetingId);
        return isset($details['reportMeetingAttendance']) ? $details['reportMeetingAttendance'] : [];
    }

    public function syncLiveEventAttendance()
    {
        $actypename = config('larapress.adobeconnect.product_typename');
        $products =
            Product::with('types')
            ->whereHas('types', function ($q) {
                $q->where('name', config('larapress.adobeconnect.product_typename'));
            })
            ->where('data->types->ac_meeting->status', 'live')
            ->get();

        foreach ($products as $product) {
            if (
                isset($product->data['types'][$actypename]['status']) &&
                $product->data['types'][$actypename]['status'] === 'live'
            ) {
                $fullyEnded = true;
                $recordings = [];
                $this->onEachServerForProduct($product, function ($meetingFolder, $meetingName, $serverData) use (&$fullyEnded, &$recordings) {
                    $meeting = $this->createOrGetMeeting($meetingFolder, $meetingName);
                    $attendances = $this->getMeetingAttendance($meeting->getScoId());
                    $serverEnded = true;
                    foreach ($attendances as $attendance) {
                        if (!isset($attendance['dateEnd'])) {
                            $fullyEnded = false;
                            $serverEnded = false;
                        }
                    }

                    if ($serverEnded) {
                        /** @var SCO[] */
                        $records = $this->getMeetingRecordings($meeting->getScoId());
                        foreach ($records as $record) {
                            $recordings[] = trim($serverData['server'], '/') . $record->getUrlPath();
                        }
                    }
                });

                if ($fullyEnded) {
                    $data = $product->data;
                    $data['types'][$actypename]['status'] = 'ended';
                    $data['types'][$actypename]['recordings'] = $recordings;

                    $product->update([
                        'data' => $data
                    ]);

                    /** @var ICourseSessionFormService */
                    $courseService = app(ICourseSessionFormService::class);
                    $this->onEachServerForProduct($product, function ($meetingFolder, $meetingName) use ($product, $courseService) {
                        $meeting = $this->createOrGetMeeting($meetingFolder, $meetingName);
                        $attendances = $this->getMeetingAttendance($meeting->getScoId());
                        foreach ($attendances as $attendance) {
                            if (!isset($attendance['login'])) {
                                continue;
                            }

                            $username = $attendance['login'];
                            $user = $this->getUserFromACLogin($username);
                            if (!is_null($user)) {
                                $end = Carbon::createFromFormat('Y-m-d\TH:i:s.vO', $attendance['dateEnd']);
                                $start = Carbon::createFromFormat('Y-m-d\TH:i:s.vO', $attendance['dateCreated']);
                                $duration = $start->diffInSeconds($end);
                                $courseService->addCourseSessionPresenceMarkForSession(
                                    null,
                                    $user,
                                    $product->id,
                                    1,
                                    $start
                                );
                                $courseService->addCourseSessionPresenceMarkForSession(
                                    null,
                                    $user,
                                    $product->id,
                                    $duration,
                                    $end
                                );
                            }
                        }
                    });
                }
            }
        }
    }
}
