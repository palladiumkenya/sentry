<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerMetric extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'facility_id',
        'uid',
        'create_date',
        'name',
        'value',
        'metric_date',
        'manifest_id',
        'dwh_value',
        'dwh_metric_date',
        'processed',
        'posted',
        'etl_job_id',
    ];

    protected $casts = [
        'create_date' => 'datetime',
        'metric_date' => 'datetime',
        'dwh_metric_date' => 'datetime',
    ];

    public function etlJob()
    {
        return $this->belongsTo(EtlJob::class, 'etl_job_id');
    }
}
