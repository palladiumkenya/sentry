<?php

use App\Models\FacilityMetric;
use Illuminate\Support\Facades\Route;

Route::get('/testing', function () {
    $etlJob = $this->etlJob;
    $facility = $this->facility;
    $metrics = FacilityMetric::where('facility_id', $facility->id)
        ->where('etl_job_id', $etlJob->id)
        ->whereNotNull('name')
        ->whereNotNull('value')
        ->whereNotNull('dwh_value')
        ->get();
    if (count($metrics) === 0) {
        return;
    }
    return view('reports.facilities.metrics', compact('facility', 'metrics'));
});
