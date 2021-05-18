<?php

namespace App\Jobs;

use App\Models\Facility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GetDatabaseFacilities implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        config(['database.connections.sqlsrv.database' => 'All_Staging_2016_2']);
        DB::connection('sqlsrv')->table('lkp_usgPartnerMenchanism')
            ->selectRaw('MFL_Code as code, FacilityName as name, County as county, Agency as agency, MechanismID as mechanism_id, Mechanism as partner')
            ->whereNotNull('MFL_Code')
            ->cursor()->each(function ($row) {
                $facility = Facility::where('code', $row->code)->first();
                if (!$facility) {
                    $facility = Facility::create([
                        'name' => $row->name,
                        'code' => $row->code,
                        'uid' => null,
                        'county' => $row->county,
                        'source' => 'DWH',
                        'etl' => false,
                        'processed' => false,
                        'posted' => false,
                    ]);
                } else {
                    // update ? --for now no
                }
            });

        DB::connection('sqlsrv')->table('lkp_usgPartnerMenchanism_HTS')
            ->selectRaw('MFL_Code as code, FacilityName as name, County as county, Agency as agency, MechanismID as mechanism_id, Mechanism as partner')
            ->whereNotNull('MFL_Code')
            ->cursor()->each(function ($row) {
                $facility = Facility::where('code', $row->code)->first();
                if (!$facility) {
                    $facility = Facility::create([
                        'name' => $row->name,
                        'code' => $row->code,
                        'uid' => null,
                        'county' => $row->county,
                        'source' => 'DWH',
                        'etl' => false,
                        'processed' => false,
                        'posted' => false,
                    ]);
                } else {
                    // update ? --for now no
                }
            });
    }
}
