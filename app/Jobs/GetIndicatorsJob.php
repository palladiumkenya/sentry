<?php

namespace App\Jobs;

use App\Jobs\GetIndicatorValues;
use App\Models\GetIndicatorsJob as GetIndicatorsJobModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GetIndicatorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $getIndicatorsJob;

    public function __construct(GetIndicatorsJobModel $getIndicatorsJob)
    {
        $this->getIndicatorsJob = $getIndicatorsJob;
    }

    public function handle()
    {
        $this->getIndicatorsJob->started_at = now();
        $this->getIndicatorsJob->save();
        GetIndicatorValues::dispatchNow();
        $this->getIndicatorsJob->completed_at = now();
        $this->getIndicatorsJob->save();
    }
}
