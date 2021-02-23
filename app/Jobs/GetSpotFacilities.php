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
            'base_uri' => env('SPOT_API_URL'),
            'verify' => false,
            'timeout'  => 60,
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
                        'partner' => isset($transfer['facility']['masterFacility']['mechanism']['name']) ? $transfer['facility']['masterFacility']['mechanism']['name'] : '',
                        'source' => 'SPOT',
                        'posted' => false,
                    ]);
                } else {
                    // update ? --for now no
                }
            }
        }
    }
}
