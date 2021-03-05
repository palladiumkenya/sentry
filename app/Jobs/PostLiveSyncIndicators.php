<?php

namespace App\Jobs;

use App\Jobs\PostLiveSyncIndicator;
use App\Models\Facility;
use App\Models\LiveSyncIndicator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PostLiveSyncIndicators implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        LiveSyncIndicator::whereIn('facility_id', Facility::where('etl', true)->pluck('id')->toArray())
            ->where('posted', false)
            ->where('created_at', '<=', now()->subtract('minutes', 1))
            ->cursor()->each(function ($liveSyncIndicator) {
                PostLiveSyncIndicator::dispatchNow($liveSyncIndicator);
            });
    }
}
