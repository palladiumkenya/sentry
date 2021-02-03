<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostLiveSyncIndicatorsJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'started_at',
        'completed_at',
        'created_by'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(ProcessedUser::class, 'created_by');
    }
}
