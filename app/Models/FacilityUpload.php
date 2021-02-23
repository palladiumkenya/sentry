<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacilityUpload extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['*'];

    protected $casts = [
        'updated' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id');
    }
}
