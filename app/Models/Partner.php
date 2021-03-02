<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'mechanism',
        'agency',
        'project',
        'code',
        'uid',
        'created_by'
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class)
            ->using(FacilityPartner::class)
            ->withPivot([
                'docket',
                'created_by',
                'created_at',
                'updated_at',
            ]);
    }
}
