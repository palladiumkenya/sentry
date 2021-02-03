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
        'facility_code',
        'facility_name',
        'indicator_date',
        'indicator_id',
        'stage',
        'facility_manifest_id',
        'posted'
    ];

    protected $casts = [
        'indicator_date' => 'datetime',
    ];
}
