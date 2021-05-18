<?php

namespace App\Jobs;

use App\Jobs\PostLiveSyncIndicator;
use App\Models\LiveSyncIndicator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PostLiveSyncIndicators implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 1800;

    protected $facilityIds;

    public function __construct($facilityIds = [])
    {
        $this->facilityIds = $facilityIds;
    }

    public function handle()
    {
        $facilityIds = $this->facilityIds;
        if (!count($facilityIds)) {
            return;
        }
        LiveSyncIndicator::whereIn('facility_id', $facilityIds)
            ->where('posted', false)
            ->where(function (Builder $query) {
                $query->orWhere('created_at', '<=', now()->subtract('seconds', 30))
                    ->orWhere('updated_at', '<=', now()->subtract('seconds', 30));
            })->cursor()->each(function ($liveSyncIndicator) {
                PostLiveSyncIndicator::dispatchNow($liveSyncIndicator)->onQueue('post_live_sync_indicator');
            });
    }
}
