<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            case 'RETENTION_ON_ART_12_MONTHS':
                $this->getRetentionOnArt12Months();
                break;
            case 'RETENTION_ON_ART_VL_1000_12_MONTHS':
                $this->getRetentionOnArtVl100012Months();
                break;
            case 'TX_PVLS':
                $this->getTxPvls();
                break;
            case 'MMD':
                $this->getMmd();
                break;
            case 'HTS_TESTED':
                $this->getHtsTested();
                break;
            case 'HTS_TESTED_POS':
                $this->getHtsTestedPos();
                break;
            case 'HTS_LINKED':
                $this->getHtsLinked();
                break;
            case 'HTS_INDEX':
                $this->getHtsIndex();
                break;
            case 'HTS_INDEX_POS':
                $this->getHtsIndexPos();
                break;
            default:
                $this->getTxNew();
                $this->getTxCurr();
                $this->getRetentionOnArt12Months();
                $this->getRetentionOnArtVl100012Months();
                $this->getTxPvls();
                $this->getMmd();
                $this->getHtsTested();
                $this->getHtsTestedPos();
                $this->getHtsLinked();
                $this->getHtsIndex();
                $this->getHtsIndexPos();
        }
    }

    public function getTxNew()
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        $period = now()->startOf('month')->sub(1, 'month');
        DB::connection('sqlsrv')->table('FACT_Trans_Newly_Started')
            ->selectRaw('MFLCode as facility_code, MAX(FacilityName) as facility_name, SUM(StartedART) as value')
            ->where('Start_Year', $period->format('Y'))
            ->where('StartART_Month', $period->format('n'))
            ->whereNotNull('MFLCode')
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'TX_NEW',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
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
            ->selectRaw('MFLCode as facility_code, MAX(FacilityName) as facility_name, SUM(TXCURR_Total) as value')
            ->whereNotNull('MFLCode')
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'TX_CURR',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getRetentionOnArt12Months()
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('Fact_Trans_HMIS_STATS_TXCURR')
            ->selectRaw('MFLCode as facility_code, MAX(FacilityName) as facility_name, SUM(Last12MonthVL) as value')
            ->whereNotNull('MFLCode')
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'RETENTION_ON_ART_12_MONTHS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getRetentionOnArtVl100012Months()
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('Fact_Trans_HMIS_STATS_TXCURR')
            ->selectRaw('MFLCode as facility_code, MAX(FacilityName) as facility_name, SUM(Last12MVLSup) as value')
            ->whereNotNull('MFLCode')
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'RETENTION_ON_ART_VL_1000_12_MONTHS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getTxPvls()
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('Fact_Trans_HMIS_STATS_TXCURR')
            ->selectRaw('MFLCode as facility_code, MAX(FacilityName) as facility_name, SUM(Last12MVLSup) as value1, SUM(Last12MonthVL) as value2')
            ->whereNotNull('MFLCode')
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) {
                $value = intval((is_null($row->value2) ? 0 : $row->value2)) > 0 ?
                    (intval((is_null($row->value1) ? 0 : $row->value1))/intval((is_null($row->value2) ? 0 : $row->value2))) * 100 : 0;
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'TX_PVLS',
                    'value' => number_format((float) $value, 0, '.', ''),
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getMmd()
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('FACT_Trans_DSD_Cascade')
            ->selectRaw('MFLCode as facility_code, MAX(FacilityName) as facility_name, SUM(OnMMD) as value')
            ->whereNotNull('MFLCode')
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'MMD',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getHtsTested()
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        $period = now()->startOf('month')->sub(1, 'month');
        DB::connection('mysql2')->table('fact_htsuptake')
            ->selectRaw('Mflcode as facility_code, MAX(FacilityName) as facility_name, SUM(Tested) as value')
            ->whereNotNull('Mflcode')
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_TESTED',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getHtsTestedPos()
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        $period = now()->startOf('month')->sub(1, 'month');
        DB::connection('mysql2')->table('fact_htsuptake')
            ->selectRaw('Mflcode as facility_code, MAX(FacilityName) as facility_name, SUM(Positive) as value')
            ->whereNotNull('Mflcode')
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_TESTED_POS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getHtsLinked()
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        $period = now()->startOf('month')->sub(1, 'month');
        DB::connection('mysql2')->table('fact_htsuptake')
            ->selectRaw('Mflcode as facility_code, MAX(FacilityName) as facility_name, SUM(Linked) as value')
            ->whereNotNull('Mflcode')
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_LINKED',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getHtsIndex()
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        $period = now()->startOf('month')->sub(1, 'month');
        DB::connection('mysql2')->table('fact_htsuptake')
            ->selectRaw('Mflcode as facility_code, MAX(FacilityName) as facility_name, SUM(Positive) as value')
            ->whereNotNull('Mflcode')
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_INDEX',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getHtsIndexPos()
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        $period = now()->startOf('month')->sub(1, 'month');
        DB::connection('mysql2')->table('fact_pns_knowledgehivstatus')
            ->selectRaw('Mflcode as facility_code, MAX(FacilityName) as facility_name, SUM(Positive) as value')
            ->whereNotNull('Mflcode')
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_INDEX_POS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_code' => $row->facility_code,
                    'facility_name' => $row->facility_name,
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                ]);
            });
    }
}
