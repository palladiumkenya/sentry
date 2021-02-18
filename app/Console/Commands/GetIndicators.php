<?php

namespace App\Console\Commands;

use App\Models\GetIndicatorsJob;
use Illuminate\Console\Command;

class GetIndicators extends Command
{
    protected $signature = 'sentry:get-indicators';

    protected $description = 'Get indicator values';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $postLiveSyncIndicatorsJob = new GetIndicatorsJob;
        $postLiveSyncIndicatorsJob->save();
    }
}
