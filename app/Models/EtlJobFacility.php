<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class EtlJobFacility extends Pivot
{
    use HasFactory, SoftDeletes;

    protected $table = 'etl_job_facility';

    protected $fillable = [
        'etl_job_id',
        'facility_id',
        'created_at',
        'updated_at',
    ];
}
