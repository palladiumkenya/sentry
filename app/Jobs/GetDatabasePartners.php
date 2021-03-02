<?php

namespace App\Jobs;

use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GetDatabasePartners implements ShouldQueue
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
                $partner = Partner::where('name', $row['name'])->first();
                if (!$partner) {
                    $partner = Partner::create([
                        'name' => $row['name'],
                        'mechanism' => $row['mechanism'],
                        'agency' => $row['agency'],
                        'project' => $row['project'],
                        'code' => $row['code'],
                        'uid' => null,
                        'created_by' => null,
                        'source' => 'DWH',
                        'processed' => false,
                        'posted' => false,
                    ]);
                } else {
                    // update ? --for now no
                }
            });
    }
}
