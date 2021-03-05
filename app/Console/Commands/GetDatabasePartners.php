<?php

namespace App\Console\Commands;

use App\Jobs\GetDatabasePartners as GetDatabasePartnersJob;
use Illuminate\Console\Command;

class GetDatabasePartners extends Command
{
    protected $signature = 'sentry:get-database-partners';

    protected $description = 'Get database partners';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        GetDatabasePartnersJob::dispatchNow();
    }
}
