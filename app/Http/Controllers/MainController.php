<?php

namespace App\Http\Controllers;


use App\Models\Facility;
use App\Models\EtlJob;
use App\Models\Partner;
use App\Models\EmailContacts;
use App\Models\PartnerMetric;
use App\Exports\DQAExport;
use App\Exports\TriangulationExport;

use App\Jobs\GenerateFacilityMetricsReport;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

use Carbon\Carbon;

class MainController extends Controller
{
    private $test_emails = [
        // "pascal.mwele@thepalladiumgroup.com", 
        // "lavatsa.leon@thepalladiumgroup.com", 
        // "andrine.njagi@thepalladiumgroup.com", 
        // "evans.munene@thepalladiumgroup.com",
        // "lousa.yogo@thepalladiumgroup.com",
        // "nobert.mumo@thepalladiumgroup.com",
        // "mary.gikura@thepalladiumgroup.com",
        // "dennis.ndwiga@thepalladiumgroup.com",
        // "paul.nthusi@thepalladiumgroup.com",
        // "margaret.gichuhi@thepalladiumgroup.com",
        // "mary.kilewe@thepalladiumgroup.com",
        // "stephen.chege@thepalladiumgroup.com",
        // "juliet.tangut@thepalladiumgroup.com",
        // "koske.kimutai@thepalladiumgroup.com",
        "charles.bett@thepalladiumgroup.com",
        // "cbrianbet@gmail.com",
        // "kennedy.muthoka@thepalladiumgroup.com"
    ];
    //
    public function DQAReport($email)
    {
        $partners_query = "select count(facilityId) facilities, partner
            from PortalDev.dbo.all_EMRSites q 
            where q.name IS NOT NULL and q.EMR IS NOT NULL and q.partner <> 'IRDO' and emr in ('KenyaEMR', 'DREAMS', 'AMRS', 'ECare', 'IQCare-KeHMIS') and q.EMR <> '' 
            GROUP BY partner";
        
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        //Partners 
        $partners = DB::connection('sqlsrv')->select(DB::raw($partners_query));
        if($email = "Test") {
            
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
            
            $emails = EmailContacts::where('is_main', 1 )->where('list_subscribed', 'DQA')->pluck('email')->toArray(); 
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
            $fh = fopen(__DIR__ .'/../../../storage/fileout_StaleDBs_'.$reportingMonth.'.csv', 'w');
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
            $fh = fopen(__DIR__ .'/../../../storage/fileout_Incomplete_Uploads_'.$reportingMonth.'.csv', 'w');
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
            foreach ($emails as $test){
                $unsubscribe_url = str_replace(
                        '{{email}}', $test,
                        nova_get_setting(nova_get_setting('production') ? 'email_unsubscribe_url' : 'email_unsubscribe_url_staging')
                    );
                // Send the email
                Mail::send('reports.partner.dqa',
                    [
                        'partner' => ['partner' => '', 'facilities' => 0,],
                        'ct_per' => $ct_per,
                        'hts_per' => $hts_per,
                        'reportMonth' => Carbon::now()->subMonth()->format('M Y'),
                        'stale_num' => count($stale),//5,
                        'unsubscribe_url' => $unsubscribe_url,
                        'incomplete_up' => count($incomplete_up)
                    ],
                    function ($message) use (&$fh, &$emails, &$reportingMonth, &$test) {
                        // email configurations
                        $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                        // email address of the recipients
                        $message->to($test)->subject('DQA Report');
                        // attach the csv  file
                        $message->attach(__DIR__ .'/../../../storage/fileout_StaleDBs_'.$reportingMonth.'.csv');
                        $message->attach(__DIR__ .'/../../../storage/fileout_Incomplete_Uploads_'.$reportingMonth.'.csv');
                    });
            
            }
            return "Test sent!!";
            
        }
        else
        foreach ($partners as $partner){
            $organization = DB::connection('sqlsrv')->table('DWHIdentity.dbo.Organizations')
                ->selectRaw('Name, Id, Code')
                ->where('UsgMechanism', $partner->partner)
                ->whereNotNull('UsgMechanism')
                ->get()->first();
            if ($organization){
                $contacts = DB::connection('sqlsrv')->table('DWHIdentity.dbo.OrganizationContactses')
                    ->selectRaw('Email, Names')
                    ->where('OrganizationId', $organization->Id)
                    ->where('Unsubscribe', 0)
                    ->get();
                
                $emails = array();
                foreach ($contacts as $contact) $emails[] = $contact->Email;

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
                    where proportion < 0.5 and SDP = '".$partner->partner."'";
                
                $stale = DB::connection('sqlsrv')->select(DB::raw($stale_query));
                // $stale = [];

                $reportingMonth = Carbon::now()->subMonth()->format('M_Y');
                $jsonDecoded = json_decode(json_encode($stale), true); 
                $fh = fopen(__DIR__ .'/../../../storage/fileout_StaleDBs_'.$reportingMonth.'.csv', 'w');
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
                        where Received<ExpectedPatients and CTPartner = '" . $partner->partner ."'";
                

                $incomplete_up = DB::connection('sqlsrv')->select(DB::raw($incomplete_up_query));
                // $incomplete_up = [];
                $jsonDecoded = json_decode(json_encode($incomplete_up), true); 
                $fh = fopen(__DIR__ .'/../../../storage/fileout_Incomplete_Uploads_'.$reportingMonth.'.csv', 'w');
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

                $ct_expected_partner = "select sum(expected) as totalexpected from portaldev.expected_uploads where docket='CT'  COLLATE utf8mb4_general_ci and partner = '".$partner->partner."' COLLATE utf8mb4_general_ci";
                $ct_expected_partner_ll = "select * from portaldev.expected_uploads where docket='CT'  COLLATE utf8mb4_general_ci and partner = '".$partner->partner."' COLLATE utf8mb4_general_ci";
                $ct_recency_partner = "select sum(recency) as totalrecency from portaldev.recency_uploads where docket='CT' COLLATE utf8mb4_general_ci and year=".Carbon::now()->subMonth()->format('Y')." and month=".Carbon::now()->subMonth()->format('m')." and partner = '".$partner->partner."' COLLATE utf8mb4_general_ci";
                $ct_recency_partner_ll = "select * from portaldev.recency_uploads where docket='CT' COLLATE utf8mb4_general_ci and year=".Carbon::now()->subMonth()->format('Y')." and month=".Carbon::now()->subMonth()->format('m')." and partner = '".$partner->partner."' COLLATE utf8mb4_general_ci";
                
                $hts_expected_partner = "select sum(expected) as totalexpected from portaldev.expected_uploads where docket='HTS' COLLATE utf8mb4_general_ci and partner = '".$partner->partner."' COLLATE utf8mb4_general_ci";
                $hts_expected_partner_ll = "select * from portaldev.expected_uploads where docket='HTS' COLLATE utf8mb4_general_ci and partner = '".$partner->partner."' COLLATE utf8mb4_general_ci";
                $hts_recency_partner = "select sum(recency) as totalrecency from portaldev.recency_uploads where docket='HTS' COLLATE utf8mb4_general_ci and year=".Carbon::now()->subMonth()->format('Y')." and month=".Carbon::now()->subMonth()->format('m')." and partner = '".$partner->partner."' COLLATE utf8mb4_general_ci";
                $hts_recency_partner_ll = "select * from portaldev.recency_uploads where docket='HTS' COLLATE utf8mb4_general_ci and year=".Carbon::now()->subMonth()->format('Y')." and month=".Carbon::now()->subMonth()->format('m')." and partner = '".$partner->partner."' COLLATE utf8mb4_general_ci";
                
                config(['database.connections.mysql.database' => 'portaldev']);
                $ct_expected = DB::connection('mysql')->select(DB::raw($ct_expected_partner))[0];
                $ct_recency = DB::connection('mysql')->select(DB::raw($ct_recency_partner))[0];
                $hts_expected = DB::connection('mysql')->select(DB::raw($hts_expected_partner))[0];
                $hts_recency = DB::connection('mysql')->select(DB::raw($hts_recency_partner))[0];
                $ct_expected_ll = DB::connection('mysql')->select(DB::raw($ct_expected_partner_ll));
                $ct_recency_ll = DB::connection('mysql')->select(DB::raw($ct_recency_partner_ll));
                $hts_expected_ll = DB::connection('mysql')->select(DB::raw($hts_expected_partner_ll));
                $hts_recency_ll = DB::connection('mysql')->select(DB::raw($hts_recency_partner_ll));
                // dd($hts_expected_ll);
                

                Excel::store(new DQAExport([$stale, $incomplete_up, $hts_recency_ll, $ct_recency_ll, $ct_expected_ll, $hts_expected_ll]), 'fileout_DQA_'.$reportingMonth.'.xlsx');
                

                $ct_per = $ct_recency->totalrecency * 100 / $ct_expected->totalexpected ;
                $hts_per = $hts_recency->totalrecency *100 / $hts_expected->totalexpected;
                // return $hts_per;
                $this->GenerateSDPTXCurrReport($partner->partner);
                $this->CreateCSV(__DIR__ .'/../../../storage/fileout_ct_expected_line_list_'.$reportingMonth.'.csv', $ct_expected_ll);
                $this->CreateCSV(__DIR__ .'/../../../storage/fileout_hts_expected_line_list_'.$reportingMonth.'.csv', $hts_expected_ll);
                $this->CreateCSV(__DIR__ .'/../../../storage/fileout_ct_recency_line_list_'.$reportingMonth.'.csv', $ct_recency_ll);
                $this->CreateCSV(__DIR__ .'/../../../storage/fileout_hts_recency_line_list_'.$reportingMonth.'.csv', $hts_recency_ll);
                

                if (count($contacts) !== 0) {
                    $unsubscribe_url = str_replace(
                            '{{email}}', $contacts,
                            nova_get_setting(nova_get_setting('production') ? 'email_unsubscribe_url' : 'email_unsubscribe_url_staging')
                        );
                    Mail::send('reports.partner.dqa',
                        [
                            'partner' => $partner,
                            'ct_per' => $ct_per,
                            'hts_per' => $hts_per,
                            'reportMonth' => Carbon::now()->subMonth()->format('M Y'),
                            'stale_num' => count($stale),//5,
                            'unsubscribe_url' => $unsubscribe_url,
                            'incomplete_up' => count($incomplete_up)
                        ],
                        function ($message) use (&$fh, &$emails, &$reportingMonth,&$partner) {
                            // email configurations
                            $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                            // email address of the recipients
                            // $message->to($emails)->subject('DQA Report');
                            $message->to(["charles.bett@thepalladiumgroup.com"])->subject('DQA Report');
                            $message->cc(["mary.gikura@thepalladiumgroup.com", "nobert.mumo@thepalladiumgroup.com", "lousa.yogo@thepalladiumgroup.com","koske.kimutai@thepalladiumgroup.com"]);
                            // attach the csv file
                            $message->attach(__DIR__ .'/../../../storage/fileout_StaleDBs_'.$reportingMonth.'.csv');
                            // $message->attach(__DIR__ .'/../../../storage/fileout_Triangulation_TXCURR_'.$reportingMonth.$partner->partner.'.csv');
                            $message->attach(__DIR__ .'/../../../storage/fileout_hts_recency_line_list_'.$reportingMonth.'.csv');
                            $message->attach(__DIR__ .'/../../../storage/fileout_ct_recency_line_list_'.$reportingMonth.'.csv');
                            $message->attach(__DIR__ .'/../../../storage/fileout_hts_expected_line_list_'.$reportingMonth.'.csv');
                            $message->attach(__DIR__ .'/../../../storage/fileout_ct_expected_line_list_'.$reportingMonth.'.csv');
                        });
                    return;
                }
            }
        }


        // Send the email
        Mail::send('reports.partner.dqa',
            [],
            function ($message) {
                // email configurations
                $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                // email address of the recipients
                $message->to(["charles.bett@thepalladiumgroup.com"])->subject('DQA Report');
                $message->cc(["charles.bett@thepalladiumgroup.com"]);
                // attach the csv covid file
                // $message->attach('fileout_NUPI_'.'.csv');
            });
        return "DONE";
    }

