<?php

namespace App\Console\Commands;

use App\Jobs\GetSpotFacilities as GetSpotFacilitiesJob;
use Illuminate\Console\Command;

class GetSpotFacilities extends Command
{
    protected $signature = 'sentry:get-spot-facilities';

    protected $description = 'Get spot facilities';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        GetSpotFacilitiesJob::dispatchNow();
    }
}
