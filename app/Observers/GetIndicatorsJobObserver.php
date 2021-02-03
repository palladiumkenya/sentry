<?php

namespace App\Observers;

use App\Jobs\GetIndicatorsJob;
use App\Models\GetIndicatorsJob as GetIndicatorsJobModel;
use Illuminate\Support\Facades\Auth;

class GetIndicatorsJobObserver
{
    public function creating(GetIndicatorsJobModel $getIndicatorsJob)
    {
        if (Auth::check()) {
            $getIndicatorsJob->created_by = Auth::user()->id;
        }
    }

    public function created(GetIndicatorsJobModel $getIndicatorsJob)
    {
        GetIndicatorsJob::dispatch($getIndicatorsJob);
    }

    public function updating(GetIndicatorsJobModel $getIndicatorsJob)
    {
        //
    }

    public function updated(GetIndicatorsJobModel $getIndicatorsJob)
    {
        //
    }

    public function deleting(GetIndicatorsJobModel $getIndicatorsJob)
    {
        //
    }

    public function deleted(GetIndicatorsJobModel $getIndicatorsJob)
    {
        //
    }
}
