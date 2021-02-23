<?php

namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;

class FacilityUpload extends Resource
{
    public static $model = \App\Models\FacilityUpload::class;

    public static $group = 'Data';

    public static $title = 'uid';

    public static $search = ['id', 'uid', 'partner', 'docket'];

    public static $displayInNavigation = true;

    public static $perPageViaRelationship = 30;

    public static function label()
    {
        return __('Facility Uploads');
    }

    public function fields(Request $request)
    {
        return [
            ID::make('ID',  'id')->sortable(),
            BelongsTo::make('Facility', 'facility', Facility::class)->sortable(),
            Text::make('Partner',  'partner')->sortable(),
            Text::make('Docket',  'docket')->sortable(),
            Number::make('Expected',  'expected')->sortable(),
            Number::make('Received',  'received')->sortable(),
            Text::make('Status',  'status')->sortable(),
            DateTime::make('Updated',  'updated')->sortable(),
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
