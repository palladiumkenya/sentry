<?php

use App\Http\Controllers\MainController;
use App\Http\Controllers\EmailController;
use App\Models\EtlJob;
use App\Models\EmailContacts;
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
use App\Jobs\GetIndicatorValues;
use App\Jobs\PostLiveSyncIndicators;
use App\Jobs\GetSpotFacilityUploads;
use App\Jobs\GetSpotFacilityMetrics;
use App\Jobs\GetSpotFacilities;
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
    $reportingMonth = Carbon::now()->subMonth()->format('M_Y');

    $jsonDecoded = json_decode($table, true); 
    $fh = fopen('fileout_Covid_'.$reportingMonth.'.csv', 'w');
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
    $emails = EmailContacts::where('is_main', 1 )->where('list_subscribed', 'Covid')->pluck('email')->toArray(); 
    $emails_cc = EmailContacts::where('is_cc', 1 )->where('list_subscribed', 'Covid')->pluck('email')->toArray(); 

    // Send the email
    Mail::send('reports.partner.covid',
        [],
        function ($message) use (&$fh, &$reportingMonth, &$emails, &$emails_cc) {
            // email configurations
            $message->from('dwh@mg.kenyahmis.org', 'NDWH');
            // email address of the recipients
            $message->to($emails)->subject('Covid Report');
            $message->cc([$emails_cc]); 
            $message->attach('fileout_Covid_'.$reportingMonth.'.csv');
        });

});

