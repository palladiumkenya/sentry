<?php

namespace App\Nova\Filters;

use App\Models\Facility;
use App\Models\LiveSyncIndicator;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class LiveSyncIndicatorsByFacility extends Filter
{
    public $name = 'Facility';

    public function apply(Request $request, $query, $value)
    {
        return $query->where('facility_id', $value);
    }

    public function options(Request $request)
    {
        return Facility::whereIn('id', LiveSyncIndicator::pluck('facility_id')->toArray())
            ->orderBy('name')
            ->pluck('id', 'name');
    }
}
