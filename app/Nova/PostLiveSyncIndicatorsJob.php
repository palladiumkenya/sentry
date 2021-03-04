<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;

class PostLiveSyncIndicatorsJob extends Resource
{
    public static $model = \App\Models\PostLiveSyncIndicatorsJob::class;

    public static $group = 'Other';

    public static $title = 'started_at';

    public static $search = ['id', 'started_at', 'completed_at'];

    public static $displayInNavigation = true;

    public static $perPageViaRelationship = 10;

    public static function label()
    {
        return __('Indicator Post Jobs');
    }

    public function fields(Request $request)
    {
        return [
            ID::make('ID',  'id')->sortable(),
            DateTime::make('Started',  'started_at')->sortable()->exceptOnForms(),
            DateTime::make('Completed',  'completed_at')->sortable()->exceptOnForms(),
            // BelongsTo::make('Initiated By', 'createdBy', User::class)->searchable()->sortable()->exceptOnForms(),
            DateTime::make('Created At',  'created_at')->sortable()->exceptOnForms(),
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
