<?php

namespace App\Observers;

use App\Models\LiveSyncIndicator;
use Illuminate\Support\Str;

class LiveSyncIndicatorObserver
{
    public function creating(LiveSyncIndicator $liveSyncIndicator)
    {
        if (!$liveSyncIndicator->indicator_id) {
            $liveSyncIndicator->indicator_id = strtoupper(Str::uuid());
        }
        if (!$liveSyncIndicator->indicator_date) {
            $liveSyncIndicator->indicator_date = now();
        }
    }

    public function created(LiveSyncIndicator $liveSyncIndicator)
    {
        LiveSyncIndicator::dispatch($liveSyncIndicator);
    }

    public function updating(LiveSyncIndicator $liveSyncIndicator)
    {
        //
    }

    public function updated(LiveSyncIndicator $liveSyncIndicator)
    {
        //
    }

    public function deleting(LiveSyncIndicator $liveSyncIndicator)
    {
        //
    }

    public function deleted(LiveSyncIndicator $liveSyncIndicator)
    {
        //
    }
}
