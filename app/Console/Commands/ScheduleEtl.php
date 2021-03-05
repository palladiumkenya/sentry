<?php

namespace App\Console\Commands;

use App\Jobs\EtlJob;
use App\Models\EtlJob as EtlJobModel;
use Illuminate\Console\Command;

class ScheduleEtl extends Command
{
    protected $signature = 'sentry:schedule-etl';

    protected $description = 'Schedule ETL';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        EtlJobModel::whereNull('started_at')->where('job_date', '<=', now())->each(function ($etlJob) {
            EtlJob::dispatch($etlJob);
        });
    }
}
