<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DisableConstraints implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600;

    protected $databaseName;

    public function __construct($databaseName)
    {
        $this->databaseName = $databaseName;
    }

    public function handle()
    {
        config(['database.connections.sqlsrv.database' => $this->databaseName]);
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_lkp_SiteAbstractionDate_SiteCode ON lkp_SiteAbstractionDate DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_ARTPatients ON stg_ARTPatients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX__ARTPatients_PatientId_PatientPK_SiteCode ON stg_ARTPatients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_ARTPatients__PatientPK_PatientID_SiteCode ON stg_ARTPatients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_ARTPatients_StartARTDate_[PatientPK_PatientID_SiteCode_FacilityName_RegistrationDate_LastARTDate_Emr_Project ON stg_ARTPatients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_Stg_ARTPatients_FacilityName_SiteCode ON stg_ARTPatients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stgARTPatients_EMR_PatientPK_PatientID ON stg_ARTPatients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_PatientLabs_TestName ON stg_PatientLabs DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_patinets_PatientID_PatientPk_SiteCode_OrderedbyDate_ReportedbyDate_TestResult ON stg_PatientLabs DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_PatientLabs_FacilityName_PatientID_PatientPk_SiteCode_OrderedbyDate ON stg_PatientLabs DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_PatientPharmacy_EMR_Project ON stg_PatientPharmacy DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_PatientPharmacy_EMR_Project_SiteCode ON stg_PatientPharmacy DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_Stg_Pharmacy_TreatmentType ON stg_PatientPharmacy DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_PatientPharmacy_PatientID_SiteCode_PatientPK_VisitID_EMR ON stg_PatientPharmacy DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_Pharmacy_PatientID_SiteCode-PatientPk_VisitID_EMR_DispenseDate_ExpectedReturn ON stg_PatientPharmacy DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_PatientPharmacy_DispenseDate__ExpectedReturn_Emr ON stg_PatientPharmacy DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX__stg_PatientPharmacy__DispenseDate_TreatmentType__ExpectedReturn ON stg_PatientPharmacy DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_Patients ON stg_Patients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_Patients_EMR_Poject ON stg_Patients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_Stg_Patients_PatientId_PatientPK_SiteCode_Emr_Project ON stg_Patients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_Stg_Patients_FacilityName_SiteCode ON stg_Patients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IS_stgPatients_EMR_PatientId_PatientPk ON stg_Patients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_PatientVisits_PatientPK ON stg_PatientVisits DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_PatientVisits_EMR_Project ON stg_PatientVisits DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_PatientVisits_SiteCode_Emr_Project ON stg_PatientVisits DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX__stg_PatientVisits_Pregnant ON stg_PatientVisits DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_Stg_PatientVisits_FacilityName_PatientID_SiteCode_PatientPK_VisitDate ON stg_PatientVisits DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_Stg_PatientVisits_VisitDate_PatientD_PatientPk ON stg_PatientVisits DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_PatientVisits_PatientID_SiteCode_PatientPK_VisitID_NextAppointmentDate_Emr ON stg_PatientVisits DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX__stg_PatientVisits_Pregnant_Check ON stg_PatientVisits DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX__stg_PatientVisits__VisitDate__SiteCode_Emr_Project ON stg_PatientVisits DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_VisitDate_PatientID_SiteCode_PatientPK_VisitID_Emr__VisitDate ON stg_PatientVisits DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX__stg_PatientVisits__VisitDate_FacilityName_SiteCode_NextAppointmentDate ON stg_PatientVisits DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_PatientStatus_PatientID_PatientPK ON stg_PatientStatus DISABLE");
        // DB::connection('sqlsrv')->statement("ALTER INDEX lkp_VisitDates_StartARTDate ON lkp_VisitDates DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_DimFacilities_FacilityName ON DimFacilities DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_Stg_PatientLabs_ProjectON stg_PatientLabs DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_PatientStatus_Project ON stg_PatientStatus DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_PatientsWABWHOCD4_Project ON stg_PatientsWABWHOCD4 DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_ARTPatients_Project ON stg_ARTPatients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_stg_Patients_project ON stg_Patients DISABLE");
        DB::connection('sqlsrv')->statement("ALTER INDEX IX_Stg_PatientVisits_project ON stg_PatientVisits DISABLE");
    }
}
