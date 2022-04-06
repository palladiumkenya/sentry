<?php

namespace App\Jobs;

use App\Models\LiveSyncIndicator;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PostLiveSyncIndicator implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 1800;

    protected $liveSyncIndicator;

    public function __construct(LiveSyncIndicator $liveSyncIndicator)
    {
        $this->liveSyncIndicator = $liveSyncIndicator;
    }

    public function handle()
    {
        $client = new Client();
        $response = $client->request('POST', 'stages/indicator', [
            'base_uri' => nova_get_setting(nova_get_setting('production') ? 'live_sync_api_url' : 'live_sync_api_url_staging'),
            'verify' => false,
            'timeout'  => 30,
            'http_errors' => false,
            'json' => [[
                'id' => $this->liveSyncIndicator->indicator_id,
                'facilityCode' => $this->liveSyncIndicator->facility->code,
                'facilityName' => $this->liveSyncIndicator->facility->name,
                'name' => $this->liveSyncIndicator->name,
                'value' => $this->liveSyncIndicator->value,
                'indicatorDate' => $this->liveSyncIndicator->indicator_date,
                'stage' => $this->liveSyncIndicator->stage,
                'facilityManifestId' => $this->liveSyncIndicator->facility_manifest_id,
            ]]
        ]);
        if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201) {
            $this->liveSyncIndicator->posted = true;
            $this->liveSyncIndicator->save();
        } else {
            Log::error(
                'PostLiveSyncIndicator: failed to post indicator ' .
                $this->liveSyncIndicator->name . ': ' .
                $this->liveSyncIndicator->facility->name
            );
        }
    }
}
