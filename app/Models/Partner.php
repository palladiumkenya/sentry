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

    public function getCleanNameAttribute()
    {
        $name = str_replace(' ', '_', $this->attributes['name']);
        return preg_replace('/[^A-Za-z0-9\-]/', '_', $name);
    }

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
