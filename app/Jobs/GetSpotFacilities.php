<?php

namespace App\Jobs;

use App\Models\Facility;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GetSpotFacilities implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        $client = new Client();
        $response = $client->request('GET', 'api/v1/transfers/manifests/all', [
            'base_uri' => nova_get_setting('spot_api_url'),
            'verify' => false,
            'timeout'  => 60,
            'http_errors' => false,
        ]);
        if ($response->getStatusCode() == 200) {
            $response = json_decode($response->getBody(), true);
            foreach ($response as $transfer) {
                $facility = Facility::where('code', $transfer['code'])->first();
                if (!$facility) {
                    $facility = Facility::create([
                        'name' => $transfer['name'],
                        'code' => $transfer['code'],
                        'uid' => isset($transfer['facility']['_id']) ? $transfer['facility']['_id'] : '',
                        'county' => isset($transfer['facility']['masterFacility']['county']['name']) ? $transfer['facility']['masterFacility']['county']['name'] : '',
                        'source' => 'SPOT',
                        'processed' => false,
                        'posted' => false,
                    ]);
                } else {
                    if (!$facility->uid) {
                        $facility->update([
                            'uid' => isset($transfer['facility']['_id']) ? $transfer['facility']['_id'] : '',
                            'processed' => false,
                            'posted' => false,
                        ]);
                    }
                }
            }
        }
    }
}