Route::get('/email/comparison_txcurr', function () {
    $ct_expected_partner = "select sum(expected) as totalexpected from portaldev.expected_uploads where docket='CT'  COLLATE utf8mb4_general_ci ";
    $ct_recency_partner = "select sum(recency) as totalrecency from portaldev.recency_uploads where docket='CT' COLLATE utf8mb4_general_ci and year=".Carbon::now()->subMonth()->format('Y')." and month=".Carbon::now()->subMonth()->format('m');
    
    $hts_expected_partner = "select sum(expected) as totalexpected from portaldev.expected_uploads where docket='HTS' COLLATE utf8mb4_general_ci";
    $hts_recency_partner = "select sum(recency) as totalrecency from portaldev.recency_uploads where docket='HTS' COLLATE utf8mb4_general_ci and year=".Carbon::now()->subMonth()->format('Y')." and month=".Carbon::now()->subMonth()->format('m');
    
    config(['database.connections.mysql.database' => 'portaldev']);
    $ct_expected = DB::connection('mysql')->select(DB::raw($ct_expected_partner))[0];
    $ct_recency = DB::connection('mysql')->select(DB::raw($ct_recency_partner))[0];
    $hts_expected = DB::connection('mysql')->select(DB::raw($hts_expected_partner))[0];
    $hts_recency = DB::connection('mysql')->select(DB::raw($hts_recency_partner))[0];

    $ct_per = $ct_recency->totalrecency * 100 / $ct_expected->totalexpected ;
    $hts_per = $hts_recency->totalrecency *100 / $hts_expected->totalexpected;
    
    $stale_query= "with clean_data as (
                select 
                    distinct FacilityCode,
                    FacilityName,
                    visits,
                    DateUploaded,
                    DateQueryExecuted
                from [DWHData Analytics].dbo.AllStaging_StgNumbers
                where 
                    FacilityCode is not null
                    and DateUploaded is not null
            ),
            visits_per_facility_per_upload_date as (
            /* get the latest facility and dateUploaded entry for each month */
                select 
                    FacilityCode,
                    FacilityName,
                    visits,
                    DateUploaded,
                    DateQueryExecuted,
                    row_number() over (partition by FacilityCode, DateUploaded order by DateQueryExecuted desc) as rk
                from clean_data
            ),
            facility_monthly_avg_visits as (
                select
                    FacilityCode,
                    FacilityName,
                    avg(visits) as avg_visits
                from visits_per_facility_per_upload_date
                where DateUploaded < dateadd(day, 1, eomonth(getdate(), -1)) /* ommit uploads done on the current month from avg computation */
                    and rk = 1
                group by 
                    FacilityCode,
                    FacilityName
            ),
            previous_month_facility_visits as (
                select 
                    FacilityCode,
                    FacilityName,
                    visits as no_of_visits,
                    DateUploaded,
                    DateQueryExecuted,
                    row_number() over (partition by FacilityCode order by DateQueryExecuted desc) as rk
                from clean_data
            where DateUploaded > dateadd(day, 1, eomonth(getdate(), -1))  /* get only uploads from the beginning of current month */
            ),
            summary as (
                select 
                    facility_monthly_avg_visits.FacilityCode,
                    facility_monthly_avg_visits.FacilityName,
                    rk,
                    facility_monthly_avg_visits.avg_visits,
                    previous_month_facility_visits.no_of_visits,
                    DateUploaded,
                    DateQueryExecuted,
                    round(cast(previous_month_facility_visits.no_of_visits as float)/cast(facility_monthly_avg_visits.avg_visits as float), 3) as proportion
                from facility_monthly_avg_visits
                left join previous_month_facility_visits ON previous_month_facility_visits.FacilityCode = facility_monthly_avg_visits.FacilityCode and rk = 1
            )
            select 
                FacilityCode,
                FacilityName,
                SDP,
                County,
                avg_visits,
                no_of_visits as current_no_of_visits,
                DateUploaded,
                DateQueryExecuted,
                proportion * 100 as percentage_of_avg_visits
            from summary
            left join HIS_Implementation.dbo.ALL_EMRSites EMRSites on EMRSites.MFL_Code=summary.FacilityCode
            where proportion < 0.5 ";
        
    $stale = DB::connection('sqlsrv')->select(DB::raw($stale_query));
    // $stale = [];

    $reportingMonth = Carbon::now()->subMonth()->format('M_Y');
    $jsonDecoded = json_decode(json_encode($stale), true); 
    $fh = fopen('fileout_StaleDBs_'.$reportingMonth.'.csv', 'w');
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
    
    $incomplete_up_query = "With Uploads as (
        Select  [DateRecieved],ROW_NUMBER()OVER(Partition by Sitecode Order by [DateRecieved] Desc) as Num ,
            SiteCode,
            cast( [DateRecieved]as date) As DateReceived,
            Emr,
            Name,
            Start,
            PatientCount
        from DWAPICentral.dbo.FacilityManifest 
        where cast  (DateRecieved as date)> DATEADD(MONTH, DATEDIFF(MONTH, 0, GETDATE())-1, 0) --First day of previous month
        and cast (DateRecieved as date) <= DATEADD(MONTH, DATEDIFF(MONTH, -1, GETDATE())-1, -1) --Last Day of previous month

        ),
        LatestUploads AS (
        Select 
            SiteCode,
            cast( [DateRecieved]as date) As DateReceived,
            Emr,
            Name,
            Start,
            PatientCount
        from Uploads
        where Num=1
        ),

        Received As (
        Select distinct 
            Fac.Code,
            fac.Name,
        Count (*) As Received
        FROM [DWAPICentral].[dbo].[PatientExtract](NoLock) Patient
        INNER JOIN [DWAPICentral].[dbo].[Facility](NoLock) Fac ON Patient.[FacilityId] = Fac.Id AND Fac.Voided=0 and Fac.Code>0
        group by 
            Fac.Code,
            fac.Name
            ),
        Facilities AS (
        Select distinct
            MFLCode,
            FacilityName,
            CTPartner,
            CTAgency
        from PortalDev.dbo.Fact_Trans_New_Cohort
        ),

        Combined AS (
        Select distinct
            MFLCode,
            FacilityName,
            CTPartner,
            CTAgency,
            LatestUploads.DateReceived,
            LatestUploads.PatientCount As ExpectedPatients
            from Facilities
            left join LatestUploads on Facilities.MFLCode=LatestUploads.SiteCode
            
        )
        Select 
            MFLCode,
            FacilityName,
            CTPartner,
            CTAgency,
            DateReceived,
            ExpectedPatients,
            Received.Received
            from Combined
            left join Received on Combined.MFLCode=Received.Code
            where Received<ExpectedPatients";
    

    $incomplete_up = DB::connection('sqlsrv')->select(DB::raw($incomplete_up_query));
    // $incomplete_up = [];

    $jsonDecoded = json_decode(json_encode($incomplete_up), true); 
    $fh = fopen('fileout_Incomplete_Uploads_'.$reportingMonth.'.csv', 'w');
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
    config(['database.connections.sqlsrv.database' => 'All_Staging_2016_2']);
    $table = DB::connection('sqlsrv')->select(DB::raw("WITH NDW_CurTx AS (
                SELECT
                    MFLCode,
                    FacilityName,
                    CTPartner,
                    County,
                    COUNT(DISTINCT CONCAT(PatientID, '-', PatientPK,'-',MFLCode)) AS CurTx_total
                FROM PortalDev.dbo.Fact_Trans_New_Cohort
                WHERE ARTOutcome = 'V'
                GROUP BY MFLCode, FacilityName, CTPartner, County
            ),
            Upload As (
            SELECT distinct
                MFLCode,
                FacName As FacilityName,
                [CT Partner],
                SiteAbstractionDate,
                DateUploaded
                from All_Staging_2016_2.dbo.Cohort2015_2016
            ),
            EMR As (SELECT
            Row_Number () over (partition by FacilityCode order by statusDate desc) as Num,
                facilityCode
                ,facilityName
                ,[value]
                ,statusDate
                ,indicatorDate
            FROM livesync.dbo.indicator
            where stage like '%EMR' and name like '%TX_CURR' and indicatorDate= EOMONTH(DATEADD(mm,-1,GETDATE()))
            ),
            DHIS2_CurTx AS (
                SELECT
                    [SiteCode],
                    [FacilityName],
                    [County],
                    [CurrentOnART_Total],
                    ReportMonth_Year
                FROM [All_Staging_2016_2].[dbo].[FACT_CT_DHIS2]
                WHERE ReportMonth_Year = ".Carbon::now()->subMonth()->format('Ym')."
            ),
            LatestEMR AS (Select
                    Emr.facilityCode 
                ,Emr.facilityName
                ,CONVERT (varchar,Emr.[value] ) As EMRValue
                ,Emr.statusDate
                ,Emr.indicatorDate
                from EMR
                where Num=1
                )
            Select
                    coalesce (NDW_CurTx.MFLCode,LatestEMR.facilityCode ) As MFLCode,
                    Coalesce (NDW_CurTx.FacilityName,LatestEMR.facilityName) As FacilityName,
                    NDW_CurTx.CTPartner,
                    NDW_CurTx.County,
                    DHIS2_CurTx.CurrentOnART_Total As KHIS_TxCurr,
                    NDW_CurTx.CurTx_total AS DWH_TXCurr,
                    LatestEMR.EMRValue As EMR_TxCurr,
                    LatestEMR.EMRValue-CurTx_total As Diff_EMR_DWH,
                    DHIS2_CurTx.CurrentOnART_Total-CurTx_total As DiffKHISDWH,
                    DHIS2_CurTx.CurrentOnART_Total-LatestEMR.EMRValue As DiffKHISEMR,
                CAST(ROUND((CAST(LatestEMR.EMRValue AS DECIMAL(7,2)) - CAST(NDW_CurTx .CurTx_total AS DECIMAL(7,2)))
                    /NULLIF(CAST(LatestEMR.EMRValue  AS DECIMAL(7,2)),0)* 100, 2) AS float) AS Percent_variance_EMR_DWH,
                CAST(ROUND((CAST(DHIS2_CurTx.CurrentOnART_Total AS DECIMAL(7,2)) - CAST(NDW_CurTx .CurTx_total AS DECIMAL(7,2)))
                    /CAST(DHIS2_CurTx.CurrentOnART_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_DWH,
                    CAST(ROUND((CAST(DHIS2_CurTx.CurrentOnART_Total AS DECIMAL(7,2)) - CAST(LatestEMR.EMRValue AS DECIMAL(7,2)))
                    /CAST(DHIS2_CurTx.CurrentOnART_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_EMR,
                    cast (Upload.DateUploaded as date)As DateUploaded,
                    cast (Upload.SiteAbstractionDate as date) As SiteAbstractionDate
                from NDW_CurTx
                left join LatestEMR on NDW_CurTx.MFLCode=LatestEMR.facilityCode
                left join DHIS2_CurTx on NDW_CurTx.MFLCode=DHIS2_CurTx.SiteCode COLLATE Latin1_General_CI_AS
                left join Upload on NDW_CurTx.MFLCode=Upload.MFLCode
                ORDER BY Percent_variance_EMR_DWH DESC"));
    // Get previous Month and Year
    $reportingMonth = Carbon::now()->subMonth()->format('M_Y');

    $jsonDecoded = json_decode(json_encode($table), true); 
    $fh = fopen('fileout_Comparison_'.$reportingMonth.'.csv', 'w');
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
    Mail::send('reports.partner.reports',
        [],
        function ($message) use (&$fh, &$reportingMonth) {
            // email configurations
            $message->from('dwh@mg.kenyahmis.org', 'NDWH');
            // email address of the recipients
            $message->to(["mary.gikura@thepalladiumgroup.com"])->subject('Comparison Report');
            // attach the csv file
            $message->attach('fileout_StaleDBs_'.$reportingMonth.'.csv');
            $message->attach('fileout_Incomplete_Uploads_'.$reportingMonth.'.csv');
            $message->attach('fileout_Comparison_'.$reportingMonth.'.csv');
        });
    return;

});

Route::get('/livesync', function(){
    ini_set('max_execution_time', -1);
    $etlJob = new EtlJob;
    $etlJob->save();
    GetSpotFacilities::dispatchNow();
    Facility::where('etl', true)->chunk(100, function ($facilities) use ($etlJob)  {
            $f = [];
            $facilities->each(function ($facility) use (&$f) {
                $f[$facility->code] = $facility->id;
            });
            GetIndicatorValues::dispatchNow(null, null, $f);
            PostLiveSyncIndicators::dispatchNow(array_values($f));
            $facilities->each(function ($facility) use ($etlJob) {
                GetSpotFacilityMetrics::dispatchNow($etlJob, $facility);
                GetSpotFacilityUploads::dispatchNow($etlJob, $facility);
            });
    });
});

Route::get('/email/start', function () {
    ini_set('max_execution_time', -1);
    $etlJob = new EtlJob;
    $etlJob->save();

    EtlJob::whereNull('started_at')->where('job_date', '<=', now())->where('id', '=', $etlJob->id)->each(function ($etlJob) {
        Etl::dispatch($etlJob);
    });
    
    return;
});

Route::get('/dqa/{email}', [MainController::class, 'DQAReport']);

Route::get('/peads/{email}', [MainController::class, 'PeadAlert']);

Route::get('/data_triangulation/{email}', [MainController::class, 'DataTriangulation']);
Route::get('/nupi/{email}', [MainController::class, 'NUPIAlert']);
Route::get('/unsubscribe/{email}', [EmailController::class, 'Unsubscribe'])->name('Unsubscribe');
Route::get('/resubscribe/{email}', [EmailController::class, 'Resubscribe'])->name('resubscribe');