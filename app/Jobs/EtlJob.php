<?php

namespace App\Jobs;

use App\Models\EtlJob as EtlJobModel;
use App\Models\Facility;
use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EtlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

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

        Facility::where('etl', true)->chunk(100, function ($facilities) use ($etlJob) {
            $f = [];
            $facilities->each(function ($facility) use (&$f) {
                $f[$facility->code] = $facility->id;
            });
            GetIndicatorValues::dispatchNow(null, null, $f);
            PostLiveSyncIndicators::dispatchNow(array_values($f));
            $facilities->each(function ($facility) use ($etlJob) {
                GetSpotFacilityMetrics::dispatchNow($etlJob, $facility);
                GetSpotFacilityUploads::dispatchNow($etlJob, $facility);
                GenerateFacilityMetricsReport::dispatchNow($etlJob, $facility);
            });
        });

        $partner = Partner::find(1);

        if ($partner) {
            SendEtlCompletedEmail::dispatchNow($partner, $etlJob, Facility::where('etl', true)->pluck('id'));
        }

        $etlJob->completed_at = now();
        $etlJob->save();
    }
}
