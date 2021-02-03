<?php

namespace App\Console\Commands;

use App\Models\PostLiveSyncIndicatorsJob;
use Illuminate\Console\Command;

class PostLiveSyncIndicators extends Command
{
    protected $signature = 'sentry:post-live-sync-indicators';

    protected $description = 'Post live sync indicators not posted';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $postLiveSyncIndicatorsJob = new PostLiveSyncIndicatorsJob;
        $postLiveSyncIndicatorsJob->save();
    }
}
