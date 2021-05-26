<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class PostDatabaseData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600;

    protected $data;
    protected $unique;

    public function __construct($data, $unique = [])
    {
        $this->data = $data;
        $this->unique = $unique;
    }

    public function handle()
    {
        if (count($this->data) === 0) {
            return;
        }
        config(['database.connections.pgsql.database' => 'portal']);
        $database = DB::connection('pgsql')->table('fact_trans_hts');

        if (count($this->unique)) {
            $payload = [];
            $where = [];
            foreach($this->data as $key => $value) {
                if (in_array($key, $this->unique)) {
                    $where[$key] = $value;
                } else {
                    $payload[$key] = $value;
                }
            }
            $database->updateOrInsert($payload, $where);
        } else {
            $database->insert($this->data);
        }
    }
}
