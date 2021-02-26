<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;

class EtlJob extends Resource
{
    public static $model = \App\Models\EtlJob::class;

    public static $group = 'Other';

    public static $title = 'started_at';

    public static $search = ['id', 'started_at', 'completed_at'];

    public static $displayInNavigation = true;

    public static $perPageViaRelationship = 30;

    public static function label()
    {
        return __('ETL Jobs');
    }

    public function fields(Request $request)
    {
        return [
            ID::make('ID',  'id')->sortable(),
            DateTime::make('Job Date',  'job_date')->sortable()->required(),
            DateTime::make('Started',  'started_at')->sortable()->exceptOnForms(),
            DateTime::make('Completed',  'completed_at')->sortable()->exceptOnForms(),
            // BelongsTo::make('Initiated By', 'createdBy', User::class)->searchable()->sortable()->exceptOnForms(),
            DateTime::make('Created At',  'created_at')->sortable()->exceptOnForms(),
            BelongsToMany::make('Facilities', 'facilities', Facility::class),
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
