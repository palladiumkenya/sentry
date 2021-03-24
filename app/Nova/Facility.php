<?php

namespace App\Nova;

use App\Nova\Filters\FacilitiesByEtlStatus;
use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Text;

class Facility extends Resource
{
    public static $model = \App\Models\Facility::class;

    public static $group = 'Data';

    public static $title = 'name';

    public static $search = ['id', 'name', 'code', 'uid', 'county', 'source'];

    public static $displayInNavigation = true;

    public static $perPageViaRelationship = 10;

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
            Text::make('Sub-County',  'sub_county')->sortable(),
            Text::make('Ward',  'ward')->sortable(),
            Text::make('Constituency',  'constituency')->sortable(),
            Text::make('Source',  'Source')->onlyOnDetail(),
            Boolean::make('ETL',  'etl')->sortable(),
            BelongsToMany::make('Partners', 'partners', Partner::class),
            HasMany::make('Metrics', 'facilityMetrics', FacilityMetric::class),
            HasMany::make('Uploads', 'facilityUploads', FacilityUpload::class),
            HasMany::make('Indicators', 'liveSyncIndicators', LiveSyncIndicator::class),
            BelongsToMany::make('ETL Jobs', 'etlJobs', EtlJob::class),
            DateTime::make('Created',  'created_at')->onlyOnDetail(),
            DateTime::make('Updated',  'updated_at')->onlyOnDetail(),
        ];
    }

    public function cards(Request $request)
    {
        return [];
    }

    public function filters(Request $request)
    {
        return [
            new FacilitiesByEtlStatus()
        ];
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
