<?php

namespace App\Console\Commands;

use App\Jobs\GetDatabaseData as GetDatabaseDataJob;
use Illuminate\Console\Command;

class GetDatabaseData extends Command
{
    protected $signature = 'sentry:get-database-data';

    protected $description = 'Get database data';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        GetDatabaseDataJob::dispatchNow();
    }
}
