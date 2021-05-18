<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class TruncateTables implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600;

    protected $databaseName;

    public function __construct($databaseName)
    {
        $this->databaseName = $databaseName;
    }

    public function handle()
    {
        config(['database.connections.sqlsrv.database' => $this->databaseName]);
        DB::connection('sqlsrv')->statement("TRUNCATE TABLE stg_ARTPatients");
        DB::connection('sqlsrv')->statement("TRUNCATE TABLE stg_PatientLabs");
        DB::connection('sqlsrv')->statement("TRUNCATE TABLE stg_PatientPharmacy");
        DB::connection('sqlsrv')->statement("TRUNCATE TABLE stg_Patients");
        DB::connection('sqlsrv')->statement("TRUNCATE TABLE stg_PatientStatus");
        DB::connection('sqlsrv')->statement("TRUNCATE TABLE stg_PatientsWABWHOCD4");
        DB::connection('sqlsrv')->statement("TRUNCATE TABLE stg_PatientVisits");
        DB::connection('sqlsrv')->statement("TRUNCATE TABLE stg_AdverseEvents");
    }
}
