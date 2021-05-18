<?php

namespace App\Jobs;

use App\Models\EtlJob;
use App\Models\Facility;
use App\Models\FacilityMetric;
use App\Models\FacilityUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Browsershot\Browsershot;

class GenerateFacilityMetricsReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    protected $etlJob;
    protected $facility;

    public function __construct(EtlJob $etlJob, Facility $facility)
    {
        $this->etlJob = $etlJob;
        $this->facility = $facility;
    }

    public function handle()
    {
        $etlJob = $this->etlJob;
        $facility = $this->facility;
        $indicators = [
            'HTS_TESTED' => 0,
            'HTS_TESTED_POS' => 0,
            'HTS_INDEX_POS' => 0,
            'TX_NEW' => 0,
            'TX_CURR' => 0,
            'RETENTION_ON_ART_12_MONTHS' => 0,
            'RETENTION_ON_ART_VL_1000_12_MONTHS' => 0
        ];
        $metrics = (new FacilityMetric)->newCollection();
        $m = FacilityMetric::where('facility_id', $facility->id)
            ->where('etl_job_id', $etlJob->id)
            ->whereNotNull('name')
            ->whereNotNull('value')
            ->whereNotNull('dwh_value')
            ->whereIn('name', array_keys($indicators))
            ->orderByRaw("FIELD(name,
                'HTS_TESTED',
                'HTS_TESTED_POS',
                'HTS_INDEX_POS',
                'TX_NEW',
                'TX_CURR',
                'RETENTION_ON_ART_12_MONTHS',
                'RETENTION_ON_ART_VL_1000_12_MONTHS'
            ) ASC, metric_date DESC")
            ->get();

        $m->each(function ($metric) use (&$indicators, &$metrics) {
            $indicators[$metric->name] = $indicators[$metric->name] + 1;
            if ($indicators[$metric->name] == 1) {
                $metrics->push($metric);
            }
        });

        $uploads = FacilityUpload::where('facility_id', $facility->id)
            ->whereNotNull('docket')
            ->whereNotNull('received')
            ->whereNotNull('updated')
            ->orderBy('updated')
            ->get();
        $minDate = now();
        $maxDate = now();
        $dockets = ['NDWH', 'HTS', 'MPI'];
        $months = [];
        foreach ($uploads as $upload)
        {
            if ($upload->updated->lessThan($minDate)) {
                $minDate = $upload->updated;
            }
        }
        $runningDate = $minDate;
        while ($runningDate < $maxDate)
        {
            if (!in_array($runningDate->format('M Y'), $months)) {
                $months[] = $runningDate->format('M Y');
            }
            $runningDate = $runningDate->addMonths(1);
        }
        $data = [];
        foreach ($dockets as $docket) {
            $data[$docket] = [];
            foreach ($months as $month) {
                $data[$docket][$month] = 0;
            }
        }
        foreach ($uploads as $upload)
        {
            $month = $upload->updated->format('M Y');
            $docket = $upload->docket;
            $data[$docket][$month] = intval($upload->received);
        }
        foreach ($dockets as $docket)
        {
            $data[$docket] = array_values($data[$docket]);
        }
        $path = storage_path('app/reports/etls/'.$etlJob->id.'_'.$facility->id.'.pdf');
        if (file_exists($path)) {
            unlink($path);
        }
        $url = nova_get_setting(nova_get_setting('production') ? 'spot_url' : 'spot_url_staging').'/#/stats/showcase/'.$facility->uid;
        $descriptions = [
            "HTS_TESTED" => "Individuals who received a HIV test",
            "HTS_TESTED_POS" => "Individuals who tested positive during a HIV test",
            "HTS_LINKED" => "Individuals who tested positive and have been enrolled to care",
            "HTS_INDEX" => "Individuals who were identified and tested using Index testing services and received their results",
            "HTS_INDEX_POS" => "Individuals who tested positive using Index testing services and received their results",
            "TX_NEW" => "Individuals newly enrolled on antiretroviral therapy (ART)",
            "TX_CURR" => "Individuals currently receiving antiretroviral therapy (ART)",
            "RETENTION_ON_ART_12_MONTHS" => "Individuals who are still alive and on ART 12 months after initiating treatment",
            "RETENTION_ON_ART_VL_1000_12_MONTHS" => "Individuals who are suppressed 12 months after initiating treatment",
            "MMD" => "Individuals dispensed drugs for  Multi month dispense (>= 90 days)",
            "TX_PVLS" => "Individuals of ART patients with a suppressed viral load within the past 12 months",
            "TX_RTT" => "Patients who experienced interruption in treatment previously and restarted ARVs in this month",
            "TX_ML" => "Individuals who were on ART previously then had no clinical contact since their last expected contact",
        ];
        $view = view('reports.facilities.metrics', compact(
            'facility', 'metrics', 'descriptions', 'url', 'data', 'months', 'docket'
        ));
        Browsershot::html($view)->save($path);
    }
}
