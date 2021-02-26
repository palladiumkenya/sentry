<?php

namespace App\Observers;

use App\Models\EtlJob as EtlJobModel;
use App\Models\Facility;
use Illuminate\Support\Facades\Auth;

class EtlJobObserver
{
    public function creating(EtlJobModel $etlJob)
    {
        if (Auth::check()) {
            $etlJob->created_by = Auth::user()->id;
        }
        if (!$etlJob->job_date) {
            $etlJob->job_date = now();
        }
    }

    public function created(EtlJobModel $etlJob)
    {
        $facilities = Facility::where('etl', true)->get();
        if (count($facilities)) {
            $etlJob->facilities()->saveMany($facilities);
            $etlJob->refresh();
        }
    }

    public function updating(EtlJobModel $etlJob)
    {
        //
    }

    public function updated(EtlJobModel $etlJob)
    {
        //
    }

    public function deleting(EtlJobModel $etlJob)
    {
        //
    }

    public function deleted(EtlJobModel $etlJob)
    {
        //
    }
}
