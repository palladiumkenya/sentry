<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Text;

class LiveSyncIndicator extends Resource
{
    public static $model = \App\Models\LiveSyncIndicator::class;

    public static $group = 'Data';

    public static $title = 'name';

    public static $search = [
        'name', 'facility_code', 'facility_name'
    ];

    public static function label() {
        return 'Indicators';
    }

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('Name')->sortable()->rules('required', 'max:255'),
            Text::make('Value')->sortable()->rules('required', 'max:255'),
            Text::make('Facility Code')->sortable()->rules('required', 'max:255'),
            Text::make('Facility Name')->sortable()->rules('required', 'max:255'),
            DateTime::make('Indicator Date')->sortable()->rules('required'),
            Text::make('Stage')->sortable()->rules('required', 'max:255'),
            Boolean::make('Processed', 'posted')->sortable()->rules('required'),
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
