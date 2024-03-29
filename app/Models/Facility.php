<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facility extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'uid',
        'county',
        'sub_county',
        'ward',
        'constituency',
        'source',
        'etl',
        'processed',
        'posted'
    ];

    public function facilityMetrics()
    {
        return $this->hasMany(FacilityMetric::class);
    }

    public function facilityUploads()
    {
        return $this->hasMany(FacilityUpload::class);
    }

    public function liveSyncIndicators()
    {
        return $this->hasMany(LiveSyncIndicator::class);
    }

    public function etlJobs()
    {
        return $this->belongsToMany(EtlJob::class)
            ->using(EtlJobFacility::class)
            ->withPivot([
                'created_at',
                'updated_at',
            ]);
    }

    public function partners()
    {
        return $this->belongsToMany(Partner::class)
            ->using(FacilityPartner::class)
            ->withPivot([
                'docket',
                'created_by',
                'created_at',
                'updated_at',
            ]);
    }
}
