<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtlJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_date',
        'started_at',
        'completed_at',
        'created_by'
    ];

    protected $casts = [
        'job_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class)
            ->using(EtlJobFacility::class)
            ->withPivot([
                'created_at',
                'updated_at',
            ]);
    }
}
