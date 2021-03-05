<?php

namespace App\Nova\Filters;

use App\Models\LiveSyncIndicator;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class LiveSyncIndicatorsByName extends Filter
{
    public $name = 'Indicator';

    public function apply(Request $request, $query, $value)
    {
        return $query->where('name', $value);
    }

    public function options(Request $request)
    {
        $options = LiveSyncIndicator::distinct('name')->orderBy('name')->pluck('name')->toArray();
        return array_combine($options, $options);
    }
}
