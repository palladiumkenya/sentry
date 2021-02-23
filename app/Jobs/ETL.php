<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ETL implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        $databaseName = 'PortalDev';
        $fileName = 'Portal Dev_log';
        ReclaimSpace::dispatch($databaseName, $fileName);
        TruncateTables::dispatch($databaseName);
        DisableConstraints::dispatch($databaseName);

    }
}
