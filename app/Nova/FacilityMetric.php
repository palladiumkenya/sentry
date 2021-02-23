<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Text;

class FacilityMetric extends Resource
{
    public static $model = \App\Models\FacilityMetric::class;

    public static $group = 'Data';

    public static $title = 'name';

    public static $search = ['id', 'uid', 'name'];

    public static $displayInNavigation = true;

    public static $perPageViaRelationship = 30;

    public static function label()
    {
        return __('Facility Metrics');
    }

    public function fields(Request $request)
    {
        return [
            ID::make('ID',  'id')->sortable(),
            BelongsTo::make('Facility', 'facility', Facility::class)->sortable(),
            Text::make('Name',  'name')->sortable(),
            Text::make('EMR Value',  'value')->sortable(),
            DateTime::make('EMR Date',  'metric_date')->sortable(),
            Text::make('DWH Value',  'dwh_value')->sortable(),
            DateTime::make('DWH Date',  'dwh_metric_date')->sortable(),
        ];
    }

    public function cards(Request $request)
    {
        return [];
    }

    public function filters(Request $request)
    {
        return [];
    }

    public function lenses(Request $request)
    {
        return [];
    }

    public function actions(Request $request)
    {
        return [];
    }
}