    public function PeadAlert($email)
    {
        $query = "WITH otz_10_19_yrs as (
                select
                    MFLCode,
                    FacilityName,
                    CTPartner,
                    County,
                    count(*) as no_otz_10_19_yrs
                from PortalDev.dbo.FACT_Trans_OTZEnrollments
                where TXCurr = 1
                    and DATIM_AgeGroup in ('10 to 14', '15 to 19')
                    and OTZEnrollmentDate  is not null
                group by MFLCode, FacilityName, CTPartner, County
            ), ovc_0_17_yrs as (
                select
                    MFLCode,
                    FacilityName,
                    County,
                    CTPartner,
                    count(*) as no_ovc_0_17_yrs
                from PortalDev.dbo.Fact_Trans_New_Cohort
                where TXCurr=1
                    and ageLV between 0 and 17
                    and OVCEnrollmentDate is not null
                group by MFLCode, FacilityName, CTPartner, County
            ),
            documented_viral_loads_last_12 as (
                select
                distinct PatientID,
                SiteCode,
                PatientPK
                from All_Staging_2016_2.dbo.vw_GetViralLoads
                where OrderedbyDate between dateadd(m, -12, dateadd(day, 1, eomonth(getdate(), -1))) -- subtract 12 months from last day of previous completed month
                    and eomonth(dateadd(mm, -1, getdate())) --get last day of previous completed month
                    and TestResult is not null
            ), txcurr_0_17_yrs_valid_vl_12_months as (
                select
                        MFLCode,
                        FacilityName,
                        County,
                        CTPartner,
                        count(*) as no_txcurr_0_17_yrs_valid_vl_12_months
                from PortalDev.dbo.Fact_Trans_New_Cohort as cohort
                inner join documented_viral_loads_last_12 on documented_viral_loads_last_12.PatientId = cohort.PatientId
                    and documented_viral_loads_last_12.PatientPK = cohort.PatientPK
                    and documented_viral_loads_last_12.SiteCode = cohort.MFLCode
                where ageLV between 0 and 17
                    and TXCurr=1
                group by MFLCode, FacilityName, CTPartner, County
            ), documented_regimen_0_19_yrs as (
                select
                        MFLCode,
                        FacilityName,
                        County,
                        CTPartner,
                        count(*) as no_txcurr_0_19_yrs_documented_regimen
                from PortalDev.dbo.Fact_Trans_New_Cohort as cohort
                where ageLV between 0 and 19
                    and TXCurr=1
                group by MFLCode, FacilityName, CTPartner, County
            ),
            visit_weight_and_height_ordering as (
                /* order pharmacy dispensations as of date by the VisitDate */
                select
                    distinct PatientID,
                    PatientPK,
                    SiteCode,
                    VisitDate,
                    Weight,
                    Height,
                    row_number() over (partition by  PatientID,PatientPK, SiteCode order by VisitDate desc) as Num
                from All_Staging_2016_2.dbo.vw_LastWeightHeight
            ),
            latest_visit as (
                select
                visit_weight_and_height_ordering.*,
                ageLV,
                ARTOutcome
                from visit_weight_and_height_ordering
                Left join PortalDev.dbo.Fact_Trans_New_Cohort on visit_weight_and_height_ordering.PatientID=Fact_Trans_New_Cohort.PatientID
                and visit_weight_and_height_ordering.PatientPK=Fact_Trans_New_Cohort.PatientPK
                and visit_weight_and_height_ordering.SiteCode=Fact_Trans_New_Cohort.MFLCode
                where Num = 1
            ),
            second_latest_visit as (
                select
                    *
                from visit_weight_and_height_ordering
                where Num = 2
            ),
            combined_weight_last_2_visits as (
            select
                coalesce(latest_visit.PatientID, second_latest_visit.PatientID) as PatientID,
                coalesce(latest_visit.PatientPK, second_latest_visit.PatientPK) as PatientPK,
                coalesce(latest_visit.SiteCode, second_latest_visit.SiteCode) as SiteCode,
                coalesce(latest_visit.Weight, second_latest_visit.Weight) as Weight,
                latest_visit.ageLV,
                latest_visit.ARTOutcome
            from latest_visit
            full join second_latest_visit on latest_visit.PatientID = second_latest_visit.PatientID
                and latest_visit.PatientPK = second_latest_visit.PatientPK
                and latest_visit.SiteCode = second_latest_visit.SiteCode
            ),
            facility_partner_combinations as (
                select
                    distinct MFLCode,
                    FacilityName,
                    CTPartner,
                    County
                from PortalDev.dbo.Fact_Trans_New_Cohort
            ),
            documented_weight_last_2_visits as (
                select
                    MFLCode,
                    FacilityName,
                    CTPartner,
                    County ,
                    count(*) as no_documented_weight
                from combined_weight_last_2_visits
                left join facility_partner_combinations on facility_partner_combinations.MFLCode = combined_weight_last_2_visits.SiteCode
                where combined_weight_last_2_visits.Weight is not null and ageLV between 0 and 19 and ARTOutcome='V'
                group by MFLCode, FacilityName, CTPartner, County  
            ),
            Paeds as (
            Select
                MFLCode,
                FacilityName,
                County,
                CTPartner,
                CTAgency,
                Count (*)PaedsTXCurr
            from PortalDev.dbo.Fact_Trans_New_Cohort
            where ageLV between 0 and 19  and TXCurr=1
            group by
                MFLCode,
                FacilityName,
                County,
                CTPartner,
                CTAgency
                ),
            FemaleAdults AS (
            Select
                MFLCode,
                FacilityName,
                County,
                CTPartner,
                CTAgency,
                Count (*)Females15TXCurr
            from PortalDev.dbo.Fact_Trans_New_Cohort
            where ageLV >=15  and TXCurr=1 and Gender='Female'
            group by
                MFLCode,
                FacilityName,
                County,
                CTPartner,
                CTAgency
                ),
                PaedsListed AS (
            SELECT
                MFLCode,
                Coalesce (listing.FacilityName,Cohort.FacilityName)As FacilityName,
                Cohort.County,
                Cohort.CTPartner,
                Cohort.CTAgency,
                Count (Distinct concat(ContactPatientPK,Sitecode))PaedsListed
            FROM [All_Staging_2016_2].[dbo].[stg_ContactListing] listing 
            inner join PortalDev.dbo.Fact_Trans_New_Cohort Cohort on
            listing.PatientID=Cohort.PatientID and
            listing.PatientPK=Cohort.PatientPK and
            listing.SiteCode=Cohort.MFLCode
            where ContactAge<15
                    and cohort.Gender = 'Female'
                    and cohort.ageLV >= 15
                    and cohort.TXCurr =1
            Group by
                MFLCode,
                Coalesce (listing.FacilityName,Cohort.FacilityName),
                Cohort.County,
                Cohort.CTPartner,
                Cohort.CTAgency
                ),
                PaedsTested AS (
            SELECT
                MFLCode,
                Coalesce (listing.FacilityName,Cohort.FacilityName)As FacilityName,
                Cohort.County,
                Cohort.CTPartner,
                Cohort.CTAgency,
                Count (Distinct concat(ContactPatientPK,listing.SiteCode))As PaedsTested
            FROM [All_Staging_2016_2].[dbo].[stg_ContactListing] listing 
            inner join PortalDev.dbo.Fact_Trans_New_Cohort Cohort on
            listing.PatientID=Cohort.PatientID and
            listing.PatientPK=Cohort.PatientPK and
            listing.SiteCode=Cohort.MFLCode
            inner join All_Staging_2016_2.dbo.stg_hts_ClientTests tests on
            listing.ContactPatientPK=tests.PatientPk and
            listing.SiteCode=tests.SiteCode
            where ContactAge<15
                    and cohort.Gender = 'Female'
                    and cohort.ageLV >= 15
                    and cohort.TXCurr = 1
            Group by
                MFLCode,
                Coalesce (listing.FacilityName,Cohort.FacilityName),
                Cohort.County,
                Cohort.CTPartner,
                Cohort.CTAgency
                ),
                MMDCalc as (
                select
                        PatientID,
                        PatientPK,
                        MFLCode,
                        FacilityName,
                        County,
                        CTPartner,
                        dtlastvisit,
                        NextAppointmentDate,
                        CASE WHEN ABS(DATEDIFF(DAY,dtLastVisit ,NextAppointmentDate) ) <=83 THEN 0
                    WHEN ABS(DATEDIFF(DAY,dtLastVisit ,NextAppointmentDate) )   >= 84 THEN  1
                    ELSE NULL END AS MMDStatus
                from PortalDev.dbo.Fact_Trans_New_Cohort as cohort
                where ageLV between 0 and 19
                    and ARTOutcome='V'
            ),
            PaedsOnMMD AS (Select
                        MFLCode,
                        FacilityName,
                        County,
                        CTPartner,
                        Count (*) PaedsOnMMD
                        from MMDCalc
                        where MMDStatus=1
                        group by
                        MFLCode,
                        FacilityName,
                        County,
                        CTPartner
            ),
            IITPaeds As (
            Select
                        PatientID,
                        PatientPK,
                        MFLCode,
                        FacilityName,
                        County,
                        CTPartner,
                        ARTOutcome,
                        NextAppointmentDate
            from PortalDev.dbo.Fact_Trans_New_Cohort
            where ageLV between 0 and 19 and datediff (mm,NextAppointmentDate, EOMONTH(DATEADD(mm,-1,GETDATE())))<=6 and ARTOutcome not in ('V','D','T','NP','S')
            ),
            PaedsIIT AS (
            Select
                        MFLCode,
                        FacilityName,
                        County,
                        CTPartner,
                        count(*)IITPaeds
                        from IITPaeds
                        group by
                        MFLCode,
                        FacilityName,
                        County,
                        CTPartner
                        )
            select
                facility_partner_combinations.MFLCode,
                facility_partner_combinations.FacilityName,
                facility_partner_combinations.CTPartner,
                facility_partner_combinations.County,
                Coalesce (Females15TXCurr,0) As Females15TXCurr,
                Coalesce (PaedsTXCurr,0) As PaedsTXCurr,
                coalesce (PaedsListed,0) As PaedsListed,
                coalesce (PaedsTested,0) As PaedsTested,
                coalesce(txcurr_0_17_yrs_valid_vl_12_months.no_txcurr_0_17_yrs_valid_vl_12_months, 0) as no_txcurr_0_17_yrs_valid_vl_12_months,
                coalesce(documented_regimen_0_19_yrs.no_txcurr_0_19_yrs_documented_regimen, 0) as no_txcurr_0_19_yrs_documented_regimen,
                coalesce(documented_weight_last_2_visits.no_documented_weight, 0) as no_documented_weight,
                Coalesce (PaedsOnMMD,0) As PaedsOnMMD,
                coalesce(otz_10_19_yrs.no_otz_10_19_yrs, 0) as no_otz_10_19_yrs,
                coalesce(ovc_0_17_yrs.no_ovc_0_17_yrs, 0) as no_ovc_0_17_yrs,
                coalesce (IITPaeds,0) as IITPaeds
            from facility_partner_combinations
            left join otz_10_19_yrs on otz_10_19_yrs.MFLCode = facility_partner_combinations.MFLCode
            left join ovc_0_17_yrs on ovc_0_17_yrs.MFLCode = facility_partner_combinations.MFLCode
            left join txcurr_0_17_yrs_valid_vl_12_months on txcurr_0_17_yrs_valid_vl_12_months.MFLCode = facility_partner_combinations.MFLCode
            left join documented_regimen_0_19_yrs on documented_regimen_0_19_yrs.MFLCode = facility_partner_combinations.MFLCode
            left join documented_weight_last_2_visits on documented_weight_last_2_visits.MFLCode = facility_partner_combinations.MFLCode
            Left join Paeds on facility_partner_combinations.MFLCode=Paeds.MFLCode
            Left join FemaleAdults on facility_partner_combinations.MFLCode=FemaleAdults.MFLCode 
            Left join PaedsListed on facility_partner_combinations.MFLCode=PaedsListed.MFLCode
            Left join PaedsTested on facility_partner_combinations.MFLCode=PaedsTested.MFLCode
            Left join PaedsOnMMD on facility_partner_combinations.MFLCode=PaedsOnMMD.MFLCode
            left join PaedsIIT on PaedsIIT.MFLCode=facility_partner_combinations.MFLCode";
        
        
        $query2 = "SELECT * from (Select Distinct df.FacilityId,Name as FacilityName,County,subCounty,Agency,Partner, f.year,f.month, f.docketId ,f.timeId as uploaddate
                from (select name,facilityId,county,subcounty,agency,partner, \"CT\" AS docket from portaldev.dim_facility where isCt = 1) df
                LEFT JOIN (SELECT * FROM (
                            SELECT DISTINCT ROW_NUMBER ( ) OVER (PARTITION BY FacilityId,docketId,Concat(Month(fm.timeId),'-', Year(fm.timeId)) ORDER BY (cast(fm.timeId as date)) desc) AS RowID,
                            FacilityId,docketId,fm.timeId, dt.year,dt.month FROM  portaldev.fact_manifest fm
                            inner join portaldev.dim_time dt on dt.timeId=fm.timeId
                            where dt.year = ".Carbon::now()->subMonth(1)->format('Y')." and dt.month = ".Carbon::now()->subMonth(1)->format('m')."
                                )u where RowId=1) f on f.facilityId=df.facilityId and df.docket=f.docketId) Y
                                WHERE uploaddate is null ";
        
        
        
