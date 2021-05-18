<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ReclaimSpace implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    protected $databaseName;
    protected $fileName;

    public function __construct($databaseName, $fileName)
    {
        $this->databaseName = $databaseName;
        $this->fileName = $fileName;
    }

    public function handle()
    {
        config(['database.connections.sqlsrv.database' => $this->databaseName]);
        DB::connection('sqlsrv')->statement("DBCC SHRINKFILE (N'".$this->fileName."' , 0, TRUNCATEONLY)");
    }
}
