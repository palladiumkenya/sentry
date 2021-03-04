<?php

namespace App\Jobs;

use App\Models\EtlJob as EtlJobModel;
use App\Models\Facility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EtlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $etlJob;

    public function __construct(EtlJobModel $etlJob)
    {
        $this->etlJob = $etlJob;
    }

    public function handle()
    {
        $etlJob = $this->etlJob;
        $etlJob->started_at = now();
        $etlJob->save();

        // ReclaimSpace::dispatch('PortalDev', 'Portal Dev_log');
        // TruncateTables::dispatch($databaseName);
        // DisableConstraints::dispatch($databaseName);

        GetIndicatorValues::dispatchNow();
        PostLiveSyncIndicators::dispatchNow();
        Facility::where('etl', true)->cursor()->each(function($facility) use ($etlJob) {
            GetSpotFacilityMetrics::dispatchNow($etlJob, $facility);
            GetSpotFacilityUploads::dispatchNow($etlJob, $facility);
            GenerateFacilityMetricsReport::dispatchNow($etlJob, $facility);
        });

        $etlJob->completed_at = now();
        $etlJob->save();
    }
}
