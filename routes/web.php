<?php

use App\Models\EtlJob;
use App\Models\FacilityMetric;
use App\Models\Facility;
use App\Models\FacilityPartner;
use App\Models\FacilityUpload;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Jobs\EtlJob as Etl;
use Illuminate\Console\Command;
use Carbon\Carbon;

Route::get('/test/{email}', function ($email) {

    $partners = Partner::get();
    $partnersNames = [];
    foreach ($partners as $partner) {
        $partnersNames[] = $partner->name;
    }
    //    config(['database.connections.sqlsrv.database' => 'DWHIdentity']);
    //    $_ = DB::connection('sqlsrv')->table('Organizations')
    //        ->selectRaw('*')
    //        ->whereIn('Code', $partnersNames)
    //        ->get();
    //
    //    Log::info(
    //        json_encode($_)
    //    );


    foreach ($partners as $partner) {

        $etlJob = EtlJob::max('id');
        $partner = Partner::where('id', 1)->first();
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

        $m = FacilityMetric::whereIn('facility_id', FacilityPartner::where('partner_id', 17)->pluck('facility_id'))
            ->where('etl_job_id', $etlJob)
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
                if (!empty($facs))
                    $facs = [];
                else
                    $facs[] = $metric->facility_id;

            } else if ($indicators[$metric->name] > 1 && !in_array($metric->facility_id, $facs)) {

                if ($metrics[$counter]->facility_id != $metric->facility_id) {
                    $metrics[$counter]->value += $metric->value;
                    $metrics[$counter]->dwh_value += $metric->dwh_value;
                    $facs[] = $metric->facility_id;
                    Log::info($facs);
                }
            }
        });



        $spoturl = nova_get_setting(nova_get_setting('production') ? 'spot_url' : 'spot_url_staging') . '/#/';
        $dwhurl = nova_get_setting('production') ? 'https://dwh.nascop.org/#/' : 'https://data.kenyahmis.org:9000/#/';

        config(['database.connections.sqlsrv.database' => 'All_Staging_2016_2']);
        $facility_partner = DB::connection('sqlsrv')->table('lkp_usgPartnerMenchanism')
            ->selectRaw('MFL_Code as code, FacilityName as name, County as county, Agency as agency, MechanismID as mechanism_id, Mechanism as partner, Implementing_Mechanism_Name as mechanism')
            ->whereNotNull('MFL_Code')
            ->where('MechanismID', '160327')
            ->whereIn('MFL_Code', Facility::where('etl', true)->pluck('code'))
            ->get();


        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://data.kenyahmis.org:8082/api/manifests/expected/ct?partner%5B%5D=Nyeri%20CHMT',
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
        $expected_ct = $expected_ct ? json_decode($expected_ct)->expected : 0;
        curl_close($curl);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://data.kenyahmis.org:8082/api/manifests/recency/ct?partner%5B%5D=Nyeri%20CHMT',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic amF5d2FkaTpGMSRoMW5nMTIz',
                'Cookie: _csrf=_zs6OKypL0oKzsZebfoXqhHq'
            ),
        ));
        $recency_ct = curl_exec($curl);
        $recency_ct = $recency_ct ? json_decode($recency_ct)->recency : 0;
        curl_close($curl);

        if ($expected_ct > 0)
            $ct_rr = ($recency_ct / $expected_ct) * 100;
        else
            $ct_rr = 0;


        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://data.kenyahmis.org:8082/api/manifests/expected/hts?partner%5B%5D=Nyeri%20CHMT',
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
        $expected_hts = $expected_hts ? json_decode($expected_hts)->expected :0;
        curl_close($curl);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://data.kenyahmis.org:8082/api/manifests/recency/hts?partner%5B%5D=Nyeri%20CHMT',
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
        $recency_hts = $recency_hts ? json_decode($recency_hts)->recency : 0;
        curl_close($curl);

        if ($expected_hts > 0)
            $hts_rr = ($recency_hts / $expected_hts) * 100;
        else
            $hts_rr = 0;

        $difference = DB::table('partner_metrics')
            ->selectRaw('sum(`value`) as `value`, SUM(dwh_value) as dwh_value')
            ->where('partner_id', 1)
            ->first();

        Mail::send('reports.partner.metricstest',
            [
                'name' => '',
                'contactPerson' => '',
                'unsubscribe_url' => '',
                'metrics' => $metrics,
                'spoturl' => $spoturl,
                'dwhurl' => $dwhurl,
                'facility_partner' => $facility_partner,
                'ct_rr' => $ct_rr,
                'hts_rr' => $hts_rr,
                'partner' => $partner,
                'difference' => $difference
            ],
            function ($message) use (&$email) {
                $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                $message->to($email)->subject('NDWH DQA Report');
            });
        return;
    }
});

Route::get('/email/covid', function () {
    config(['database.connections.sqlsrv.database' => 'PortalDev']);
    $table = DB::connection('sqlsrv')->table('PortalDev.dbo.FACT_COVID_STATS')
    ->selectRaw('*')->get();
    // Get previous Month and Year
    $currentDateTime = Carbon::now();
    $newDateTime = Carbon::now()->subMonth()->format('M_Y');

    $jsonDecoded = json_decode($table, true); 
    $fh = fopen('fileout_'.$newDateTime.'.csv', 'w');
    if (is_array($jsonDecoded)) {
        $counter = 0;
        foreach ($jsonDecoded as $line) {
            // sets the header row
            if($counter == 0){
                $header = array_keys($line);
                fputcsv($fh, $header);
            }
            $counter++;

            // sets the data row
            foreach ($line as $key => $value) {
                if ($value) {
                    $line[$key] = $value;
                }
            }
            // add each row to the csv file
            if (is_array($line)) {
                fputcsv($fh,$line);
            }
        }
    }
    fclose($fh);

    // Send the email
    Mail::send('reports.partner.covid',
        [],
        function ($message) use (&$fh) {
            // email configurations
            $message->from('dwh@mg.kenyahmis.org', 'NDWH');
            // email address of the recipients
            $message->to("npm1@cdc.gov")->subject('Covid Report');
            $message->cc(["mary.gikura@thepalladiumgroup.com", "kennedy.muthoka@thepalladiumgroup.com", "Evans.Munene@thepalladiumgroup.com"]);
            // attach the csv covid file
            $message->attach('fileout.csv');
        });

});

Route::get('/email/start', function () {
    ini_set('max_execution_time', -1);
    $etlJob = new EtlJob;
    $etlJob->save();

    EtlJob::whereNull('started_at')->where('job_date', '<=', now())->each(function ($etlJob) {
        Etl::dispatch($etlJob);
    });
    
    return;
});