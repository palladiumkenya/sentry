<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacilityUpload extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'facility_id',
        'uid',
        'docket',
        'expected',
        'received',
        'status',
        'updated',
        'processed',
        'posted',
        'etl_job_id',
        'partner_id',
    ];

    protected $casts = [
        'updated' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id');
    }

    public function etlJob()
    {
        return $this->belongsTo(EtlJob::class, 'etl_job_id');
    }
}
