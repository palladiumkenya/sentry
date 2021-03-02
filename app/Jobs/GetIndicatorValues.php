<?php

namespace App\Jobs;

use App\Models\Facility;
use Carbon\Carbon;
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
    protected $period;
    protected $facilities;

    public function __construct($indicator = null, $period = null)
    {
        $this->indicator = $indicator;
        $this->period = is_null($period) ?
            now()->startOf('month')->sub(1, 'month')->endOfDay() :
            Carbon::parse($period)->endOfDay();
    }

    public function handle()
    {
        $facilities = [];
        Facility::where('etl', true)->cursor()->each(function($facility) use (&$facilities) {
            $facilities[$facility->code] = $facility->id;
        });
        if (!count($facilities)) {
            return;
        }
        $this->facilities = $facilities;
        switch ($this->indicator) {
            case 'HTS_TESTED':
                $this->getHtsTested($this->period, $facilities);
                break;
            case 'HTS_TESTED_POS':
                $this->getHtsTestedPos($this->period, $facilities);
                break;
            case 'HTS_LINKED':
                $this->getHtsLinked($this->period, $facilities);
                break;
            case 'HTS_INDEX':
                $this->getHtsIndex($this->period, $facilities);
                break;
            case 'HTS_INDEX_POS':
                $this->getHtsIndexPos($this->period, $facilities);
                break;
            case 'TX_NEW':
                $this->getTxNew($this->period, $facilities);
                break;
            case 'TX_CURR':
                $this->getTxCurr($this->period, $facilities);
                break;
            case 'TX_RTT':
                // $this->getTxRtt($this->period, $facilities);
                break;
            case 'TX_ML':
                // $this->getTxMl($this->period, $facilities);
                break;
            case 'TX_PVLS':
                $this->getTxPvls($this->period, $facilities);
                break;
            case 'MMD':
                $this->getMmd($this->period, $facilities);
                break;
            case 'RETENTION_ON_ART_12_MONTHS':
                $this->getRetentionOnArt12Months($this->period, $facilities);
                break;
            case 'RETENTION_ON_ART_VL_1000_12_MONTHS':
                $this->getRetentionOnArtVl100012Months($this->period, $facilities);
                break;
            default:
                $this->getHtsTested($this->period, $facilities);
                $this->getHtsTestedPos($this->period, $facilities);
                $this->getHtsLinked($this->period, $facilities);
                $this->getHtsIndex($this->period, $facilities);
                $this->getHtsIndexPos($this->period, $facilities);
                $this->getTxNew($this->period, $facilities);
                $this->getTxCurr($this->period, $facilities);
                // $this->getTxRtt($this->period, $facilities);
                // $this->getTxMl($this->period, $facilities);
                $this->getTxPvls($this->period, $facilities);
                $this->getMmd($this->period, $facilities);
                $this->getRetentionOnArt12Months($this->period, $facilities);
                $this->getRetentionOnArtVl100012Months($this->period, $facilities);
        }
    }

    public function getHtsTested($period, $facilities)
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        DB::connection('mysql2')->table('fact_htsuptake')
            ->selectRaw('Mflcode as facility_code, SUM(Tested) as value')
            ->whereNotNull('Mflcode')
            ->whereIn('Mflcode', array_keys($facilities))
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) use ($facilities) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_TESTED',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_id' => $facilities[$row->facility_code],
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getHtsTestedPos($period, $facilities)
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        DB::connection('mysql2')->table('fact_htsuptake')
            ->selectRaw('Mflcode as facility_code, SUM(Positive) as value')
            ->whereNotNull('Mflcode')
            ->whereIn('Mflcode', array_keys($facilities))
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) use ($facilities) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_TESTED_POS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_id' => $facilities[$row->facility_code],
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getHtsLinked($period, $facilities)
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        DB::connection('mysql2')->table('fact_htsuptake')
            ->selectRaw('Mflcode as facility_code, SUM(Linked) as value')
            ->whereNotNull('Mflcode')
            ->whereIn('Mflcode', array_keys($facilities))
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) use ($facilities) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_LINKED',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_id' => $facilities[$row->facility_code],
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getHtsIndex($period, $facilities)
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        DB::connection('mysql2')->table('fact_htsuptake')
            ->selectRaw('Mflcode as facility_code, SUM(Positive) as value')
            ->whereNotNull('Mflcode')
            ->whereIn('Mflcode', array_keys($facilities))
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) use ($facilities) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_INDEX',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_id' => $facilities[$row->facility_code],
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getHtsIndexPos($period, $facilities)
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        DB::connection('mysql2')->table('fact_pns_knowledgehivstatus')
            ->selectRaw('Mflcode as facility_code, SUM(Positive) as value')
            ->whereNotNull('Mflcode')
            ->whereIn('Mflcode', array_keys($facilities))
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('n'))
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) use ($facilities) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'HTS_INDEX_POS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_id' => $facilities[$row->facility_code],
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getTxNew($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('FACT_Trans_Newly_Started')
            ->selectRaw('MFLCode as facility_code, SUM(StartedART) as value')
            ->where('Start_Year', $period->format('Y'))
            ->where('StartART_Month', $period->format('n'))
            ->whereNotNull('MFLCode')
            ->whereIn('MFLCode', array_keys($facilities))
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'TX_NEW',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_id' => $facilities[$row->facility_code],
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getTxCurr($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('Fact_Trans_HMIS_STATS_TXCURR')
            ->selectRaw('MFLCode as facility_code, SUM(TXCURR_Total) as value')
            ->whereNotNull('MFLCode')
            ->whereIn('MFLCode', array_keys($facilities))
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'TX_CURR',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_id' => $facilities[$row->facility_code],
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    // public function getTxRtt($period, $facilities)
    // {
    //     //
    // }

    // public function getTxMl($period, $facilities)
    // {
    //     //
    // }

    public function getTxPvls($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('Fact_Trans_HMIS_STATS_TXCURR')
            ->selectRaw('MFLCode as facility_code, SUM(Last12MVLSup) as value')
            ->whereNotNull('MFLCode')
            ->whereIn('MFLCode', array_keys($facilities))
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'TX_PVLS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_id' => $facilities[$row->facility_code],
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getMmd($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('FACT_Trans_DSD_MMDUptake')
            ->selectRaw('MFLCode as facility_code, SUM(MMD) as value')
            ->whereNotNull('MFLCode')
            ->whereIn('MFLCode', array_keys($facilities))
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'MMD',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_id' => $facilities[$row->facility_code],
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getRetentionOnArt12Months($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('FACT_ART_Retention731')
            ->selectRaw('MFLCode as facility_code, SUM(Active12M) as value')
            ->where('StartYear', $period->format('Y'))
            ->where('StartMonth', $period->format('n'))
            ->whereNotNull('MFLCode')
            ->whereIn('MFLCode', array_keys($facilities))
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'RETENTION_ON_ART_12_MONTHS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_id' => $facilities[$row->facility_code],
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }

    public function getRetentionOnArtVl100012Months($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        DB::connection('sqlsrv')->table('FACT_ART_Retention731')
            ->selectRaw('MFLCode as facility_code, SUM(m12_Last12MVLSup) as value')
            ->whereNotNull('MFLCode')
            ->whereIn('MFLCode', array_keys($facilities))
            ->where('StartYear', $period->format('Y'))
            ->where('StartMonth', $period->format('n'))
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities) {
                \App\Models\LiveSyncIndicator::create([
                    'name' => 'RETENTION_ON_ART_VL_1000_12_MONTHS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'facility_id' => $facilities[$row->facility_code],
                    'indicator_date' => now(),
                    'indicator_id' => strtoupper(Str::uuid()),
                    'stage' => 'DWH',
                    'facility_manifest_id' => null,
                    'posted' => false
                ]);
            });
    }
}
