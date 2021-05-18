<?php

namespace App\Jobs;

use App\Models\Facility;
use App\Models\FacilityPartner;
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

    public $tries = 1;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        config(['database.connections.sqlsrv.database' => 'All_Staging_2016_2']);
        DB::connection('sqlsrv')->table('lkp_usgPartnerMenchanism_HTS')
            ->selectRaw('MFL_Code as code, FacilityName as name, County as county, Agency as agency, MechanismID as mechanism_id, Mechanism as partner, Implementing_Mechanism_Name as mechanism, project')
            ->whereNotNull('MFL_Code')
            ->cursor()->each(function ($row) {
                $partner = Partner::where('name', $row->partner)->first();
                if (!$partner) {
                    $partner = Partner::create([
                        'name' => $row->partner,
                        'mechanism' => $row->mechanism,
                        'agency' => $row->agency,
                        'project' => $row->project,
                        'code' => $row->mechanism_id,
                        'uid' => null,
                        'created_by' => null,
                        'source' => 'DWH',
                        'processed' => false,
                        'posted' => false,
                    ]);
                } else {
                    // update ? --for now no
                }
                $facility = Facility::where('code', $row->code)->first();
                if ($partner && $facility) {
                    $facilityPartner = FacilityPartner::where('facility_id', $facility->id)
                        ->where('partner_id', $partner->id)
                        ->where('docket', 'HTS')
                        ->first();
                    if (!$facilityPartner) {
                        $facility->partners()->save($facility, ['docket' => 'HTS']);
                    }
                }
            });

        DB::connection('sqlsrv')->table('lkp_usgPartnerMenchanism')
            ->selectRaw('MFL_Code as code, FacilityName as name, County as county, Agency as agency, MechanismID as mechanism_id, Mechanism as partner, Implementing_Mechanism_Name as mechanism')
            ->whereNotNull('MFL_Code')
            ->cursor()->each(function ($row) {
                $partner = Partner::where('name', $row->partner)->first();
                if (!$partner) {
                    $partner = Partner::create([
                        'name' => $row->partner,
                        'mechanism' => $row->mechanism,
                        'agency' => $row->agency,
                        'project' => null,
                        'code' => $row->mechanism_id,
                        'uid' => null,
                        'created_by' => null,
                        'source' => 'DWH',
                        'processed' => false,
                        'posted' => false,
                    ]);
                } else {
                    // update ? --for now no
                }
                $facility = Facility::where('code', $row->code)->first();
                if ($partner && $facility) {
                    $facilityPartner = FacilityPartner::where('facility_id', $facility->id)
                        ->where('partner_id', $partner->id)
                        ->where('docket', 'CT')
                        ->first();
                    if (!$facilityPartner) {
                        $facility->partners()->save($facility, ['docket' => 'CT']);
                    }
                }
            });
    }
}
