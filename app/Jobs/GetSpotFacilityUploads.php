<?php

namespace App\Jobs;

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

class GetSpotFacilityUploads implements ShouldQueue
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
        $response = $client->request('GET', 'api/v1/transfers/facilities/'.$facility->uid, [
            'base_uri' => env('SPOT_API_URL'),
            'verify' => false,
            'timeout'  => 60,
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
                        'expected' => $upload['patientCount'],
                        'received' => $upload['recievedCount'],
                        'status' => $upload['handshakeStatus'],
                        'processed' => false,
                        'posted' => false,
                    ]);
                } else {
                    if (($facilityUpload->status !== $upload['handshakeStatus']) || ($facilityUpload->received !== $upload['received'])) {
                        $facilityUpload->update([
                            'status' => $upload['handshakeStatus'],
                            'received' => $upload['received'],
                            'processed' => false,
                            'posted' => false,
                        ]);
                    }
                }
            }
        }
    }
}
