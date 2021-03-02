<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveSyncIndicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'facility_id',
        'indicator_date',
        'indicator_id',
        'stage',
        'facility_manifest_id',
        'processed',
        'posted'
    ];

    protected $casts = [
        'indicator_date' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id');
    }
}
