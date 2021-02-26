<?php

namespace App\Jobs;

use App\Jobs\PostLiveSyncIndicator;
use App\Models\Facility;
use App\Models\LiveSyncIndicator;
use App\Models\PostLiveSyncIndicatorsJob as PostLiveSyncIndicatorsJobModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PostLiveSyncIndicatorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $postLiveSyncIndicatorsJob;

    public function __construct(PostLiveSyncIndicatorsJobModel $postLiveSyncIndicatorsJob)
    {
        $this->postLiveSyncIndicatorsJob = $postLiveSyncIndicatorsJob;
    }

    public function handle()
    {
        $this->postLiveSyncIndicatorsJob->started_at = now();
        $this->postLiveSyncIndicatorsJob->save();

        LiveSyncIndicator::whereIn('facility_code', Facility::where('etl', true)->pluck('code')->toArray())
            ->where('posted', false)
            ->where('created_at', '<=', now()->subtract('minutes', 5))
            ->cursor()->each(function ($liveSyncIndicator) {
                PostLiveSyncIndicator::dispatch($liveSyncIndicator);
            });

        $this->postLiveSyncIndicatorsJob->completed_at = now();
        $this->postLiveSyncIndicatorsJob->save();
    }
}