        config(['database.connections.sqlsrv.database' => 'All_Staging_2016_2']);

        $table = DB::connection('sqlsrv')->select(DB::raw($query));
        // Get previous Month and Year
        $reportingMonth = Carbon::now()->subMonth()->format('M_Y_D');
        $jsonDecoded = json_decode(json_encode($table), true); 
        $fh = fopen(__DIR__ .'/../../../storage/fileout_Paeds_'.$reportingMonth.'.csv', 'w');
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

        config(['database.connections.mysql.database' => 'portaldev']);
        $fac_not_reporting = DB::connection('mysql')->select(DB::raw($query2));
        
        $jsonDecoded = json_decode(json_encode($fac_not_reporting), true); 
        $fh = fopen(__DIR__ .'/../../../storage/fileout_FacilitiesNotReporting_'.$reportingMonth.'.csv', 'w');
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

        if($email = "Test") {
            $emails = EmailContacts::where('is_main', 1 )->where('list_subscribed', 'Paeds')->pluck('email')->toArray(); 
            
            foreach ($emails as $test){
                $unsubscribe_url = str_replace(
                        '{{email}}', $test,
                        nova_get_setting(nova_get_setting('production') ? 'email_unsubscribe_url' : 'email_unsubscribe_url_staging')
                    );
                // Send the email
                Mail::send('reports.partner.topline',
                    [
                        'unsubscribe_url' => $unsubscribe_url
                    ],
                    function ($message) use (&$fh, &$reportingMonth, &$test) {
                        // email configurations
                        $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                        // email address of the recipients
                        $message->to($test)->subject('Paediatric Topline Indicators');
                        // $message->cc(["mary.gikura@thepalladiumgroup.com", "nobert.mumo@thepalladiumgroup.com", "charles.bett@thepalladiumgroup.com"]);
                        // attach the csv covid file
                        $message->attach(__DIR__ .'/../../../storage/fileout_Paeds_'.$reportingMonth.'.csv');
                        $message->attach(__DIR__ .'/../../../storage/fileout_FacilitiesNotReporting_'.$reportingMonth.'.csv');
                    });
            }
            return "DONE";

        }else{
            $unsubscribe_url = str_replace(
                    '{{email}}', "",
                    nova_get_setting(nova_get_setting('production') ? 'email_unsubscribe_url' : 'email_unsubscribe_url_staging')
                );
            // Send the email
            Mail::send('reports.partner.topline',
                [
                    'unsubscribe_url' => $unsubscribe_url
                ],
                function ($message) use (&$fh, &$reportingMonth) {
                    // email configurations
                    $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                    // email address of the recipients
                    $message->to(["kennedy.muthoka@thepalladiumgroup.com"])->subject('Paediatric Topline Indicators');
                    // $message->cc(["npm1@cdc.gov", "mary.gikura@thepalladiumgroup.com", "kennedy.muthoka@thepalladiumgroup.com", "charles.bett@thepalladiumgroup.com", "Evans.Munene@thepalladiumgroup.com", "koske.kimutai@thepalladiumgroup.com"]);
                    $message->cc(["mary.gikura@thepalladiumgroup.com", "nobert.mumo@thepalladiumgroup.com", "charles.bett@thepalladiumgroup.com"]);
                    // attach the csv covid file
                    $message->attach(__DIR__ .'/../../../storage/fileout_Paeds_'.$reportingMonth.'.csv');
                    $message->attach(__DIR__ .'/../../../storage/fileout_FacilitiesNotReporting_'.$reportingMonth.'.csv');
                });
            return "DONE";
        }
    }

    public function DataTriangulation($email)
    {
        // Get previous Month and Year
        $reportingMonth = Carbon::now()->subMonth()->format('M_Y');

        $query_txcurr = "With NDW_CurTx AS (
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
                    DHIS2_CurTx.CurrentOnART_Total-CurTx_total As DiffKHISDWH,
                    DHIS2_CurTx.CurrentOnART_Total-LatestEMR.EMRValue As DiffKHISEMR,
                CAST(ROUND((CAST(DHIS2_CurTx.CurrentOnART_Total AS DECIMAL(7,2)) - CAST(NDW_CurTx .CurTx_total AS DECIMAL(7,2)))
                    /CAST(DHIS2_CurTx.CurrentOnART_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_DWH,
                    CAST(ROUND((CAST(DHIS2_CurTx.CurrentOnART_Total AS DECIMAL(7,2)) - CAST(LatestEMR.EMRValue AS DECIMAL(7,2)))
                    /CAST(DHIS2_CurTx.CurrentOnART_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_EMR,
                    CAST(ROUND((CAST(LatestEMR.EMRValue AS DECIMAL(7,2)) - CAST(NDW_CurTx .CurTx_total AS DECIMAL(7,2)))
                    /CAST(NULLIF(LatestEMR.EMRValue, 0)  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_EMR_DWH
                from NDW_CurTx
                left join LatestEMR on NDW_CurTx.MFLCode=LatestEMR.facilityCode
                left join DHIS2_CurTx on NDW_CurTx.MFLCode=DHIS2_CurTx.SiteCode COLLATE Latin1_General_CI_AS";
        
        $query_txnew = "With NDW_NewTx AS (
                select  
                    MFLCode,
                    FacilityName,
                    CTPartner,
                    County,
                    SUM([StartedART]) txNew 
            from PortalDev.dbo.FACT_Trans_Newly_Started 
            where [StartedART] > 0 
            and Start_Year = ".Carbon::now()->subMonth(2)->format('Y')." and StartART_Month =".Carbon::now()->subMonth(2)->format('m')."
            GROUP BY MFLCode, FacilityName, CTPartner, County
            ),
            EMR As (SELECT
            Row_Number () over (partition by FacilityCode order by statusDate desc) as Num,
                facilityCode
                ,facilityName
                ,[value]
                ,statusDate
                ,indicatorDate
            FROM livesync.dbo.indicator
            where stage like '%EMR' and name like '%TX_NEW' and indicatorDate=  EOMONTH(DATEADD(mm,-2,GETDATE()))
            ),
            DHIS2_TxNew AS (
                SELECT
                    [SiteCode],
                    [FacilityName],
                    [County],
                    [StartedART_Total],
                    ReportMonth_Year
                FROM [All_Staging_2016_2].[dbo].[FACT_CT_DHIS2]
                WHERE ReportMonth_Year = ".Carbon::now()->subMonth(2)->format('Ym')."
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
                    coalesce (NDW_NewTx.MFLCode,LatestEMR.facilityCode ) As MFLCode,
                    Coalesce (NDW_NewTx.FacilityName,LatestEMR.facilityName) As FacilityName,
                    NDW_NewTx.CTPartner,
                    NDW_NewTx.County,
                    DHIS2_TxNew.StartedART_Total As KHIS_TxNew,
                    NDW_NewTx.txNew AS DWH_TXNew,
                    LatestEMR.EMRValue As EMR_TxNew,
                    DHIS2_TxNew.StartedART_Total-txNew As DiffKHISDWH,
                    DHIS2_TxNew.StartedART_Total-LatestEMR.EMRValue As DiffKHISEMR,
                CAST(ROUND((CAST(DHIS2_TxNew.StartedART_Total AS DECIMAL(7,2)) - CAST(NDW_NewTx .txNew AS DECIMAL(7,2)))
                    /CAST(DHIS2_TxNew.StartedART_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_DWH,
                    CAST(ROUND((CAST(DHIS2_TxNew.StartedART_Total AS DECIMAL(7,2)) - CAST(LatestEMR.EMRValue AS DECIMAL(7,2)))
                    /CAST(DHIS2_TxNew.StartedART_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_EMR,
                    CAST(ROUND((CAST(LatestEMR.EMRValue AS DECIMAL(7,2)) - CAST(NDW_NewTx .txNew AS DECIMAL(7,2)))
                    /CAST(NULLIF(LatestEMR.EMRValue, 0)  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_EMR_DWH
                from NDW_NewTx
                left join LatestEMR on NDW_NewTx.MFLCode=LatestEMR.facilityCode
                left join DHIS2_TxNew on NDW_NewTx.MFLCode=DHIS2_TxNew.SiteCode COLLATE Latin1_General_CI_AS";
        $query_hts_tested = "With NDW AS (SELECT
            Row_Number () over (partition by FacilityCode order by statusDate desc) as Num,
                facilityCode
                ,facilityName
                ,[value]
                ,statusDate
                ,indicatorDate
            FROM livesync.dbo.indicator
            where stage like '%DWH' and name like '%HTS_TESTED' and statusDate>= '2022-10-06'
            ),
            EMR As (SELECT
            Row_Number () over (partition by FacilityCode order by statusDate desc) as Num,
                facilityCode
                ,facilityName
                ,[value]
                ,statusDate
                ,indicatorDate
            FROM livesync.dbo.indicator
            where stage like '%EMR' and name like '%HTS_TESTED' and indicatorDate>=  EOMONTH(DATEADD(mm,-2,GETDATE()))
            ),
            DHIS2_TxNew AS (
                SELECT
                    [SiteCode],
                    [FacilityName],
                    [County],
                    Tested_Total,
                    ReportMonth_Year
                FROM [All_Staging_2016_2].[dbo].[FACT_HTS_DHIS2]
                WHERE ReportMonth_Year = ".Carbon::now()->subMonth(2)->format('Ym')."
            ),
            LatestDWH AS (Select
                    NDW.facilityCode 
                ,NDW.facilityName
                ,CONVERT (varchar,NDW.[value] ) As DWHValue
                ,NDW.statusDate
                ,NDW.indicatorDate
                from NDW
                where Num=1
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
                    coalesce (LatestDWH.facilityCode,LatestEMR.facilityCode ) As MFLCode,
                    Coalesce (LatestDWH.facilityName,LatestEMR.facilityName) As FacilityName,
                    --LatestDWH.CTPartner,
                    --NDW.County,
                    DHIS2_TxNew.Tested_Total As KHIS_Value,
                    LatestDWH.DWHValue AS DWHValue,
                    LatestEMR.EMRValue As EMRValue,
                    DHIS2_TxNew.Tested_Total-DWHValue As DiffKHISDWH,
                    DHIS2_TxNew.Tested_Total-LatestEMR.EMRValue As DiffKHISEMR,
                CAST(ROUND((CAST(DHIS2_TxNew.Tested_Total AS DECIMAL(7,2)) - CAST(DWHValue AS DECIMAL(7,2)))
                    /CAST(DHIS2_TxNew.Tested_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_DWH,
                    CAST(ROUND((CAST(DHIS2_TxNew.Tested_Total AS DECIMAL(7,2)) - CAST(LatestEMR.EMRValue AS DECIMAL(7,2)))
                    /CAST(DHIS2_TxNew.Tested_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_EMR,
                    CAST(ROUND((CAST(LatestEMR.EMRValue AS DECIMAL(7,2)) - CAST(DWHValue AS DECIMAL(7,2)))
                    /CAST(NULLIF(LatestEMR.EMRValue, 0)  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_EMR_DWH
                from LatestEMR
                left join LatestDWH on LatestDWH.facilityCode=LatestEMR.facilityCode
                left join DHIS2_TxNew on CONVERT (varchar,LatestEMR.facilityCode)=DHIS2_TxNew.SiteCode COLLATE Latin1_General_CI_AS";
        $query_hts_pos = "With NDW AS (SELECT
            Row_Number () over (partition by FacilityCode order by statusDate desc) as Num,
                facilityCode
                ,facilityName
                ,[value]
                ,statusDate
                ,indicatorDate
            FROM livesync.dbo.indicator
            where stage like '%DWH' and name like '%HTS_TESTED_POS' and statusDate>= '2022-10-06' 
            ),
            EMR As (SELECT
            Row_Number () over (partition by FacilityCode order by statusDate desc) as Num,
                facilityCode
                ,facilityName
                ,[value]
                ,statusDate
                ,indicatorDate
            FROM livesync.dbo.indicator
            where stage like '%EMR' and name like '%HTS_TESTED_POS' and indicatorDate= EOMONTH(DATEADD(mm,-2,GETDATE()))
            ),
            DHIS2_TxNew AS (
                SELECT
                    [SiteCode],
                    [FacilityName],
                    [County],
                    [Positive_Total],
                    ReportMonth_Year
                FROM [All_Staging_2016_2].[dbo].[FACT_HTS_DHIS2]
                WHERE ReportMonth_Year = ".Carbon::now()->subMonth(2)->format('Ym')."
            ),
            LatestDWH AS (Select
                    NDW.facilityCode 
                ,NDW.facilityName
                ,CONVERT (varchar,NDW.[value] ) As DWHValue
                ,NDW.statusDate
                ,NDW.indicatorDate
                from NDW
                where Num=1
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
                    coalesce (LatestDWH.facilityCode,LatestEMR.facilityCode ) As MFLCode,
                    Coalesce (LatestDWH.facilityName,LatestEMR.facilityName) As FacilityName,
                    --LatestDWH.CTPartner,
                    --NDW.County,
                    DHIS2_TxNew.Positive_Total As KHIS_Value,
                    LatestDWH.DWHValue AS DWHValue,
                    LatestEMR.EMRValue As EMRValue,
                    DHIS2_TxNew.Positive_Total-DWHValue As DiffKHISDWH,
                    DHIS2_TxNew.Positive_Total-LatestEMR.EMRValue As DiffKHISEMR,
                CAST(ROUND((CAST(DHIS2_TxNew.Positive_Total AS DECIMAL(7,2)) - CAST(DWHValue AS DECIMAL(7,2)))
                    /CAST(DHIS2_TxNew.Positive_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_DWH,
                    CAST(ROUND((CAST(DHIS2_TxNew.Positive_Total AS DECIMAL(7,2)) - CAST(LatestEMR.EMRValue AS DECIMAL(7,2)))
                    /CAST(DHIS2_TxNew.Positive_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_EMR,
                    CAST(ROUND((CAST(LatestEMR.EMRValue AS DECIMAL(7,2)) - CAST(DWHValue AS DECIMAL(7,2)))
                    /CAST(NULLIF(LatestEMR.EMRValue, 0)  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_EMR_DWH
                from LatestEMR
                left join LatestDWH on LatestDWH.facilityCode=LatestEMR.facilityCode
                left join DHIS2_TxNew on CONVERT (varchar,LatestEMR.facilityCode)=DHIS2_TxNew.SiteCode COLLATE Latin1_General_CI_AS";
        
        $query_index_pos = "Select * from latest_facility_metrics_vw where IndicatorName = 'HTS_INDEX_POS'";
        $query_retention_art_vl_1000 = "Select * from latest_facility_metrics_vw where IndicatorName = 'RETENTION_ON_ART_VL_1000_12_MONTHS'";
        $query_retention_art_vl = "SELECT * from latest_facility_metrics_vw where IndicatorName = 'RETENTION_ON_ART_12_MONTHS'";
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        $table = DB::connection('sqlsrv')->select(DB::raw($query_txcurr));
        $table2 = DB::connection('sqlsrv')->select(DB::raw($query_txnew));
        $table3 = DB::connection('sqlsrv')->select(DB::raw($query_hts_tested));
        $table3 = DB::connection('sqlsrv')->select(DB::raw($query_hts_pos));

        $index_pos = $this->GetDataFromDB($query_index_pos, 'mysql', 'sentry');
        $retention_art_vl_1000 = $this->GetDataFromDB($query_retention_art_vl_1000, 'mysql', 'sentry');
        $retention_art_vl = $this->GetDataFromDB($query_retention_art_vl, 'mysql', 'sentry');
        Excel::store(new TriangulationExport([$index_pos, $retention_art_vl, $retention_art_vl_1000]), 'fileout_Triangulation_'.$reportingMonth.'.xlsx');


        $jsonDecoded = json_decode(json_encode($table), true); 
        $fh = fopen(__DIR__ .'/../../../storage/fileout_Triangulation_TxCurr'.$reportingMonth.'.csv', 'w');
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
        
        $jsonDecoded = json_decode(json_encode($table2), true); 
        $fh = fopen(__DIR__ .'/../../../storage/fileout_Triangulation_TxNew'.$reportingMonth.'.csv', 'w');
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
        
        $jsonDecoded = json_decode(json_encode($table3), true); 
        $fh = fopen(__DIR__ .'/../../../storage/fileout_Triangulation_HTSTEST'.$reportingMonth.'.csv', 'w');
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
        
        $jsonDecoded = json_decode(json_encode($table3), true); 
        $fh = fopen(__DIR__ .'/../../../storage/fileout_Triangulation_HTSPOS'.$reportingMonth.'.csv', 'w');
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

        if($email = "Test") {
            $emails = EmailContacts::where('is_main', 1 )->where('list_subscribed', 'Triangulation')->pluck('email')->toArray(); 
            
            foreach ($emails as $test){
                $unsubscribe_url = str_replace(
                    '{{email}}', $test,
                    nova_get_setting(nova_get_setting('production') ? 'email_unsubscribe_url' : 'email_unsubscribe_url_staging')
                );

                // Send the email
                Mail::send('reports.partner.triangulation',
                    ['unsubscribe_url' => $unsubscribe_url],
                    function ($message) use (&$fh, &$reportingMonth, &$test) {
                        // email configurations
                        $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                        // email address of the recipients
                        $message->to(['charles.bett@thepalladiumgroup.com'])->subject('Data Triangulation Report');
                        // attach the csv file
                        $message->attach(__DIR__ .'/../../../storage/fileout_Triangulation_TxCurr'.$reportingMonth.'.csv');
                        $message->attach(__DIR__ .'/../../../storage/fileout_Triangulation_TxNew'.$reportingMonth.'.csv');
                        $message->attach(__DIR__ .'/../../../storage/fileout_Triangulation_HTSTEST'.$reportingMonth.'.csv');
                        $message->attach(__DIR__ .'/../../../storage/fileout_Triangulation_HTSPOS'.$reportingMonth.'.csv');
                        $message->attach(__DIR__ .'/../../../storage/app/fileout_Triangulation_'.$reportingMonth.'.xlsx');
                    });
            }
            return "DONE";
        } else {
            $unsubscribe_url = str_replace(
                    '{{email}}', "",
                    nova_get_setting(nova_get_setting('production') ? 'email_unsubscribe_url' : 'email_unsubscribe_url_staging')
                );

            // Send the email
            // PalladiumServiceDesk@grmfutures.onmicrosoft.com
            Mail::send('reports.partner.triangulation',
                ['unsubscribe_url' => $unsubscribe_url],
                function ($message) use (&$fh, &$reportingMonth) {
                    // email configurations
                    $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                    // email address of the recipients
                    $message->to(["charles.bett@thepalladiumgroup.com"])->subject('Data Triangulation Report');
                    $message->cc(["mary.gikura@thepalladiumgroup.com", "nobert.mumo@thepalladiumgroup.com", "charles.bett@thepalladiumgroup.com"]);
                    // attach the csv file
                    $message->attach(__DIR__ .'/../../../storage/fileout_Triangulation_TxCurr'.$reportingMonth.'.csv');
                    $message->attach(__DIR__ .'/../../../storage/fileout_Triangulation_TxNew'.$reportingMonth.'.csv');
                    $message->attach(__DIR__ .'/../../../storage/fileout_Triangulation_HTSTEST'.$reportingMonth.'.csv');
                    $message->attach(__DIR__ .'/../../../storage/fileout_Triangulation_HTSPOS'.$reportingMonth.'.csv');
                    $message->attach(__DIR__ .'/../../../storage/app/fileout_Triangulation_'.$reportingMonth.'.xlsx');
                });
            return "DONE";

        }
    }

    public function NUPIAlert($email)
    {
        // Get previous Month and Year
        $reportingMonth = Carbon::now()->subMonth()->format('M_Y');
        $query = "with facilities_list as (
                select 
                    distinct 
                    FacilityName,
                    MFLCode,
                    CTPartner,
                    FileName as EMR
                from PortalDev.dbo.Fact_Trans_New_Cohort    
            ),
            khis as (
                select
                    facilities_list.CTPartner as SDP,
                    facilities_list.EMR,
                    facilities_list.FacilityName,
                    facilities_list.MFLCode,
                    sum(CurrentOnART_Total) as TXCurr_khis
                from All_Staging_2016_2.dbo.FACT_CT_DHIS2 as khis
                inner join facilities_list on cast(facilities_list.MFLCode as nvarchar)= cast(khis.SiteCode as nvarchar)  collate Latin1_General_CI_AS
                where ReportMonth_Year = '".Carbon::now()->subMonth(2)->format('Ym')."'
                group by 
                    facilities_list.CTPartner, 
                    facilities_list.EMR, 
                    facilities_list.FacilityName, 
                    facilities_list.MFLCode
            ),
            nupi as (
            select 
                distinct PatientID as client_upn,
                replace(ccc_no, '-' , '') as nupi_no,
                FileName as EMR_Type,
                FacilityName as Facility,
                MFLCode,
                CTPartner as SDIP,
                CTAgency as Agency,
                ARTOutcome as ARTOutcomeJuly2022
            from tmp_and_adhoc.dbo.nupi_dataset_20220826 
            inner join PortalDev.dbo.Fact_Trans_New_Cohort as cohort on  replace(ccc_no, '-' , '') = cohort.PatientID
                and nupi_dataset_20220826.origin_facility_kmfl_code = cohort.MFLCode
            ),
            nupi_by_facility as (
            select 
                    SDIP,
                    EMR_Type,
                    Facility,
                    MFLCode,
                    count(*) as clients_with_nupi
                from nupi
                group by 
                    SDIP, 
                    EMR_Type, 
                    Facility, 
                    MFLCode
            )
            select
                coalesce(khis.FacilityName,nupi_by_facility.Facility) as Facility,
                coalesce(khis.MFLCode,nupi_by_facility.MFLCode) as MFLCode,
                coalesce(khis.SDP, nupi_by_facility.SDIP) as SDIP,
                coalesce(khis.EMR, nupi_by_facility.EMR_Type) as EMR,
                sum(TXCurr_khis) as TXCurr_khis,
                sum(clients_with_nupi) as clients_with_nupi,
                round(cast(sum(clients_with_nupi) as float) / cast(sum(TXCurr_khis) as float), 2) * 100 as proportion_with_nupi_no
            from nupi_by_facility 
            full join khis on nupi_by_facility.SDIP = khis.SDP
                and nupi_by_facility.EMR_Type = khis.EMR 
                and nupi_by_facility.MFLCode =  khis.MFLCode
            group by 
                coalesce(khis.FacilityName,nupi_by_facility.Facility) ,
                coalesce(khis.MFLCode,nupi_by_facility.MFLCode),
                coalesce(khis.SDP, nupi_by_facility.SDIP),
                coalesce(khis.EMR, nupi_by_facility.EMR_Type)
            order by round(cast(sum(clients_with_nupi) as float) / cast(sum(TXCurr_khis) as float), 2) desc;";
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        $table = DB::connection('sqlsrv')->select(DB::raw($query));
        // $table = [];

        $jsonDecoded = json_decode(json_encode($table), true); 
        $fh = fopen(__DIR__ .'/../../../storage/fileout_NUPI_'.$reportingMonth.'.csv', 'w');
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

        if($email = "Test") {
            $emails = EmailContacts::where('is_main', 1 )->where('list_subscribed', 'Paeds')->pluck('email')->toArray(); 
            
            foreach ($emails as $test){
                $unsubscribe_url = str_replace(
                    '{{email}}', $test,
                    nova_get_setting(nova_get_setting('production') ? 'email_unsubscribe_url' : 'email_unsubscribe_url_staging')
                );

                // Send the email
                Mail::send('reports.partner.reports',
                    ['unsubscribe_url' => $unsubscribe_url],
                    function ($message) use (&$fh, &$reportingMonth, &$test) {
                        // email configurations
                        $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                        // email address of the recipients
                        $message->to([$test])->subject('NUPI Report');
                        
                        // attach the csv covid file
                        $message->attach(__DIR__ .'/../../../storage/fileout_NUPI_'.$reportingMonth.'.csv');
                    });
            }
            return "DONE";
        }else {
            $unsubscribe_url = str_replace(
                    '{{email}}', '',
                    nova_get_setting(nova_get_setting('production') ? 'email_unsubscribe_url' : 'email_unsubscribe_url_staging')
                );

            // Send the email
            Mail::send('reports.partner.reports',
                ['unsubscribe_url' => $unsubscribe_url],
                function ($message) use (&$fh, &$reportingMonth) {
                    // email configurations
                    $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                    // email address of the recipients
                    $message->to(["charles.bett@thepalladiumgroup.com"])->subject('NUPI Report');
                    $message->cc(["charles.bett@thepalladiumgroup.com"]);
                    // attach the csv covid file
                    $message->attach(__DIR__ .'/../../../storage/fileout_NUPI_'.$reportingMonth.'.csv');
                });
            return "DONE";

        }
    }

    public function EHealthAssessment($email)
    {
        $query = '';

        SendEmail();
    }

    public function GenerateSDPTXCurrReport($partner)
    {
        // Get previous Month and Year
        $reportingMonth = Carbon::now()->subMonth()->format('M_Y');

        $query = "With NDW_CurTx AS (
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
                    DHIS2_CurTx.CurrentOnART_Total-CurTx_total As DiffKHISDWH,
                    DHIS2_CurTx.CurrentOnART_Total-LatestEMR.EMRValue As DiffKHISEMR,
                CAST(ROUND((CAST(DHIS2_CurTx.CurrentOnART_Total AS DECIMAL(7,2)) - CAST(NDW_CurTx .CurTx_total AS DECIMAL(7,2)))
                    /CAST(DHIS2_CurTx.CurrentOnART_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_DWH,
                    CAST(ROUND((CAST(DHIS2_CurTx.CurrentOnART_Total AS DECIMAL(7,2)) - CAST(LatestEMR.EMRValue AS DECIMAL(7,2)))
                    /CAST(DHIS2_CurTx.CurrentOnART_Total  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_KHIS_EMR,
                    CAST(ROUND((CAST(LatestEMR.EMRValue AS DECIMAL(7,2)) - CAST(NDW_CurTx .CurTx_total AS DECIMAL(7,2)))
                    /CAST(NULLIF(LatestEMR.EMRValue, 0)  AS DECIMAL(7,2))* 100, 2) AS float) AS Percent_variance_EMR_DWH
                from NDW_CurTx
                left join LatestEMR on NDW_CurTx.MFLCode=LatestEMR.facilityCode
                left join DHIS2_CurTx on NDW_CurTx.MFLCode=DHIS2_CurTx.SiteCode COLLATE Latin1_General_CI_AS
                where NDW_CurTx.CTPartner = '".$partner."'";
        
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        $table = DB::connection('sqlsrv')->select(DB::raw($query));

        $jsonDecoded = json_decode(json_encode($table), true); 
        $fh = fopen(__DIR__ .'/../../../storage/fileout_Triangulation_TXCURR_'.$reportingMonth.$partner.'.csv', 'w');
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

        return ;
    }

    public function SendEmail($report, $recepients, $cc = [], $attachments = [], $unsubscribe_url = '')
    {
        Mail::send($report,
            ['unsubscribe_url' => $unsubscribe_url],
            function ($message) use (&$fh, &$recepients, &$cc, &$attachments) {
                // email configurations
                $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                // email address of the recipients
                $message->to($recepients)->subject('NUPI Report');
                $message->cc($cc);
                // attach the file
                foreach($attachments as $attach)
                    $message->attach($attach);
            });
    }

    public function CreateCSV($name, $data){
        
        $reportingMonth = Carbon::now()->subMonth()->format('M_Y');
        $jsonDecoded = json_decode(json_encode($data), true); 
        $fh = fopen($name, 'w');
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
    }

    public function GetDataFromDB($query = '', $connection = 'sqlsrv', $db = ''){
        if ($connection == 'mysql'){
            config(['database.connections.mysql.database' => $db]);
        } else if ($connection == 'mssql'){
            config(['database.connections.sqlsrv.database' => $db]);
        }
        return DB::connection($connection)->select(DB::raw($query));
    }
}
