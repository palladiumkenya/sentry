<?php

namespace App\Console\Commands;

use App\Models\EtlJob;
use Illuminate\Console\Command;

class Etl extends Command
{
    protected $signature = 'sentry:etl';

    protected $description = 'ETL';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $etlJob = new EtlJob;
        $etlJob->save();
    }
}
