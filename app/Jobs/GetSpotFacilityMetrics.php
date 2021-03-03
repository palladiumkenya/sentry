<?php

namespace App\Jobs;

use App\Models\EtlJob;
use App\Models\Facility;
use App\Models\FacilityMetric;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GetSpotFacilityMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $etlJob;
    protected $facility;

    public function __construct(EtlJob $etlJob, Facility $facility)
    {
        $this->etlJob = $etlJob;
        $this->facility = $facility;
    }

    public function handle()
    {
        $facility = $this->facility;
        if (!$facility->uid) {
            return;
        }
        $client = new Client();
        $response = $client->request('GET', 'api/v1/metrics/facmetrics/getIndicatorsByFacilityId/'.$facility->uid, [
            'base_uri' => nova_get_setting('spot_api_url'),
            'verify' => false,
            'timeout'  => 60,
            'http_errors' => false,
        ]);
        if ($response->getStatusCode() == 200) {
            $response = json_decode($response->getBody(), true);
            foreach ($response as $metric) {
                $facilityMetric = FacilityMetric::where('uid', $metric['_id'])->first();
                if (!$facilityMetric) {
                    $facilityMetric = FacilityMetric::create([
                        'uid' => $metric['_id'],
                        'facility_id' => $facility->id,
                        'create_date' => $metric['createDate'] ? Carbon::parse($metric['createDate'])->format('Y-m-d H:i:s') : null,
                        'name' => $metric['name'],
                        'value' => $metric['value'],
                        'metric_date' => Carbon::parse($metric['indicatorDate'])->format('Y-m-d H:i:s'),
                        'dwh_value' => $metric['dwhValue'],
                        'dwh_metric_date' => $metric['dwhIndicatorDate'] ? Carbon::parse($metric['dwhIndicatorDate'])->format('Y-m-d H:i:s') : null,
                        'manifest_id' => $metric['facilityManifestId'],
                        'processed' => false,
                        'posted' => false,
                        'etl_job_id' => $this->etlJob->id,
                    ]);
                } else {
                    $facilityMetric->update([
                        'facility_id' => $facility->id,
                        'create_date' => $metric['createDate'] ? Carbon::parse($metric['createDate'])->format('Y-m-d H:i:s') : null,
                        'name' => $metric['name'],
                        'value' => $metric['value'],
                        'metric_date' => Carbon::parse($metric['indicatorDate'])->format('Y-m-d H:i:s'),
                        'dwh_value' => $metric['dwhValue'],
                        'dwh_metric_date' => $metric['dwhIndicatorDate'] ? Carbon::parse($metric['dwhIndicatorDate'])->format('Y-m-d H:i:s') : null,
                        'manifest_id' => $metric['facilityManifestId'],
                        'processed' => false,
                        'posted' => false,
                        'etl_job_id' => $this->etlJob->id,
                    ]);
                }
            }
        }
    }
}
