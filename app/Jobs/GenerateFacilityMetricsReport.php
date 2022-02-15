<?php

namespace App\Jobs;

use App\Models\EtlJob;
use App\Models\Facility;
use App\Models\FacilityMetric;
use App\Models\FacilityPartner;
use App\Models\FacilityUpload;
use App\Models\Partner;
use App\Models\PartnerMetric;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class GenerateFacilityMetricsReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600;

    protected $etlJob;
    protected $partner;

    public function __construct(EtlJob $etlJob, Partner $partner)
    {
        $this->etlJob = $etlJob;
        $this->partner = $partner;
    }

    public function handle()
    {
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

        $m = FacilityMetric::whereIn('facility_id', FacilityPartner::where('partner_id', $this->partner->id)->pluck('facility_id'))
            ->where('etl_job_id', $this->etlJob->id)
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
        $counter = -1;
        $facs = [];

        $m->each(function ($metric) use (&$indicators, &$metrics, &$counter, &$facs) {
            $indicators[$metric->name] = $indicators[$metric->name] + 1;

            if ($indicators[$metric->name] == 1) {
                $metrics->push($metric);
                $counter++;
                $partner_metric = new PartnerMetric();
                $partner_metric->name = $metric->name;
                $partner_metric->partner_id = $this->partner->id;
                $partner_metric->etl_job_id = $metric->etl_job_id;
                $partner_metric->value = $metric->value;
                $partner_metric->metric_date = $metric->metric_date;
                $partner_metric->dwh_value = $metric->dwh_value;
                $partner_metric->dwh_metric_date = $metric->dwh_metric_date;
                $partner_metric->save();
                if (!empty($facs))
                    $facs = [];
                else
                    $facs[] = $metric->facility_id;

            } else if ($indicators[$metric->name] > 1 && !in_array($metric->facility_id, $facs)) {
                if ($metrics[$counter]->facility_id != $metric->facility_id) {
                    $metrics[$counter]->value += $metric->value;
                    $metrics[$counter]->dwh_value += $metric->dwh_value;
                    $facs[] = $metric->facility_id;
                    $partner_metric = new PartnerMetric();
                    $partner_metric->name = $metric->name;
                    $partner_metric->partner_id = $this->partner->id;
                    $partner_metric->etl_job_id = $metric->etl_job_id;
                    $partner_metric->value = $metric->value;
                    $partner_metric->metric_date = $metric->metric_date;
                    $partner_metric->dwh_value = $metric->dwh_value;
                    $partner_metric->dwh_metric_date = $metric->dwh_metric_date;
                    $partner_metric->save();
                }
            }
            Log::info($facs);
        });


        $spoturl = nova_get_setting(nova_get_setting('production') ? 'spot_url' : 'spot_url_staging') . '/#/';
        $dwhurl = nova_get_setting('production') ? 'https://dwh.nascop.org/#/' : 'https://data.kenyahmis.org:9000/#/';

//        config(['database.connections.sqlsrv.database' => 'All_Staging_2016_2']);
        $facility_partner = DB::connection('sqlsrv')->table('All_Staging_2016_2.dbo.lkp_usgPartnerMenchanism')
            ->selectRaw('MFL_Code as code, FacilityName as name, County as county, Agency as agency, MechanismID as mechanism_id, Mechanism as partner, Implementing_Mechanism_Name as mechanism')
            ->whereNotNull('MFL_Code')
            ->where('MechanismID', $this->partner->code)
            ->whereIn('MFL_Code', Facility::where('etl', true)->pluck('code'))
            ->get();


        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://dwh.nascop.org/api/manifests/expected/ct?partner%5B%5D=' . str_replace(' ', '%20', $this->partner->name),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));
        $expected_ct = curl_exec($curl);
        $expected_ct = json_decode($expected_ct)->expected;
        curl_close($curl);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://dwh.nascop.org/api/manifests/recency/ct?partner%5B%5D=' . str_replace(' ', '%20', $this->partner->name),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));
        $recency_ct = curl_exec($curl);
        $recency_ct = json_decode($recency_ct)->recency;
        curl_close($curl);

        if ($expected_ct > 0)
            $ct_rr = round(($recency_ct / $expected_ct) * 100, 2);
        else
            $ct_rr = 0;


        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://dwh.nascop.org/api/manifests/expected/hts?partner%5B%5D=Nyeri%20CHMT',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));
        $expected_hts = curl_exec($curl);
        $expected_hts = json_decode($expected_hts)->expected;
        curl_close($curl);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://dwh.nascop.org/api/manifests/recency/hts?partner%5B%5D=Nyeri%20CHMT',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));
        $recency_hts = curl_exec($curl);
        $recency_hts = json_decode($recency_hts)->recency;
        curl_close($curl);

        if ($expected_hts > 0)
            $hts_rr = round(($recency_hts / $expected_hts) * 100, 2);
        else
            $hts_rr = 0;
        if (!empty($metrics)) {
            $difference = DB::table('partner_metrics')
                ->selectRaw('sum(`value`) as `value`, SUM(dwh_value) as dwh_value')
                ->where('partner_id', $this->partner->id)
                ->get();
            $metrics = DB::table('partner_metrics')
                ->selectRaw('sum(`value`) as `value`, SUM(dwh_value) as dwh_value, `name`, metric_date, dwh_metric_date')
                ->where('partner_id', $this->partner->id)
                ->groupByRaw('partner_id, name')
                ->get();

            Log::info($metrics);

            EmailJob::dispatchNow(
                $metrics, $spoturl, $dwhurl, $facility_partner, $ct_rr, $hts_rr, $this->partner, $difference
            );
        }

    }

}
