<?php

namespace Larapress\AdobeConnect\Commands;

use Illuminate\Console\Command;
use Larapress\AdobeConnect\Services\AdobeConnect\IAdobeConnectService;

class ACSyncronizeLiveEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lp:ac:sync-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncronize Live/Ended events attendance';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /** @var IAdobeConnectService */
        $acService = app(IAdobeConnectService::class);
        $acService->syncLiveEventAttendance();

        return 0;
    }
}
