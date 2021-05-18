<?php

namespace App\Jobs;

use App\Models\EtlJob;
use App\Models\Facility;
use App\Models\FacilityUpload;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GetSpotFacilityUploads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    protected $etlJob;
    protected $facility;

    public function __construct(EtlJob $etlJob, Facility $facility)
    {
        $this->etlJob = $etlJob;
        $this->facility = $facility;
    }

    public function handle()
    {
        $etlJob = $this->etlJob;
        $facility = $this->facility;
        if (!$facility->uid) {
            return;
        }
        $client = new Client();
        $response = $client->request('GET', 'api/v1/transfers/facilities/'.$facility->uid, [
            'base_uri' => nova_get_setting(nova_get_setting('production') ? 'spot_api_url' : 'spot_api_url_staging'),
            'verify' => false,
            'timeout'  => 30,
            'http_errors' => false,
        ]);
        if ($response->getStatusCode() == 200) {
            $response = json_decode($response->getBody(), true);
            $uploads = isset($response['manifests']) ? $response['manifests']:[];
            foreach ($uploads as $upload) {
                $facilityUpload = FacilityUpload::where('uid', $upload['_id'])->first();
                if (!$facilityUpload) {
                    $facilityUpload = FacilityUpload::create([
                        'uid' => $upload['_id'],
                        'facility_id' => $facility->id,
                        'updated' => $upload['logDate'] ? Carbon::parse($upload['logDate'])->format('Y-m-d H:i:s') : null,
                        'docket' => $upload['docket'],
                        'expected' => isset($upload['patientCount']) ? $upload['patientCount'] : null,
                        'received' => isset($upload['recievedCount']) ? $upload['recievedCount'] : null,
                        'status' => isset($upload['handshakeStatus']) ? $upload['handshakeStatus'] : null,
                        'processed' => false,
                        'posted' => false,
                        'etl_job_id' => $etlJob->id,
                    ]);
                } else {
                    $facilityUpload->update([
                        'status' => isset($upload['handshakeStatus']) ? $upload['handshakeStatus'] : null,
                        'received' => isset($upload['recievedCount']) ? $upload['recievedCount'] : null,
                        'processed' => false,
                        'posted' => false,
                        'etl_job_id' => $etlJob->id,
                    ]);
                }
            }
        } else {
            Log::error('GetSpotFacilityUploads: failed to fetch uploads for facility '.$facility->uid);
        }
    }
}
