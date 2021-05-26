DROP TABLE fact_trans_hts;
CREATE TABLE fact_trans_hts (
    mfl int,
    facility varchar(255),
    county varchar(255),
    sub_county varchar(255),
    partner varchar(255),
    agency varchar(255),
    project varchar(255),
    emr varchar(255),
    patient_pk varchar(255),
    encounter_id varchar(255),
    gender varchar(255),
    dob date,
    marital_status varchar(255),
    patient_disabled varchar(255),
    disability_type varchar(255),
    test_date date,
    age_at_testing int,
    population_type varchar(255),
    key_population_type varchar(255),
    ever_tested_for_hiv varchar(255),
    test_type varchar(255),
    test_type_name varchar(255),
    months_since_last_test int,
    months_since_last_test_category varchar(255),
    client_tested_as varchar(255),
    entry_point varchar(255),
    test_strategy varchar(255),
    test_result_1 varchar(255),
    test_result_2 varchar(255),
    final_test_result varchar(255),
    patient_given_result varchar(255),
    tb_screening varchar(255),
    client_self_tested varchar(255),
    couple_discordant varchar(255),
    consent varchar(255),
    date_enrolled date,
    reported_ccc_number varchar(255),
    tested int,
    positive int,
    linked int
);

CREATE INDEX fact_trans_hts_mfl_index ON fact_trans_hts (mfl);
CREATE INDEX fact_trans_hts_facility_index ON fact_trans_hts (facility);
CREATE INDEX fact_trans_hts_county_index ON fact_trans_hts (county);
CREATE INDEX fact_trans_hts_sub_county_index ON fact_trans_hts (sub_county);
CREATE INDEX fact_trans_hts_partner_index ON fact_trans_hts (partner);
CREATE INDEX fact_trans_hts_agency_index ON fact_trans_hts (agency);
CREATE INDEX fact_trans_hts_gender_index ON fact_trans_hts (gender);
CREATE INDEX fact_trans_hts_dob_index ON fact_trans_hts (dob);
CREATE INDEX fact_trans_hts_test_date_index ON fact_trans_hts (test_date);
