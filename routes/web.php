<?php

use App\Http\Controllers\MainController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\HTSRecencyController;
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
    $query = 'SELECT 
        MFLCode, 
        PartnerName,
        FacilityName,
        County,
        SubCounty,
        COUNT(*) Adults,
        SUM(CASE WHEN VaccinationStatus in (\'Fully Vaccinated\',\'Not Vaccinated\',\'Partially Vaccinated\') THEN 1 ELSE 0 END) Screened,
        SUM(CASE WHEN VaccinationStatus in (\'Partially Vaccinated\') THEN 1 ELSE 0 END) Partially_Vacinated,
        SUM(CASE WHEN VaccinationStatus in (\'Fully Vaccinated\') THEN 1 ELSE 0 END) Fully_Vaccinated
    FROM REPORTING.[dbo].[LineListCovid]
    GROUP BY MFLCode, 
        PartnerName,
        FacilityName,
        County,
        SubCounty
    ';
    config(['database.connections.sqlsrv.database' => 'PortalDev']);
    $table = DB::connection('sqlsrv')->select(DB::raw($query));
    // Get previous Month and Year
    $reportingMonth = Carbon::now()->subMonth()->format('M_Y');

    $jsonDecoded = json_decode(json_encode($table), true); 
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
            $message->cc($emails_cc); 
            $message->attach('fileout_Covid_'.$reportingMonth.'.csv');
        });

});

Route::get('/email/comparison_txcurr', function () {
    
    $comparison_query = "WITH NDW_CurTx AS (
                SELECT
                    SiteCode MFLCode,
                    FacilityName,
                    PartnerName,
                    County,
                    SUM(ISTxCurr) AS CurTx_total
                FROM REPORTING.dbo.Linelist_FACTART
                WHERE ARTOutcome = 'V'
                GROUP BY SiteCode, FacilityName, PartnerName, County
            ),
--             Upload As (
--             SELECT distinct
--                 MFLCode,
--                 FacName As FacilityName,
--                 [CT Partner],
--                 SiteAbstractionDate,
--                 DateUploaded
--                 from All_Staging_2016_2.dbo.Cohort2015_2016
--             ),
            EMR As (
                SELECT
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
                FROM [NDWH].[dbo].[FACT_CT_DHIS2]
                WHERE ReportMonth_Year =".Carbon::now()->subMonth()->format('Ym')." and ISNUMERIC(SiteCode) =1
            ),
            LatestEMR AS (
                Select
                    Emr.facilityCode 
                    ,Emr.facilityName
                    ,CAST(CONVERT (varchar,Emr.[value] ) AS DECIMAL(10, 4)) As EMRValue
                    ,Emr.statusDate
                    ,Emr.indicatorDate
                from EMR
                where Num=1
            ),
            Uploads as (
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
                    MFLCode ,
                    FacilityName,
                    PartnerName,
                    AgencyName
                from REPORTING.dbo.all_EMRSites
            ),
            Combined AS (
                Select distinct
                    MFLCode,
                    FacilityName,
                    PartnerName,
                    AgencyName,
                    LatestUploads.DateReceived,
                    LatestUploads.PatientCount As ExpectedPatients
                from Facilities
                left join LatestUploads on Facilities.MFLCode =LatestUploads.SiteCode
            ),
            Uploaddata AS (
                Select
                    MFLCode,
                    FacilityName,
                    PartnerName,
                    AgencyName,
                    DateReceived,
                    ExpectedPatients,
                    Received.Received as CompletenessStatus
                from Combined
                left join Received on Combined.MFLCode=Received.Code
                where Received<ExpectedPatients
            ),
            DWAPI AS (
                SELECT
                    * 
                FROM
                    (
                    SELECT
                        ROW_NUMBER ( ) OVER ( PARTITION BY Sitecode ORDER BY CAST ( DateRecieved AS DATE ) DESC, JSON_VALUE(Items, '$.Version') DESC ) AS NUM,
                        fm.SiteCode,
                        JSON_VALUE ( Items, '$.Version' ) AS DwapiVersion,
                        JSON_VALUE ( Items, '$.Name' ) AS Docket 
                    FROM
                        ( SELECT DISTINCT code FROM DWAPICentral.dbo.PatientExtract p INNER JOIN DWAPICentral.dbo.Facility f ON f.Id= p.FacilityId AND f.Voided= 0 AND code > 1 ) p
                        LEFT JOIN DWAPICentral.dbo.FacilityManifest fm ON p.Code= fm.SiteCode
                        JOIN DWAPICentral.dbo.FacilityManifestCargo fc ON fc.FacilityManifestId= fm.Id 
                        AND CargoType = 2
                        ) Y 
                WHERE
                    Num = 1
            ) 
            Select
                coalesce (NDW_CurTx.MFLCode, null ) As MFLCode,
                NDW_CurTx.FacilityName As FacilityName,
                NDW_CurTx.PartnerName,
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
--                 cast (Upload.DateUploaded as date)As DateUploaded,
--                 cast (Upload.SiteAbstractionDate as date) As SiteAbstractionDate,
                case when CompletenessStatus is null then 'Complete' else 'Incomplete' End As Completeness,
				DWAPI.DwapiVersion
            from NDW_CurTx
            left join LatestEMR on NDW_CurTx.MFLCode=LatestEMR.facilityCode
			LEFT JOIN DWAPI ON DWAPI.SiteCode= LatestEMR.facilityCode
            left join DHIS2_CurTx on NDW_CurTx.MFLCode=DHIS2_CurTx.SiteCode COLLATE Latin1_General_CI_AS
--          left join Upload on NDW_CurTx.MFLCode=Upload.MFLCode
            left join Uploaddata on NDW_CurTx.MFLCode=Uploaddata.MFLCode COLLATE Latin1_General_CI_AS
            ORDER BY Percent_variance_EMR_DWH DESC";
    
    $comparison_hts = "WITH NDW_HTSPos AS (
                SELECT
                    MFLCode SiteCode,
                    FacilityName,
                    PartnerName SDP,
                    County  collate Latin1_General_CI_AS County,
                    SUM(positive) AS HTSPos_total
                FROM NDWH.dbo.FactHTSClientTests link
								LEFT JOIN NDWH.dbo.DimPatient AS pat ON link.PatientKey = pat.PatientKey
                LEFT JOIN NDWH.dbo.DimPartner AS part ON link.PartnerKey = part.PartnerKey
                LEFT JOIN NDWH.dbo.DimFacility AS fac ON link.FacilityKey = fac.FacilityKey
                LEFT JOIN NDWH.dbo.DimAgency AS agency ON link.AgencyKey = agency.AgencyKey
                where link.DateTestedKey  between  DATEADD(MONTH, DATEDIFF(MONTH, 0, GETDATE())-1, 0) and DATEADD(MONTH, DATEDIFF(MONTH, -1, GETDATE())-1, -1) and FinalTestResult='Positive' and MFLCode is not null and TestType in ('Initial Test', 'Initial')
                GROUP BY MFLCode, FacilityName, PartnerName, County
            ),
--         Upload As (
--             SELECT distinct
--                 MFLCode,
--                 FacName As FacilityName,
--                 [CT Partner],
--                 DateUploaded
--                 from All_Staging_2016_2.dbo.Cohort2015_2016
--             ),
            EMR As (SELECT
                Row_Number () over (partition by FacilityCode order by statusDate desc) as Num,
                    facilityCode
                    ,facilityName
                    ,[value]
                    ,statusDate
                    ,indicatorDate
                FROM livesync.dbo.indicator
                where stage like '%EMR' and name like '%HTS_TESTED_POS' and indicatorDate=EOMONTH(DATEADD(mm,-1,GETDATE())) and facilityCode is not null
                ),
            Facilityinfo AS (
                Select
                    MFL_Code,
                    County,
                    SDP,
                    EMR
                from HIS_Implementation.dbo.All_EMRSites
            ),
            DHIS2_HTSPos AS (
                SELECT
                    try_cast([SiteCode] as int) SiteCode,
                    [FacilityName] collate Latin1_General_CI_AS FacilityName,
                    [County],
                    Positive_Total,
                    ReportMonth_Year
                FROM [NDWH].[dbo].FACT_HTS_DHIS2
                WHERE ReportMonth_Year = ".Carbon::now()->subMonth()->format('Ym')." and SiteCode <>'NULL' and ISNUMERIC(SiteCode) =1
            ),
            LatestEMR AS (
                Select
                    Emr.facilityCode 
                    ,Emr.facilityName
                    ,CONVERT (varchar,Emr.[value] ) As EMRValue
                    ,Emr.statusDate
                    ,Emr.indicatorDate
                from EMR
                where Num=1 and Emr.facilityCode is not null
            ),
            DWAPI AS (
                SELECT
                    * 
                FROM
                    (
                    SELECT
                        ROW_NUMBER ( ) OVER ( PARTITION BY Sitecode ORDER BY CAST ( DateRecieved AS DATE ) DESC, JSON_VALUE(Items, '$.Version') DESC ) AS NUM,
                        fm.SiteCode,
                        JSON_VALUE ( Items, '$.Version' ) AS DwapiVersion,
                        JSON_VALUE ( Items, '$.Name' ) AS Docket 
                    FROM
                        ( SELECT DISTINCT code FROM DWAPICentral.dbo.PatientExtract p INNER JOIN DWAPICentral.dbo.Facility f ON f.Id= p.FacilityId AND f.Voided= 0 AND code > 1 ) p
                        LEFT JOIN DWAPICentral.dbo.FacilityManifest fm ON p.Code= fm.SiteCode
                        JOIN DWAPICentral.dbo.FacilityManifestCargo fc ON fc.FacilityManifestId= fm.Id 
                        AND CargoType = 2
                        ) Y 
                WHERE
                    Num = 1
            ) 
            Select
                coalesce (DHIS2_HTSPos.SiteCode, NDW_HTSPos.sitecode,LatestEMR.facilityCode ) As MFLCode,
                Coalesce (NDW_HTSPos.FacilityName, DHIS2_HTSPos.FacilityName) As FacilityName,
                fac.SDP As SDP,
                fac.emr as EMR,
                Coalesce (NDW_HTSPos.County, DHIS2_HTSPos.County) As County,
                DHIS2_HTSPos.Positive_Total As KHIS_HTSPos,
                coalesce (NDW_HTSPos.HTSPos_total, 0 )AS DWH_HTSPos,
                LatestEMR.EMRValue As EMR_HTSPos,
                LatestEMR.EMRValue-HTSPos_total As Diff_EMR_DWH,
                DHIS2_HTSPos.Positive_Total-HTSPos_total As DiffKHISDWH,
                DHIS2_HTSPos.Positive_Total-LatestEMR.EMRValue As DiffKHISEMR,
				CAST(ROUND((CAST(LatestEMR.EMRValue AS DECIMAL(7,2)) - CAST(coalesce(NDW_HTSPos.HTSPos_total, null) AS DECIMAL(7,2)))
                /NULLIF(CAST(LatestEMR.EMRValue  AS DECIMAL(7,2)),0)* 100, 2) AS float) AS Percent_variance_EMR_DWH,
                CAST(ROUND((CAST(DHIS2_HTSPos.Positive_Total AS DECIMAL(7,2)) - CAST(NDW_HTSPos.HTSPos_total AS DECIMAL(7,2)))
                /CAST(DHIS2_HTSPos.Positive_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_DWH,
                CAST(ROUND((CAST(DHIS2_HTSPos.Positive_Total AS DECIMAL(7,2)) - CAST(LatestEMR.EMRValue AS DECIMAL(7,2)))
                /CAST(DHIS2_HTSPos.Positive_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_EMR,
--                 cast (Upload.DateUploaded as date)As DateUploaded,
				DWAPI.DwapiVersion
            from DHIS2_HTSPos
            left join LatestEMR on DHIS2_HTSPos.sitecode=LatestEMR.facilityCode
			LEFT JOIN DWAPI ON DWAPI.SiteCode= LatestEMR.facilityCode
            left join NDW_HTSPos on NDW_HTSPos.sitecode=DHIS2_HTSPos.SiteCode
--             left join Upload on NDW_HTSPos.sitecode=Upload.MFLCode
            left join Facilityinfo fac on DHIS2_HTSPos.SiteCode=fac.MFL_Code
            where DHIS2_HTSPos.Positive_Total is not null
            ORDER BY Percent_variance_EMR_DWH DESC";
    
    config(['database.connections.sqlsrv.database' => 'NDWH']);
    $table = DB::connection('sqlsrv')->select(DB::raw($comparison_query));
    // Get previous Month and Year
    $reportingMonth = Carbon::now()->subMonth()->format('M_Y');

    $jsonDecoded = json_decode(json_encode($table), true); 
    $fh = fopen('fileout_Comparison_TXCURR_'.$reportingMonth.'.csv', 'w');
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

    config(['database.connections.sqlsrv.database' => 'NDWH']);
    $table2 = DB::connection('sqlsrv')->select(DB::raw($comparison_hts));

    $jsonDecoded = json_decode(json_encode($table2), true); 
    $fh = fopen('fileout_Comparison_HTS_'.$reportingMonth.'.csv', 'w');
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


    $emails = EmailContacts::where('is_main', 1 )->where('list_subscribed', 'DQA')->pluck('email')->toArray(); 
    
    foreach ($emails as $e){
        // Send the email
        Mail::send('reports.partner.reports',
            ['unsubscribe_url' => ''],
            function ($message) use (&$fh, &$reportingMonth, &$e) {
                // email configurations
                $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                // email address of the recipients
                $message->to([$e])->subject('Comparison Report');
                // attach the csv file
                $message->attach('fileout_Comparison_TXCURR_'.$reportingMonth.'.csv');
                $message->attach('fileout_Comparison_HTS_'.$reportingMonth.'.csv');
            });
    }
    return;

});



Route::get('/reportingMonth', function(){
    Log::info(now()->startOf('month')->sub(2, 'month')->addDays(16)->endOf('month')->startOf('day'));
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

    $etlJob->completed_at = now();
    $etlJob->save();
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
Route::get('/peadstesting/{email}', [MainController::class, 'PeadTestingAlert']);
Route::get('/htsrecency/pull', [HTSRecencyController::class, 'PullHtsRecency']);

Route::get('/data_triangulation/{email}', [MainController::class, 'DataTriangulation']);
Route::get('/nupi/{email}', [MainController::class, 'NUPIAlert']);
Route::get('/unsubscribe/{email}', [EmailController::class, 'Unsubscribe'])->name('Unsubscribe');
Route::get('/resubscribe/{email}', [EmailController::class, 'Resubscribe'])->name('resubscribe');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/nupi', [ImportController::class, 'getImport'])->name('import');
    Route::get('/import_parse', [ImportController::class, 'parseImport'])->name('import_parse');
    Route::post('/import_process', [ImportController::class, 'processImport'])->name('import_process');
    Route::post('file-upload/upload-large-files', [ImportController::class, 'uploadLargeFiles'])->name('files.upload.large');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
