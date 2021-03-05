<?php

namespace App\Nova;

use App\Nova\Actions\Repost;
use App\Nova\Filters\LiveSyncIndicatorsByFacility;
use App\Nova\Filters\LiveSyncIndicatorsByName;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
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
        'name'
    ];

    public static $displayInNavigation = true;

    public static $perPageViaRelationship = 10;

    public static function label() {
        return 'Indicators';
    }

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Facility', 'facility', Facility::class)->rules('required'),
            Text::make('Name')->sortable()->rules('required', 'max:255'),
            Text::make('Value')->sortable()->rules('required', 'max:255'),
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
        return [
            new LiveSyncIndicatorsByName(),
            new LiveSyncIndicatorsByFacility(),
        ];
    }

    public function lenses(Request $request)
    {
        return [];
    }

    public function actions(Request $request)
    {
        return [
            new Repost(),
        ];
    }
}
