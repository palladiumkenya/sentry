<?php

namespace App\Console\Commands;

use App\Jobs\GetDatabaseFacilities as GetDatabaseFacilitiesJob;
use Illuminate\Console\Command;

class GetDatabaseFacilities extends Command
{
    protected $signature = 'sentry:get-database-facilities';

    protected $description = 'Get database facilities';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        GetDatabaseFacilitiesJob::dispatchNow();
    }
}
