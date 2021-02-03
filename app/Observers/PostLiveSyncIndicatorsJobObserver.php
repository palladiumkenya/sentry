<?php

namespace App\Observers;

use App\Jobs\PostLiveSyncIndicatorsJob;
use App\Models\PostLiveSyncIndicatorsJob as PostLiveSyncIndicatorsJobModel;
use Illuminate\Support\Facades\Auth;

class PostLiveSyncIndicatorsJobObserver
{
    public function creating(PostLiveSyncIndicatorsJobModel $postLiveSyncIndicatorsJob)
    {
        if (Auth::check()) {
            $postLiveSyncIndicatorsJob->created_by = Auth::user()->id;
        }
    }

    public function created(PostLiveSyncIndicatorsJobModel $postLiveSyncIndicatorsJob)
    {
        PostLiveSyncIndicatorsJob::dispatch($postLiveSyncIndicatorsJob);
    }

    public function updating(PostLiveSyncIndicatorsJobModel $postLiveSyncIndicatorsJob)
    {
        //
    }

    public function updated(PostLiveSyncIndicatorsJobModel $postLiveSyncIndicatorsJob)
    {
        //
    }

    public function deleting(PostLiveSyncIndicatorsJobModel $postLiveSyncIndicatorsJob)
    {
        //
    }

    public function deleted(PostLiveSyncIndicatorsJobModel $postLiveSyncIndicatorsJob)
    {
        //
    }
}
