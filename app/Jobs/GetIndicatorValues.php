<?php

namespace App\Jobs;

use App\Models\LiveSyncIndicator;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class GetIndicatorValues implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 1800;

    protected $indicator;
    protected $period;
    protected $facilities;

    public function __construct($indicator = null, $period = null, $facilities = [])
    {
        $this->indicator = $indicator;
        $this->period = is_null($period) ?
            now()->sub(1, 'month')->endOf('month')->startOf('day') :
            Carbon::parse($period)->endOf('month');
        $this->facilities = $facilities;
    }

    public function handle()
    {
        if (!count($this->facilities)) {
            return;
        }

        switch ($this->indicator) {
            case 'HTS_TESTED':
                $this->getHtsTested($this->period, $this->facilities);
                break;
            case 'HTS_TESTED_POS':
                $this->getHtsTestedPos($this->period, $this->facilities);
                break;
            case 'HTS_LINKED':
                $this->getHtsLinked($this->period, $this->facilities);
                break;
            case 'HTS_INDEX':
                $this->getHtsIndex($this->period, $this->facilities);
                break;
            case 'HTS_INDEX_POS':
                $this->getHtsIndexPos($this->period, $this->facilities);
                break;
            case 'TX_NEW':
                $this->getTxNew($this->period, $this->facilities);
                break;
            case 'TX_CURR':
                $this->getTxCurr($this->period, $this->facilities);
                break;
            case 'TX_RTT':
                // $this->getTxRtt($this->period, $this->facilities);
                break;
            case 'TX_ML':
                // $this->getTxMl($this->period, $this->facilities);
                break;
            case 'TX_PVLS':
                $this->getTxPvls($this->period, $this->facilities);
                break;
            case 'MMD':
                $this->getMmd($this->period, $this->facilities);
                break;
            case 'RETENTION_ON_ART_12_MONTHS':
                $this->getRetentionOnArt12Months($this->period, $this->facilities);
                break;
            case 'RETENTION_ON_ART_VL_1000_12_MONTHS':
                $this->getRetentionOnArtVl100012Months($this->period, $this->facilities);
                break;
            default:
                $this->getHtsTested($this->period, $this->facilities);
                $this->getHtsTestedPos($this->period, $this->facilities);
                // $this->getHtsLinked($this->period, $this->facilities);
                // $this->getHtsIndex($this->period, $this->facilities);
                // todo:: look into this $this->getHtsIndexPos($this->period, $this->facilities);
                $this->getTxNew($this->period, $this->facilities);
                $this->getTxCurr($this->period, $this->facilities);
                // $this->getTxRtt($this->period, $this->facilities);
                // $this->getTxMl($this->period, $this->facilities);
                // $this->getTxPvls($this->period, $this->facilities);
                // $this->getMmd($this->period, $this->facilities);
                $this->getRetentionOnArt12Months($this->period, $this->facilities);
                $this->getRetentionOnArtVl100012Months($this->period, $this->facilities);
        }
    }

    public function getHtsTested($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'NDWH']);
        $fetched = [];
        DB::connection('sqlsrv')->table('FactHTSClientTests')
            ->selectRaw('MFLCode as facility_code, SUM(Tested) as value')
            ->leftJoin('DimFacility', 'FactHTSClientTests.FacilityKey', '=', 'DimFacility.FacilityKey')
            ->whereNotNull('MFLCode')
            ->whereIn('Mflcode', array_keys($facilities))
            ->whereRaw('year(DateTestedKey) = ?', [$period->format('Y')])
            ->whereRaw('month(DateTestedKey) = ?', [$period->format('m')])
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities, $period, &$fetched) {
                LiveSyncIndicator::updateOrCreate(
                    [
                        'name' => 'HTS_TESTED',
                        'facility_id' => $facilities[$row->facility_code],
                        'stage' => 'DWH',
                        'indicator_date' => $period->format('Y-m-d H:i:s'),
                    ],
                    [
                        'value' => is_null($row->value) ? 0 : $row->value,
                        'indicator_id' => strtoupper(Str::uuid()),
                        'facility_manifest_id' => null,
                        'posted' => false
                    ]
                );
                $fetched[] = $row->facility_code;
                Log::info($facilities[$row->facility_code]);
                
                try {
                    $client = new Client();
                    $response = $client->request('POST', 'api/v1/metrics/facmetrics/dwhIndicator', [
                        'base_uri' => nova_get_setting(nova_get_setting('production') ? 'spot_api_url' : 'spot_api_url_staging'),
                        'verify' => false,
                        'timeout'  => 300,
                        'http_errors' => false,
                        'json' => [
                            'id' => strtoupper(Str::uuid()),
                            'facilityCode' => $row->facility_code,
                            'facilityName' => '',
                            'name' => 'HTS_TESTED',
                            'value' => is_null($row->value) ? 0 : $row->value,
                            'indicatorDate' => $period->format('Y-m-d H:i:s'),
                            'stage' => 'DWH',
                            'facilityManifestId' => null,
                        ]
                    ]);
                    if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                        // $this->liveSyncIndicator->posted = true;
                        // $this->liveSyncIndicator->save();
                    } else {
                        Log::error(
                            'PostLiveSyncIndicator: failed to post indicator ' 
                            // $this->liveSyncIndicator->name . ': ' .
                            // $this->liveSyncIndicator->facility->name
                        );
                    }
                } catch (\Exception $e) {
                    Log::error(
                        'PostLiveSyncIndicator: failed to post indicator ' .
                        // $this->liveSyncIndicator->name . ': ' .
                        // $this->liveSyncIndicator->facility->name . ': ' .
                        $e->getMessage()
                    );
                }
            });
        collect(array_diff(array_keys($facilities), $fetched))->each(function($facility_code) use ($facilities, $period) {
            LiveSyncIndicator::updateOrCreate(
                [
                    'name' => 'HTS_TESTED',
                    'facility_id' => $facilities[$facility_code],
                    'stage' => 'DWH',
                    'indicator_date' => $period->format('Y-m-d H:i:s'),
                ],
                [
                    'value' => 0,
                    'indicator_id' => strtoupper(Str::uuid()),
                    'facility_manifest_id' => null,
                    'posted' => false
                ]
            );
        });
    }

    public function getHtsTestedPos($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'NDWH']);
        $fetched = [];
        DB::connection('sqlsrv')->table('FactHTSClientTests')
            ->selectRaw('Mflcode as facility_code, SUM(Positive) as value')
            ->leftJoin('DimFacility', 'FactHTSClientTests.FacilityKey', '=', 'DimFacility.FacilityKey')
            ->whereNotNull('Mflcode')
            ->whereIn('Mflcode', array_keys($facilities))
            ->whereRaw('year(DateTestedKey) = ?', [$period->format('Y')])
            ->whereRaw('month(DateTestedKey) = ?', [$period->format('m')])
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) use ($facilities, $period, &$fetched) {
                LiveSyncIndicator::updateOrCreate(
                    [
                        'name' => 'HTS_TESTED_POS',
                        'facility_id' => $facilities[$row->facility_code],
                        'stage' => 'DWH',
                        'indicator_date' => $period->format('Y-m-d H:i:s'),
                    ],
                    [
                        'value' => is_null($row->value) ? 0 : $row->value,
                        'indicator_id' => strtoupper(Str::uuid()),
                        'facility_manifest_id' => null,
                        'posted' => false
                    ]
                );
                
        try {
            $client = new Client();
            $response = $client->request('POST', 'api/v1/metrics/facmetrics/dwhIndicator', [
                'base_uri' => nova_get_setting(nova_get_setting('production') ? 'spot_api_url' : 'spot_api_url_staging'),
                'verify' => false,
                'timeout'  => 300,
                'http_errors' => false,
                'json' => [
                    'id' => strtoupper(Str::uuid()),
                    'facilityCode' => $row->facility_code,
                    'facilityName' => '',
                    'name' => 'HTS_TESTED_POS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'indicatorDate' => $period->format('Y-m-d H:i:s'),
                    'stage' => 'DWH',
                    'facilityManifestId' => null,
                ]
            ]);
            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                // $this->liveSyncIndicator->posted = true;
                // $this->liveSyncIndicator->save();
            } else {
                Log::error(
                    'PostLiveSyncIndicator: failed to post indicator ' 
                    // $this->liveSyncIndicator->name . ': ' .
                    // $this->liveSyncIndicator->facility->name
                );
            }
        } catch (\Exception $e) {
            Log::error(
                'PostLiveSyncIndicator: failed to post indicator ' .
                // $this->liveSyncIndicator->name . ': ' .
                // $this->liveSyncIndicator->facility->name . ': ' .
                $e->getMessage()
            );
        }
            });
            
        collect(array_diff(array_keys($facilities), $fetched))->each(function($facility_code) use ($facilities, $period) {
            LiveSyncIndicator::updateOrCreate(
                [
                    'name' => 'HTS_TESTED_POS',
                    'facility_id' => $facilities[$facility_code],
                    'stage' => 'DWH',
                    'indicator_date' => $period->format('Y-m-d H:i:s'),
                ],
                [
                    'value' => 0,
                    'indicator_id' => strtoupper(Str::uuid()),
                    'facility_manifest_id' => null,
                    'posted' => false
                ]
            );
        });
    }

    public function getHtsLinked($period, $facilities)
    {
        config(['database.connections.mysql2.database' => 'portaldev']);
        $fetched = [];
        DB::connection('mysql2')->table('fact_htsuptake')
            ->selectRaw('Mflcode as facility_code, SUM(Linked) as value')
            ->whereNotNull('Mflcode')
            ->whereIn('Mflcode', array_keys($facilities))
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('m'))
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) use ($facilities, $period, &$fetched) {
                LiveSyncIndicator::updateOrCreate(
                    [
                        'name' => 'HTS_LINKED',
                        'facility_id' => $facilities[$row->facility_code],
                        'stage' => 'DWH',
                        'indicator_date' => $period->format('Y-m-d H:i:s'),
                    ],
                    [
                        'value' => is_null($row->value) ? 0 : $row->value,
                        'indicator_id' => strtoupper(Str::uuid()),
                        'facility_manifest_id' => null,
                        'posted' => false
                    ]
                );
                $fetched[] = $row->facility_code;
                Log::info($facilities[$row->facility_code]);
                
        try {
            $client = new Client();
            $response = $client->request('POST', 'api/v1/metrics/facmetrics/dwhIndicator', [
                'base_uri' => nova_get_setting(nova_get_setting('production') ? 'spot_api_url' : 'spot_api_url_staging'),
                'verify' => false,
                'timeout'  => 300,
                'http_errors' => false,
                'json' => [
                    'id' => strtoupper(Str::uuid()),
                    'facilityCode' => $row->facility_code,
                    'facilityName' => '',
                    'name' => 'HTS_LINKED',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'indicatorDate' => $period->format('Y-m-d H:i:s'),
                    'stage' => 'DWH',
                    'facilityManifestId' => null,
                ]
            ]);
            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                // $this->liveSyncIndicator->posted = true;
                // $this->liveSyncIndicator->save();
            } else {
                Log::error(
                    'PostLiveSyncIndicator: failed to post indicator ' 
                    // $this->liveSyncIndicator->name . ': ' .
                    // $this->liveSyncIndicator->facility->name
                );
            }
        } catch (\Exception $e) {
            Log::error(
                'PostLiveSyncIndicator: failed to post indicator ' .
                // $this->liveSyncIndicator->name . ': ' .
                // $this->liveSyncIndicator->facility->name . ': ' .
                $e->getMessage()
            );
        }
            });
                
        collect(array_diff(array_keys($facilities), $fetched))->each(function($facility_code) use ($facilities, $period) {
            LiveSyncIndicator::updateOrCreate(
                [
                    'name' => 'HTS_LINKED',
                    'facility_id' => $facilities[$facility_code],
                    'stage' => 'DWH',
                    'indicator_date' => $period->format('Y-m-d H:i:s'),
                ],
                [
                    'value' => 0,
                    'indicator_id' => strtoupper(Str::uuid()),
                    'facility_manifest_id' => null,
                    'posted' => false
                ]
            );
        });
    }

    public function getHtsIndex($period, $facilities)
    {
        //TODO:: we need to get the query
        config(['database.connections.sqlsrv.database' => 'NDWH']);
        $fetched = [];
        DB::connection('sqlsrv')->table('FactHTSClientTests')
            ->selectRaw('Mflcode as facility_code, SUM(Positive) as value')
            ->leftJoin('DimFacility', 'FactHTSClientTests.FacilityKey', '=', 'DimFacility.FacilityKey')
            ->whereNotNull('Mflcode')
            ->whereIn('Mflcode', array_keys($facilities))
            ->whereRaw('year(DateTestedKey) = ?', [$period->format('Y')])
            ->whereRaw('month(DateTestedKey) = ?', [$period->format('m')])
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) use ($facilities, $period, &$fetched) {
                LiveSyncIndicator::updateOrCreate(
                    [
                        'name' => 'HTS_INDEX',
                        'facility_id' => $facilities[$row->facility_code],
                        'stage' => 'DWH',
                        'indicator_date' => $period->format('Y-m-d H:i:s'),
                    ],
                    [
                        'value' => is_null($row->value) ? 0 : $row->value,
                        'indicator_id' => strtoupper(Str::uuid()),
                        'facility_manifest_id' => null,
                        'posted' => false
                    ]
                );
                $fetched[] = $row->facility_code;
            
                Log::info($facilities[$row->facility_code]);
                
        try {
            $client = new Client();
            $response = $client->request('POST', 'api/v1/metrics/facmetrics/dwhIndicator', [
                'base_uri' => nova_get_setting(nova_get_setting('production') ? 'spot_api_url' : 'spot_api_url_staging'),
                'verify' => false,
                'timeout'  => 300,
                'http_errors' => false,
                'json' => [
                    'id' => strtoupper(Str::uuid()),
                    'facilityCode' => $row->facility_code,
                    'facilityName' => '',
                    'name' => 'HTS_INDEX',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'indicatorDate' => $period->format('Y-m-d H:i:s'),
                    'stage' => 'DWH',
                    'facilityManifestId' => null,
                ]
            ]);
            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                // $this->liveSyncIndicator->posted = true;
                // $this->liveSyncIndicator->save();
            } else {
                Log::error(
                    'PostLiveSyncIndicator: failed to post indicator ' 
                    // $this->liveSyncIndicator->name . ': ' .
                    // $this->liveSyncIndicator->facility->name
                );
            }
        } catch (\Exception $e) {
            Log::error(
                'PostLiveSyncIndicator: failed to post indicator ' .
                // $this->liveSyncIndicator->name . ': ' .
                // $this->liveSyncIndicator->facility->name . ': ' .
                $e->getMessage()
            );
        }
            });
        collect(array_diff(array_keys($facilities), $fetched))->each(function($facility_code) use ($facilities, $period) {
            LiveSyncIndicator::updateOrCreate(
                [
                    'name' => 'HTS_INDEX',
                    'facility_id' => $facilities[$facility_code],
                    'stage' => 'DWH',
                    'indicator_date' => $period->format('Y-m-d H:i:s'),
                ],
                [
                    'value' => 0,
                    'indicator_id' => strtoupper(Str::uuid()),
                    'facility_manifest_id' => null,
                    'posted' => false
                ]
            );
        });
    }

    public function getHtsIndexPos($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'NDWH']);
        $fetched = [];
        DB::connection('sqlsrv')->table('fact_pns_knowledgehivstatus')
            ->selectRaw('Mflcode as facility_code, SUM(Positive) as value')
            ->whereNotNull('Mflcode')
            ->whereIn('Mflcode', array_keys($facilities))
            ->where('year', $period->format('Y'))
            ->where('month', $period->format('m'))
            ->groupBy('Mflcode')
            ->cursor()->each(function ($row) use ($facilities, $period, &$fetched) {
                LiveSyncIndicator::updateOrCreate(
                    [
                        'name' => 'HTS_INDEX_POS',
                        'facility_id' => $facilities[$row->facility_code],
                        'stage' => 'DWH',
                        'indicator_date' => $period->format('Y-m-d H:i:s'),
                    ],
                    [
                        'value' => is_null($row->value) ? 0 : $row->value,
                        'indicator_id' => strtoupper(Str::uuid()),
                        'facility_manifest_id' => null,
                        'posted' => false
                    ]
                );
                $fetched[] = $row->facility_code;
            
                Log::info($facilities[$row->facility_code]);
                
        try {
            $client = new Client();
            $response = $client->request('POST', 'api/v1/metrics/facmetrics/dwhIndicator', [
                'base_uri' => nova_get_setting(nova_get_setting('production') ? 'spot_api_url' : 'spot_api_url_staging'),
                'verify' => false,
                'timeout'  => 300,
                'http_errors' => false,
                'json' => [
                    'id' => strtoupper(Str::uuid()),
                    'facilityCode' => $row->facility_code,
                    'facilityName' => '',
                    'name' => 'HTS_INDEX_POS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'indicatorDate' => $period->format('Y-m-d H:i:s'),
                    'stage' => 'DWH',
                    'facilityManifestId' => null,
                ]
            ]);
            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                // $this->liveSyncIndicator->posted = true;
                // $this->liveSyncIndicator->save();
            } else {
                Log::error(
                    'PostLiveSyncIndicator: failed to post indicator ' 
                    // $this->liveSyncIndicator->name . ': ' .
                    // $this->liveSyncIndicator->facility->name
                );
            }
        } catch (\Exception $e) {
            Log::error(
                'PostLiveSyncIndicator: failed to post indicator ' .
                // $this->liveSyncIndicator->name . ': ' .
                // $this->liveSyncIndicator->facility->name . ': ' .
                $e->getMessage()
            );
        }
            });
        collect(array_diff(array_keys($facilities), $fetched))->each(function($facility_code) use ($facilities, $period) {
            LiveSyncIndicator::updateOrCreate(
                [
                    'name' => 'HTS_INDEX_POS',
                    'facility_id' => $facilities[$facility_code],
                    'stage' => 'DWH',
                    'indicator_date' => $period->format('Y-m-d H:i:s'),
                ],
                [
                    'value' => 0,
                    'indicator_id' => strtoupper(Str::uuid()),
                    'facility_manifest_id' => null,
                    'posted' => false
                ]
            );
        });
    }

    public function getTxNew($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'REPORTING']);
        $fetched = [];
        DB::connection('sqlsrv')->table('AggregateCohortRetention')
            ->selectRaw('MFLCode as facility_code, SUM(patients_startedART) as value')
            ->whereRaw("YEAR(CAST(REPLACE(StartARTYearMonth , '-', '') + '01' AS DATE))  = ?", [$period->format('Y')])
            ->whereRaw("MONTH(CAST(REPLACE(StartARTYearMonth , '-', '') + '01' AS DATE))  = ?", [$period->format('m')])
            ->whereNotNull('MFLCode')
            ->whereIn('MFLCode', array_keys($facilities))
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities, $period, &$fetched) {
                LiveSyncIndicator::updateOrCreate(
                    [
                        'name' => 'TX_NEW',
                        'facility_id' => $facilities[$row->facility_code],
                        'stage' => 'DWH',
                        'indicator_date' => $period->format('Y-m-d H:i:s'),
                    ],
                    [
                        'value' => is_null($row->value) ? 0 : $row->value,
                        'indicator_id' => strtoupper(Str::uuid()),
                        'facility_manifest_id' => null,
                        'posted' => false
                    ]
                );
                $fetched[] = $row->facility_code;
            
                Log::info($facilities[$row->facility_code]);
                
        try {
            $client = new Client();
            $response = $client->request('POST', 'api/v1/metrics/facmetrics/dwhIndicator', [
                'base_uri' => nova_get_setting(nova_get_setting('production') ? 'spot_api_url' : 'spot_api_url_staging'),
                'verify' => false,
                'timeout'  => 300,
                'http_errors' => false,
                'json' => [
                    'id' => strtoupper(Str::uuid()),
                    'facilityCode' => $row->facility_code,
                    'facilityName' => '',
                    'name' => 'TX_NEW',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'indicatorDate' => $period->format('Y-m-d H:i:s'),
                    'stage' => 'DWH',
                    'facilityManifestId' => null,
                ]
            ]);
            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                // $this->liveSyncIndicator->posted = true;
                // $this->liveSyncIndicator->save();
            } else {
                Log::error(
                    'PostLiveSyncIndicator: failed to post indicator ' 
                    // $this->liveSyncIndicator->name . ': ' .
                    // $this->liveSyncIndicator->facility->name
                );
            }
        } catch (\Exception $e) {
            Log::error(
                'PostLiveSyncIndicator: failed to post indicator ' .
                // $this->liveSyncIndicator->name . ': ' .
                // $this->liveSyncIndicator->facility->name . ': ' .
                $e->getMessage()
            );
        }
            });
        collect(array_diff(array_keys($facilities), $fetched))->each(function($facility_code) use ($facilities, $period) {
            LiveSyncIndicator::updateOrCreate(
                [
                    'name' => 'TX_NEW',
                    'facility_id' => $facilities[$facility_code],
                    'stage' => 'DWH',
                    'indicator_date' => $period->format('Y-m-d H:i:s'),
                ],
                [
                    'value' => 0,
                    'indicator_id' => strtoupper(Str::uuid()),
                    'facility_manifest_id' => null,
                    'posted' => false
                ]
            );
        });
    }

    public function getTxCurr($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'REPORTING']);
        $fetched = [];
        DB::connection('sqlsrv')->table('AggregateTXCurr')
            ->selectRaw('MFLCode as facility_code, SUM(CountClientsTXCur) as value')
            ->whereNotNull('MFLCode')
            ->whereIn('MFLCode', array_keys($facilities))
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities, $period, &$fetched) {
                LiveSyncIndicator::updateOrCreate(
                    [
                        'name' => 'TX_CURR',
                        'facility_id' => $facilities[$row->facility_code],
                        'stage' => 'DWH',
                        'indicator_date' => $period->format('Y-m-d H:i:s'),
                    ],
                    [
                        'value' => is_null($row->value) ? 0 : $row->value,
                        'indicator_id' => strtoupper(Str::uuid()),
                        'facility_manifest_id' => null,
                        'posted' => false
                    ]
                );
                $fetched[] = $row->facility_code;
            
                Log::info($facilities[$row->facility_code]);
                
        try {
            $client = new Client();
            $response = $client->request('POST', 'api/v1/metrics/facmetrics/dwhIndicator', [
                'base_uri' => nova_get_setting(nova_get_setting('production') ? 'spot_api_url' : 'spot_api_url_staging'),
                'verify' => false,
                'timeout'  => 300,
                'http_errors' => false,
                'json' => [
                    'id' => strtoupper(Str::uuid()),
                    'facilityCode' => $row->facility_code,
                    'facilityName' => '',
                    'name' => 'TX_CURR',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'indicatorDate' => $period->format('Y-m-d H:i:s'),
                    'stage' => 'DWH',
                    'facilityManifestId' => null,
                ]
            ]);
            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                // $this->liveSyncIndicator->posted = true;
                // $this->liveSyncIndicator->save();
            } else {
                Log::error(
                    'PostLiveSyncIndicator: failed to post indicator ' 
                    // $this->liveSyncIndicator->name . ': ' .
                    // $this->liveSyncIndicator->facility->name
                );
            }
        } catch (\Exception $e) {
            Log::error(
                'PostLiveSyncIndicator: failed to post indicator ' .
                // $this->liveSyncIndicator->name . ': ' .
                // $this->liveSyncIndicator->facility->name . ': ' .
                $e->getMessage()
            );
        }
            });
        collect(array_diff(array_keys($facilities), $fetched))->each(function($facility_code) use ($facilities, $period) {
            LiveSyncIndicator::updateOrCreate(
                [
                    'name' => 'TX_CURR',
                    'facility_id' => $facilities[$facility_code],
                    'stage' => 'DWH',
                    'indicator_date' => $period->format('Y-m-d H:i:s'),
                ],
                [
                    'value' => 0,
                    'indicator_id' => strtoupper(Str::uuid()),
                    'facility_manifest_id' => null,
                    'posted' => false
                ]
            );
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
        $fetched = [];
        DB::connection('sqlsrv')->table('Fact_Trans_HMIS_STATS_TXCURR')
            ->selectRaw('MFLCode as facility_code, SUM(Last12MVLSup) as value')
            ->whereNotNull('MFLCode')
            ->whereIn('MFLCode', array_keys($facilities))
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities, $period, &$fetched) {
                LiveSyncIndicator::updateOrCreate(
                    [
                        'name' => 'TX_PVLS',
                        'facility_id' => $facilities[$row->facility_code],
                        'stage' => 'DWH',
                        'indicator_date' => $period->format('Y-m-d H:i:s'),
                    ],
                    [
                        'value' => is_null($row->value) ? 0 : $row->value,
                        'indicator_id' => strtoupper(Str::uuid()),
                        'facility_manifest_id' => null,
                        'posted' => false
                    ]
                );
                $fetched[] = $row->facility_code;
            });
        collect(array_diff(array_keys($facilities), $fetched))->each(function($facility_code) use ($facilities, $period) {
            LiveSyncIndicator::updateOrCreate(
                [
                    'name' => 'TX_PVLS',
                    'facility_id' => $facilities[$facility_code],
                    'stage' => 'DWH',
                    'indicator_date' => $period->format('Y-m-d H:i:s'),
                ],
                [
                    'value' => 0,
                    'indicator_id' => strtoupper(Str::uuid()),
                    'facility_manifest_id' => null,
                    'posted' => false
                ]
            );
        });
    }

    public function getMmd($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        $fetched = [];
        DB::connection('sqlsrv')->table('FACT_Trans_DSD_MMDUptake')
            ->selectRaw('MFLCode as facility_code, SUM(MMD) as value')
            ->whereNotNull('MFLCode')
            ->whereIn('MFLCode', array_keys($facilities))
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities, $period, &$fetched) {
                LiveSyncIndicator::updateOrCreate(
                    [
                        'name' => 'MMD',
                        'facility_id' => $facilities[$row->facility_code],
                        'stage' => 'DWH',
                        'indicator_date' => $period->format('Y-m-d H:i:s'),
                    ],
                    [
                        'value' => is_null($row->value) ? 0 : $row->value,
                        'indicator_id' => strtoupper(Str::uuid()),
                        'facility_manifest_id' => null,
                        'posted' => false
                    ]
                );
                $fetched[] = $row->facility_code;
            });
        collect(array_diff(array_keys($facilities), $fetched))->each(function($facility_code) use ($facilities, $period) {
            LiveSyncIndicator::updateOrCreate(
                [
                    'name' => 'MMD',
                    'facility_id' => $facilities[$facility_code],
                    'stage' => 'DWH',
                    'indicator_date' => $period->format('Y-m-d H:i:s'),
                ],
                [
                    'value' => 0,
                    'indicator_id' => strtoupper(Str::uuid()),
                    'facility_manifest_id' => null,
                    'posted' => false
                ]
            );
        });
    }

    public function getRetentionOnArt12Months($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        $fetched = [];
        DB::connection('sqlsrv')->table('FACT_ART_Retention731')
            ->selectRaw('MFLCode as facility_code, SUM(Active12M) as value')
            ->where('StartMonth', $period->format('m'))
            ->where('StartYear', $period->format('Y'))
            ->whereNotNull('MFLCode')
            ->whereIn('MFLCode', array_keys($facilities))
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities, $period, &$fetched) {
                LiveSyncIndicator::updateOrCreate(
                    [
                        'name' => 'RETENTION_ON_ART_12_MONTHS',
                        'facility_id' => $facilities[$row->facility_code],
                        'stage' => 'DWH',
                        'indicator_date' => $period->format('Y-m-d H:i:s'),
                    ],
                    [
                        'value' => is_null($row->value) ? 0 : $row->value,
                        'indicator_id' => strtoupper(Str::uuid()),
                        'facility_manifest_id' => null,
                        'posted' => false
                    ]
                );
                $fetched[] = $row->facility_code;
            
                Log::info($facilities[$row->facility_code]);
                
        try {
            $client = new Client();
            $response = $client->request('POST', 'api/v1/metrics/facmetrics/dwhIndicator', [
                'base_uri' => nova_get_setting(nova_get_setting('production') ? 'spot_api_url' : 'spot_api_url_staging'),
                'verify' => false,
                'timeout'  => 300,
                'http_errors' => false,
                'json' => [
                    'id' => strtoupper(Str::uuid()),
                    'facilityCode' => $row->facility_code,
                    'facilityName' => '',
                    'name' => 'RETENTION_ON_ART_12_MONTHS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'indicatorDate' => $period->format('Y-m-d H:i:s'),
                    'stage' => 'DWH',
                    'facilityManifestId' => null,
                ]
            ]);
            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                // $this->liveSyncIndicator->posted = true;
                // $this->liveSyncIndicator->save();
            } else {
                Log::error(
                    'PostLiveSyncIndicator: failed to post indicator ' 
                    // $this->liveSyncIndicator->name . ': ' .
                    // $this->liveSyncIndicator->facility->name
                );
            }
        } catch (\Exception $e) {
            Log::error(
                'PostLiveSyncIndicator: failed to post indicator ' .
                // $this->liveSyncIndicator->name . ': ' .
                // $this->liveSyncIndicator->facility->name . ': ' .
                $e->getMessage()
            );
        }
            });
        collect(array_diff(array_keys($facilities), $fetched))->each(function($facility_code) use ($facilities, $period) {
            LiveSyncIndicator::updateOrCreate(
                [
                    'name' => 'RETENTION_ON_ART_12_MONTHS',
                    'facility_id' => $facilities[$facility_code],
                    'stage' => 'DWH',
                    'indicator_date' => $period->format('Y-m-d H:i:s'),
                ],
                [
                    'value' => 0,
                    'indicator_id' => strtoupper(Str::uuid()),
                    'facility_manifest_id' => null,
                    'posted' => false
                ]
            );
        });
    }

    public function getRetentionOnArtVl100012Months($period, $facilities)
    {
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        $fetched = [];
        DB::connection('sqlsrv')->table('FACT_Trans_Retention')
            ->selectRaw('MFLCode as facility_code, sum ([12Mstatus]) as value')
            ->whereNotNull('MFLCode')
            ->whereIn('MFLCode', array_keys($facilities))
            ->where('StartART_Year', $period->format('Y'))
            ->where('StartART_Month', $period->format('m'))
            ->groupBy('MFLCode')
            ->cursor()->each(function ($row) use ($facilities, $period, &$fetched) {
                LiveSyncIndicator::updateOrCreate(
                    [
                        'name' => 'RETENTION_ON_ART_VL_1000_12_MONTHS',
                        'facility_id' => $facilities[$row->facility_code],
                        'stage' => 'DWH',
                        'indicator_date' => $period->format('Y-m-d H:i:s'),
                    ],
                    [
                        'value' => is_null($row->value) ? 0 : $row->value,
                        'indicator_id' => strtoupper(Str::uuid()),
                        'facility_manifest_id' => null,
                        'posted' => false
                    ]
                );
                $fetched[] = $row->facility_code;
            
                Log::info($facilities[$row->facility_code]);
                
        try {
            $client = new Client();
            $response = $client->request('POST', 'api/v1/metrics/facmetrics/dwhIndicator', [
                'base_uri' => nova_get_setting(nova_get_setting('production') ? 'spot_api_url' : 'spot_api_url_staging'),
                'verify' => false,
                'timeout'  => 300,
                'http_errors' => false,
                'json' => [
                    'id' => strtoupper(Str::uuid()),
                    'facilityCode' => $row->facility_code,
                    'facilityName' => '',
                    'name' => 'RETENTION_ON_ART_VL_1000_12_MONTHS',
                    'value' => is_null($row->value) ? 0 : $row->value,
                    'indicatorDate' => $period->format('Y-m-d H:i:s'),
                    'stage' => 'DWH',
                    'facilityManifestId' => null,
                ]
            ]);
            if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
                // $this->liveSyncIndicator->posted = true;
                // $this->liveSyncIndicator->save();
            } else {
                Log::error(
                    'PostLiveSyncIndicator: failed to post indicator ' 
                    // $this->liveSyncIndicator->name . ': ' .
                    // $this->liveSyncIndicator->facility->name
                );
            }
        } catch (\Exception $e) {
            Log::error(
                'PostLiveSyncIndicator: failed to post indicator ' .
                // $this->liveSyncIndicator->name . ': ' .
                // $this->liveSyncIndicator->facility->name . ': ' .
                $e->getMessage()
            );
        }
            });
        collect(array_diff(array_keys($facilities), $fetched))->each(function($facility_code) use ($facilities, $period) {
            LiveSyncIndicator::updateOrCreate(
                [
                    'name' => 'RETENTION_ON_ART_VL_1000_12_MONTHS',
                    'facility_id' => $facilities[$facility_code],
                    'stage' => 'DWH',
                    'indicator_date' => $period->format('Y-m-d H:i:s'),
                ],
                [
                    'value' => 0,
                    'indicator_id' => strtoupper(Str::uuid()),
                    'facility_manifest_id' => null,
                    'posted' => false
                ]
            );
        });
    }
}
