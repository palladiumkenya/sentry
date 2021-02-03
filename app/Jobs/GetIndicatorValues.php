<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GetIndicatorValues implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $indicator;

    public function __construct($indicator)
    {
        $this->indicator = $indicator;
    }

    public function handle()
    {
        switch ($this->indicator) {
            case 'TX_NEW':
                $this->getTxNew();
                break;
            case 'TX_CURR':
                $this->getTxCurr();
                break;
            case '12M_VL':
                $this->get12mVl();
                break;
            case 'HTS_TST':
                $this->getHtsTst();
                break;
            case 'HTS_POS':
                $this->getHtsPos();
                break;
            case 'HTS_LNK':
                $this->getHtsLnk();
                break;
            default:
                $this->getTxNew();
                $this->getTxCurr();
                $this->get12mVl();
                $this->getHtsTst();
                $this->getHtsPos();
                $this->getHtsLnk();
        }
    }

    public function getTxNew()
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        $period = now()->startOf('month')->sub(1, 'month');
        DB::connection('sqlsrv')->table('FACT_Trans_Newly_Started')
            ->selectRaw('MFLCode as facility_code, FacilityName as facility_name, SUM(StartedART) as value')
            ->where('Start_Year', $period->format('Y'))
            ->where('StartART_Month', $period->format('n'))
            ->groupBy('MFLCode')->groupBy('FacilityName')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'TX_NEW',
                    'value' => $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => 'F425DF84-FA3D-49A3-834B-ABD300AE10E8',
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getTxCurr()
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('Fact_Trans_HMIS_STATS_TXCURR')
            ->selectRaw('MFLCode as facility_code, FacilityName as facility_name, SUM(TXCURR_Total) as value')
            ->groupBy('MFLCode')->groupBy('FacilityName')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'TX_CURR',
                    'value' => $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => 'F425DF84-FA3D-49A3-834B-ABD300AE10E8',
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function get12mVl()
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('Fact_Trans_HMIS_STATS_TXCURR')
            ->selectRaw('MFLCode as facility_code, FacilityName as facility_name, SUM(Last12MonthVL) as value')
            ->groupBy('MFLCode')->groupBy('FacilityName')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => '12M_VL',
                    'value' => $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => 'F425DF84-FA3D-49A3-834B-ABD300AE10E8',
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getHtsPos()
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        $period = now()->startOf('month')->sub(1, 'month');
        DB::connection('mysql2')->table('fact_htsuptake')
            ->selectRaw('Mflcode as facility_code, FacilityName as facility_name, SUM(Positive) as value')
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')->groupBy('FacilityName')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_POS',
                    'value' => $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => 'F425DF84-FA3D-49A3-834B-ABD300AE10E8',
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getHtsTst()
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        $period = now()->startOf('month')->sub(1, 'month');
        DB::connection('mysql2')->table('fact_htsuptake')
            ->selectRaw('Mflcode as facility_code, FacilityName as facility_name, SUM(Tested) as value')
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')->groupBy('FacilityName')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_TST',
                    'value' => $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => 'F425DF84-FA3D-49A3-834B-ABD300AE10E8',
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getHtsLnk()
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        $period = now()->startOf('month')->sub(1, 'month');
        DB::connection('mysql2')->table('fact_htsuptake')
            ->selectRaw('Mflcode as facility_code, FacilityName as facility_name, SUM(Linked) as value')
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')->groupBy('FacilityName')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_LNK',
                    'value' => $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => 'F425DF84-FA3D-49A3-834B-ABD300AE10E8',
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }
}
