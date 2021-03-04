<?php

use App\Models\EtlJob;
use App\Models\Facility;
use App\Models\FacilityMetric;
use Illuminate\Support\Facades\Route;

Route::get('/testing', function () {
    $etlJob = EtlJob::find(4);
    $facility = Facility::find(2);
    $metrics = FacilityMetric::where('facility_id', $facility->id)
        ->where('etl_job_id', $etlJob->id)
        ->whereNotNull('name')
        ->whereNotNull('value')
        ->whereNotNull('dwh_value')
        ->get();
    if (count($metrics) === 0) {
        return;
    }
    $descriptions = [
        "TX_CURR" => "Individuals currently receiving antiretroviral therapy (ART)",
        "TX_NEW" => "Individuals newly enrolled on antiretroviral therapy (ART)",
        "HTS_TESTED" => "Individuals who received a HIV test",
        "HTS_TESTED_POS" => "Individuals who tested positive during a HIV test",
        "HTS_LINKED" => "Individuals who tested positive and have been enrolled to care",
        "RETENTION_ON_ART_12_MONTHS" => "Individuals who are still alive and on ART 12 months after initiating treatment",
        "RETENTION_ON_ART_VL_1000_12_MONTHS" => "Individuals who are suppressed 12 months after initiating treatment",
        "MMD" => "Individuals dispensed drugs for  Multi month dispense (>= 90 days)",
        "HTS_INDEX" => "Individuals who were identified and tested using Index testing services and received their results",
        "TX_PVLS" => "Individuals of ART patients with a suppressed viral load within the past 12 months",
        "HTS_INDEX_POS" => "Individuals who tested positive using Index testing services and received their results",
        "TX_RTT" => "Patients who experienced interruption in treatment previously and restarted ARVs in this month",
        "TX_ML" => "Individuals who were on ART previously then had no clinical contact since their last expected contact",
    ];
    $url = nova_get_setting(nova_get_setting('production') ? 'spot_url' : 'spot_url_staging');
    return view('reports.facilities.metrics', compact('facility', 'metrics', 'descriptions', 'url'));
});
