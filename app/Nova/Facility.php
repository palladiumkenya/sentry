<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Text;

class Facility extends Resource
{
    public static $model = \App\Models\Facility::class;

    public static $group = 'Data';

    public static $title = 'name';

    public static $search = ['id', 'name', 'code', 'uid', 'county', 'partner', 'source'];

    public static $displayInNavigation = true;

    public static $perPageViaRelationship = 30;

    public static function label()
    {
        return __('Facilities');
    }

    public function fields(Request $request)
    {
        return [
            ID::make('ID',  'id')->sortable(),
            Text::make('Name',  'name')->sortable(),
            Text::make('Code',  'code')->sortable(),
            Text::make('GUID',  'uid')->sortable()->onlyOnDetail(),
            Text::make('County',  'county')->sortable(),
            Text::make('Partner',  'partner')->sortable(),
            Boolean::make('ETL',  'etl')->sortable(),
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
        return [
            new Actions\ToggleEtlStatus,
        ];
    }
}
