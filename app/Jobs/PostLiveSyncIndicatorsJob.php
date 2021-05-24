<?php

namespace App\Jobs;

use App\Jobs\PostLiveSyncIndicators;
use App\Models\Facility;
use App\Models\PostLiveSyncIndicatorsJob as PostLiveSyncIndicatorsJobModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PostLiveSyncIndicatorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 1800;

    protected $postLiveSyncIndicatorsJob;

    public function __construct(PostLiveSyncIndicatorsJobModel $postLiveSyncIndicatorsJob)
    {
        $this->postLiveSyncIndicatorsJob = $postLiveSyncIndicatorsJob;
    }

    public function handle()
    {
        $this->postLiveSyncIndicatorsJob->started_at = now();
        $this->postLiveSyncIndicatorsJob->save();
        Facility::where('etl', true)->chunk(100, function ($facilities) {
            $f = [];
            $facilities->each(function ($facility) use (&$f) {
                $f[$facility->code] = $facility->id;
            });
            PostLiveSyncIndicators::dispatch($f);
        });
        $this->postLiveSyncIndicatorsJob->completed_at = now();
        $this->postLiveSyncIndicatorsJob->save();
    }
}
