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

    public function __construct()
    {
        //
    }

    public function handle()
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('lkp_USGPartnerMenchanism')
            ->selectRaw('MFL_Code as code, FacilityName as name, County as county, Mechanism as partner')
            ->whereNotNull('MFL_Code')
            ->cursor()->each(function ($row) {
                $facility = Facility::where('code', $row['code'])->first();
                if (!$facility) {
                    $facility = Facility::create([
                        'name' => $row['name'],
                        'code' => $row['code'],
                        'uid' => null,
                        'county' => $row['county'],
                        'partner' => $row['partner'],
                        'source' => 'DWH',
                        'posted' => false,
                    ]);
                } else {
                    // update ? --for now no
                }
            });
    }
}
