<?php

namespace App\Jobs;

use App\Models\EtlJob;
use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class MergePartnerFacilityMetricsReports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600;

    protected $partner;
    protected $etlJob;

    public function __construct(Partner $partner, EtlJob $etlJob)
    {
        $this->partner = $partner;
        $this->etlJob = $etlJob;
    }

    public function handle()
    {
        $path = storage_path('app/reports/etls');
        $files = Storage::files($path);
        $path = storage_path(
            'app/reports/etls/'.
            $this->partner->clean_name.'_'.
            $this->etlJob->job_date->format('YmdHis').'_dqa.pdf'
        );
    }
}
