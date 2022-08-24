<?php

namespace App\Http\Controllers;


use App\Models\Facility;
use App\Models\EtlJob;
use App\Models\Partner;
use App\Models\PartnerMetric;

use App\Jobs\GenerateFacilityMetricsReport;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

use Carbon\Carbon;

class MainController extends Controller
{
    //
    public function DQAReport()
    {
        $etlJob =  EtlJob::find(\DB::table('etl_jobs')->max('id'));
        $partners = Partner::get();
        PartnerMetric::truncate();

        foreach ($partners as $partner) {
            GenerateFacilityMetricsReport::dispatchNow($etlJob, $partner);
        }
        return;
    }

    public function PeadAlert()
    {
        $query = "with otz_10_19_yrs as (
            select
                MFLCode,
                FacilityName,
                CTPartner,
                County,
                count(*) as no_otz_10_19_yrs
            from PortalDev.dbo.FACT_Trans_OTZEnrollments
            where TXCurr = 1
                and CTAgency = 'CDC'
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
            where ARTOutcome = 'V'
                and CTAgency = 'CDC'
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
                and ARTOutcome = 'V'
                and CTAgency = 'CDC'
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
                and ARTOutcome = 'V'
                and CTAgency = 'CDC'
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
                row_number() over (partition by PatientPK, PatientID, SiteCode order by VisitDate desc) as rank
            from All_Staging_2016_2.dbo.stg_PatientVisits
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
            where rank = 1
        ),
        second_latest_visit as (
            select
                *
            from visit_weight_and_height_ordering
            where rank = 2
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
            where CTAgency = 'CDC'
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
        where ageLV between 0 and 19  and CTAgency ='CDC' and ARTOutcome='V'
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
        where ageLV >=15 and CTAgency ='CDC' and ARTOutcome='V' and Gender='Female'
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
            Count (*)PaedsListed
        FROM [All_Staging_2016_2].[dbo].[stg_ContactListing] listing 
        inner join PortalDev.dbo.Fact_Trans_New_Cohort Cohort on
        listing.PatientID=Cohort.PatientID and
        listing.PatientPK=Cohort.PatientPK and
        listing.SiteCode=Cohort.MFLCode
        where ContactAge<15
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
            Count (*)PaedsTested
        FROM [All_Staging_2016_2].[dbo].[stg_ContactListing] listing 
        inner join PortalDev.dbo.Fact_Trans_New_Cohort Cohort on
        listing.PatientID=Cohort.PatientID and
        listing.PatientPK=Cohort.PatientPK and
        listing.SiteCode=Cohort.MFLCode
        inner join All_Staging_2016_2.dbo.stg_hts_ClientTests tests on
        listing.ContactPatientPK=tests.PatientPk and
        listing.SiteCode=tests.SiteCode
        where ContactAge<15
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
                and ARTOutcome = 'V'
                and CTAgency = 'CDC'
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
            coalesce(ovc_0_17_yrs.no_ovc_0_17_yrs, 0) as no_ovc_0_17_yrs
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
        Left join PaedsOnMMD on facility_partner_combinations.MFLCode=PaedsOnMMD.MFLCode";
        
        $query2 = "SELECT * from (Select Distinct df.FacilityId,Name as FacilityName,County,subCounty,Agency,Partner, f.year,f.month, f.docketId ,f.timeId as uploaddate
                from (select name,facilityId,county,subcounty,agency,partner, \"CT\" AS docket from portaldev.dim_facility where isCt = 1) df
                LEFT JOIN (SELECT * FROM (
                            SELECT DISTINCT ROW_NUMBER ( ) OVER (PARTITION BY FacilityId,docketId,Concat(Month(fm.timeId),'-', Year(fm.timeId)) ORDER BY (cast(fm.timeId as date)) desc) AS RowID,
                            FacilityId,docketId,fm.timeId, dt.year,dt.month FROM  portaldev.fact_manifest fm
                            inner join portaldev.dim_time dt on dt.timeId=fm.timeId
                            where dt.year = ".Carbon::now()->subMonth()->format('Y')." and dt.month = ".Carbon::now()->subMonth()->format('m')."
                )u where RowId=1) f on f.facilityId=df.facilityId and df.docket=f.docketId) Y
                                WHERE uploaddate is null and Agency = 'CDC'";
        
        
        config(['database.connections.sqlsrv.database' => 'All_Staging_2016_2']);

        $table = DB::connection('sqlsrv')->select(DB::raw($query));
        // Get previous Month and Year
        $reportingMonth = Carbon::now()->subMonth()->format('M_Y_D');
        $jsonDecoded = json_decode(json_encode($table), true); 
        $fh = fopen('fileout_Paeds_'.$reportingMonth.'.csv', 'w');
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
        // Get previous Month and Year
        $jsonDecoded = json_decode(json_encode($fac_not_reporting), true); 
        $fh = fopen('fileout_FacilitiesNotReporting_'.$reportingMonth.'.csv', 'w');
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
        Mail::send('reports.partner.topline',
            [],
            function ($message) use (&$fh, &$reportingMonth) {
                // email configurations
                $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                // email address of the recipients
                $message->to(["kennedy.muthoka@thepalladiumgroup.com"])->subject('Paediatric Topline Indicators');
                // $message->cc(["npm1@cdc.gov", "mary.gikura@thepalladiumgroup.com", "kennedy.muthoka@thepalladiumgroup.com", "charles.bett@thepalladiumgroup.com", "Evans.Munene@thepalladiumgroup.com", "koske.kimutai@thepalladiumgroup.com"]);
                $message->cc(["mary.gikura@thepalladiumgroup.com", "nobert.mumo@thepalladiumgroup.com", "charles.bett@thepalladiumgroup.com"]);
                // attach the csv covid file
                $message->attach('fileout_Paeds_'.$reportingMonth.'.csv');
                $message->attach('fileout_FacilitiesNotReporting_'.$reportingMonth.'.csv');
            });
        return "DONE";
    }

    public function NUPIAlert()
    {
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

        // Send the email
        Mail::send('reports.partner.covid',
            [],
            function ($message) use (&$fh, &$reportingMonth) {
                // email configurations
                $message->from('dwh@mg.kenyahmis.org', 'NDWH');
                // email address of the recipients
                $message->to(["charles.bett@thepalladiumgroup.com"])->subject('NUPI Report');
                $message->cc(["charles.bett@thepalladiumgroup.com"]);
                // attach the csv covid file
                $message->attach('fileout_Covid_'.$reportingMonth.'.csv');
            });
        return "DONE";
    }
}
