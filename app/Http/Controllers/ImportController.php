<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\NUPI;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportController extends Controller
{
    public function getImport()
    {
        ini_set('upload_max_filesize', -1);
        ini_set('post_max_size', -1);
        return view('importnupi');
    }

    public function parseImport(CsvImportRequest $request)
    {

        $path = $request->file('csv_file')->getRealPath();

        if ($request->has('header')) {
            $data = Excel::toArray([], $path );
        } else {
            $data = array_map('str_getcsv', file($path));
        }

        if (count($data) > 0) {
            if ($request->has('header')) {
                $csv_header_fields = $data[0][0];
                // foreach ( as $key) {
                //     $csv_header_fields[] = $key;
                // }
            }
            $csv_data = array_slice($data[0], 0, 2);

            //CsvData::create([
            //     'csv_filename' => $request->file('csv_file')->getClientOriginalName(),
            //     'csv_header' => $request->has('header'),
            //     'csv_data' => json_encode($data)
            // ]);
        } else {
            return redirect()->back();
        }
        // dd($csv_header_fields);

        return view('import_fields', compact( 'csv_header_fields', 'csv_data', 'csv_data_file'));

    }

    public function processImport(Request $request)
    {
        ini_set('upload_max_filesize', -1);
        ini_set('post_max_size', -1);
        $path = $request->file('csv_file')->getRealPath();

        if ($request->has('header')) {
            $data = Excel::toArray([], $path );
        } else {
            return redirect()->back();
            $data = array_map('str_getcsv', file($path));
        }

        if (count($data[0]) > 0) {
            if ($request->has('header')) {
                $csv_header_fields = $data[0][0];
            }
            $csv_data = array_shift($data[0]);
        
            $archive_db = "SELECT * INTO tmp_and_adhoc.dbo.nupi_dataset_" .Carbon::now()->format('dMY'). " FROM tmp_and_adhoc.dbo.nupi_dataset; DELETE FROM tmp_and_adhoc.dbo.nupi_dataset;" ;
            $import_data = "";

            config(['database.connections.sqlsrv.database' => 'tmp_and_adhoc']);
            DB::connection('sqlsrv')->raw($archive_db);

            foreach ($data[0] as $d) {
                config(['database.connections.sqlsrv.database' => 'nupi_dataset']);
                DB::connection('sqlsrv')->table('tmp_and_adhoc.dbo.nupi_dataset')->insert([
                    'ccc_no'=>$d[0], 
                    'county'=>$d[1], 
                    'sub_county'=>$d[2], 
                    'origin_facility_kmfl_code'=>$d[3], 
                    'facility'=>$d[4], 
                    'gender'=>$d[5], 
                    'client_number'=>$d[6], 
                    'created_by'=>$d[7], 
                    'date_created'=>$d[8], 
                    'date_of_initiation'=>$d[9], 
                    'treatment_outcome'=>$d[10], 
                    'date_of_last_encounter'=>$d[11], 
                    'date_of_last_viral_load'=>$d[12], 
                    'date_of_next_appointment'=>$d[13], 
                    'last_regimen'=>$d[14], 
                    'last_regimen_line'=>$d[15], 
                    'current_on_art'=>$d[16], 
                    'date_of_hiv_diagnosis'=>$d[17], 
                    'last_viral_load_result'=>$d[18]
                    ]);
            }

        } else {
            return redirect()->back();
        }

        return view('import_success');
    }
    
}


class CsvImportRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'csv_file' => 'required|file'
        ];
    }
}