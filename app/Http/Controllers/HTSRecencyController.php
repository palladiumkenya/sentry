<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HTSRecencyController extends Controller
{
    public function PullHtsRecency()
    {
        ini_set('upload_max_filesize', -1);
        ini_set('post_max_size', -1);
        ini_set('max_execution_time', -1);
        ini_set('memory_limit', '8192M');
        $query_dim_facility = "select * from [HTSRecencyDump].[base].[dim_facility]";
        $query_dim_age_group = "select * from [HTSRecencyDump].[base].[dim_age_group]";
        $query_dim_modality = "select * from [HTSRecencyDump].[base].[dim_modality]";
        $query_dim_rslrf_client = "select * from [HTSRecencyDump].[base].[dim_rslrf_client]";
        $query_dim_rtri_lab = "select * from [HTSRecencyDump].[base].[dim_rtri_lab]";
        $query_dim_sex = "select * from [HTSRecencyDump].[base].[dim_sex]";
        $query_dim_viral_load_lab = "select * from [HTSRecencyDump].[base].[dim_viral_load_lab]";
        $query_fact_rslrf_recency_test = "select * from [HTSRecencyDump].[base].[fact_rslrf_recency_test]";

        config(['database.connections.sqlsrv60.database' => 'HTSRecencyDump']);
        $facility = DB::connection('sqlsrv60')->select($query_dim_facility);
        DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_facility")->truncate();
        foreach($facility as $record){
            config(['database.connections.sqlsrv.database' => 'Data request palantir']);
            DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_facility")->insert(get_object_vars($record));
        }

        config(['database.connections.sqlsrv60.database' => 'HTSRecencyDump']);
        $age_group = DB::connection('sqlsrv60')->select($query_dim_age_group);
        DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_age_group")->truncate();
        foreach($age_group as $record){
            config(['database.connections.sqlsrv.database' => 'Data request palantir']);
            DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_age_group")->insert(get_object_vars($record));
        }

        config(['database.connections.sqlsrv60.database' => 'HTSRecencyDump']);
        $modality = DB::connection('sqlsrv60')->select($query_dim_modality);
        DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_modality")->truncate();
        foreach($modality as $record){
            config(['database.connections.sqlsrv.database' => 'Data request palantir']);
            DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_modality")->insert(get_object_vars($record));
        }

        config(['database.connections.sqlsrv60.database' => 'HTSRecencyDump']);
        $rslrf_client = DB::connection('sqlsrv60')->select($query_dim_rslrf_client);
        DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_rslrf_client")->truncate();
        foreach($rslrf_client as $record){
            config(['database.connections.sqlsrv.database' => 'Data request palantir']);
            DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_rslrf_client")->insert(get_object_vars($record));
        }

        config(['database.connections.sqlsrv60.database' => 'HTSRecencyDump']);
        $rtri_lab = DB::connection('sqlsrv60')->select($query_dim_rtri_lab);
        DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_rtri_lab")->truncate();
        foreach($rtri_lab as $record){
            config(['database.connections.sqlsrv.database' => 'Data request palantir']);
            DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_rtri_lab")->insert(get_object_vars($record));
        }

        config(['database.connections.sqlsrv60.database' => 'HTSRecencyDump']);
        $sex = DB::connection('sqlsrv60')->select($query_dim_sex);
        DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_sex")->truncate();
        foreach($sex as $record){
            config(['database.connections.sqlsrv.database' => 'Data request palantir']);
            DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_sex")->insert(get_object_vars($record));
        }

        config(['database.connections.sqlsrv60.database' => 'HTSRecencyDump']);
        $viral_load_lab = DB::connection('sqlsrv60')->select($query_dim_viral_load_lab);
        DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_viral_load_lab")->truncate();
        foreach($viral_load_lab as $record){
            config(['database.connections.sqlsrv.database' => 'Data request palantir']);
            DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_dim_viral_load_lab")->insert(get_object_vars($record));
        }

        config(['database.connections.sqlsrv60.database' => 'HTSRecencyDump']);
        $rslrf_recency_test = DB::connection('sqlsrv60')->select($query_fact_rslrf_recency_test);
        DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_fact_rslrf_recency_test")->truncate();
        foreach($rslrf_recency_test as $record){
            config(['database.connections.sqlsrv.database' => 'Data request palantir']);
            DB::connection('sqlsrv')->table("Data request palantir.dbo.HTSRecencyDump_fact_rslrf_recency_test")->insert(get_object_vars($record));
        }

    }
}
