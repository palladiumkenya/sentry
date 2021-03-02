<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacilityPartner extends Pivot
{
    use HasFactory, SoftDeletes;

    protected $table = 'facility_partner';

    protected $fillable = [
        'facility_id',
        'partner_id',
        'docket',
        'created_by',
    ];
}
