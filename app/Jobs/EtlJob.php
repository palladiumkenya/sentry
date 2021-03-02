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
use Illuminate\Support\Facades\Storage;

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
        $databaseName = 'PortalDev';
        $fileName = 'Portal Dev_log';

        $etlJob = $this->etlJob;
        $etlJob->started_at = now();
        $etlJob->save();

        $directory = storage_path('app/reports/etls/'.$etlJob->id);
        Storage::makeDirectory($directory);

        // ReclaimSpace::dispatch($databaseName, $fileName);
        // TruncateTables::dispatch($databaseName);
        // DisableConstraints::dispatch($databaseName);

        GetSpotFacilities::dispatchNow();
        GetIndicatorValues::dispatchNow();
        PostLiveSyncIndicators::dispatchNow();
        Facility::where('etl', true)->cursor()->each(function($facility) use ($etlJob) {
            GetSpotFacilityMetrics::dispatch($etlJob, $facility);
            GenerateFacilityMetricsReport::dispatch($etlJob, $facility);
        });
        $etlJob->completed_at = now();
        $etlJob->save();
    }
}
