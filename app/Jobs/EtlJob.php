<?php

namespace App\Jobs;

use App\Models\EtlJob as EtlJobModel;
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
        $databaseName = 'PortalDev';
        $fileName = 'Portal Dev_log';

        $this->etlJob->started_at = now();
        $this->etlJob->save();

        // ReclaimSpace::dispatch($databaseName, $fileName);
        // TruncateTables::dispatch($databaseName);
        // DisableConstraints::dispatch($databaseName);

        $this->etlJob->completed_at = now();
        $this->etlJob->save();
    }
}
