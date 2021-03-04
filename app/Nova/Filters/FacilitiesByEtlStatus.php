<?php

namespace App\Nova\Filters;

use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class FacilitiesByEtlStatus extends Filter
{
    public function apply(Request $request, $query, $value)
    {
        return $query->where('etl', $value);
    }

    public function options(Request $request)
    {
        return [
            'Activated' => 1,
            'Deactivated' => 0,
        ];
    }
}
