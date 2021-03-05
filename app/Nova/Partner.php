<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;

class Partner extends Resource
{
    public static $model = \App\Models\Partner::class;

    public static $group = 'Data';

    public static $title = 'name';

    public static $search = ['id', 'name', 'mechanism', 'agency', 'project', 'code', 'uid'];

    public static $displayInNavigation = true;

    public static $perPageViaRelationship = 10;

    public static function label()
    {
        return __('Partners');
    }

    public function fields(Request $request)
    {
        return [
            ID::make('ID',  'id')->sortable(),
            Text::make('Name',  'name')->sortable()->required(),
            Text::make('Mechanism',  'mechanism')->sortable(),
            Text::make('Agency',  'agency')->sortable(),
            Text::make('Project',  'project')->sortable(),
            Text::make('Code',  'code')->sortable(),
            Text::make('UID',  'uid')->sortable(),
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
