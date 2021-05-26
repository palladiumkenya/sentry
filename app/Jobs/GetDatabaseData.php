<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GetDatabaseData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        config(['database.connections.mysql.database' => 'portaldev']);
        DB::connection('mysql')->table('fact_trans_hts')
            ->selectRaw('
                SiteCode as mfl,
                FacilityName as facility,
                County as county,
                SubCounty as sub_county,
                CTPartner as partner,
                Agency as agency,
                project as project,
                Emr as emr,
                PatientsPK as patient_pk,
                EncounterId as encounter_id,
                Gender as gender,
                DOB as dob,
                MaritalStatus as marital_status,
                PatientDisabled as patient_disabled,
                DisabilityType as disability_type,
                TestDate as test_date,
                AgeAtTesting as age_at_testing,
                PopulationType as population_type,
                KeypopulationType as key_population_type,
                EverTestedForHiv as ever_tested_for_hiv,
                TestType as test_type,
                TestedBefore as test_type_name,
                MonthsSinceLastTest as months_since_last_test,
                MonthsLastTest as months_since_last_test_category,
                ClientTestedAs as client_tested_as,
                EntryPoint as entry_point,
                TestStrategy as test_strategy,
                TestResult1 as test_result_1,
                TestResult2 as test_result_2,
                FinalTestResult as final_test_result,
                PatientGivenResult as patient_given_result,
                tbScreening as tb_screening,
                ClientSelfTested as client_self_tested,
                CoupleDiscordant as couple_discordant,
                consent as consent,
                DateEnrolled as date_enrolled,
                ReportedCCCNumber as reported_ccc_number,
                Tested as tested,
                Positive as positive,
                Linked as linked
            ')
            ->cursor()->each(function ($row) {
                PostDatabaseData::dispatch((array) $row);
            });
    }
}
