<?php

namespace App\Jobs;

use App\Models\Facility;
use Illuminate\Bus\Queueable;
use App\Models\FacilityMetric;
use Spatie\Browsershot\Browsershot;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class GenerateFacilityMetricsReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        $facility = Facility::whereIn('code', ['13548'])->first();
        if (!$facility) {
            return;
        }
        $metrics = FacilityMetric::where('facility_id', $facility->id)
            ->whereNotNull('name')
            ->whereNotNull('value')
            ->whereNotNull('dwh_value')
            ->get();
        $view = view('reports.facilities.metrics', compact('facility', 'metrics'));
        $path = storage_path('app/reports/palladium_ndwh_dqa.pdf');
        if (file_exists($path)) {
            unlink($path);
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
            "TX_RTT" => "Patients who experienced interruption in treatment previously and restarted ARVs in this month"
        ];
        SnappyPdf::loadView('reports.facilities.metrics', compact('facility', 'metrics', 'descriptions'))->save($path);
        // Browsershot::html($view)->landscape(true)->showBackground()->save($path);
    }
}

