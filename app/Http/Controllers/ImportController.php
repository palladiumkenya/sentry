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
        ini_set('max_execution_time', -1);
        ini_set('memory_limit', '4096M');
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
        ini_set('max_execution_time', -1);
        ini_set('memory_limit', '4096M');
        
        $path = $request->file('csv_file')->getRealPath();
        $file = fopen($path, "r");
        $i = 0;

        $archive_db = "SELECT * INTO tmp_and_adhoc.dbo.nupi_dataset_" .Carbon::now()->format('dmY_His'). " FROM tmp_and_adhoc.dbo.nupi_dataset" ;

        try{
            config(['database.connections.sqlsrv.database' => 'tmp_and_adhoc']);
            DB::connection('sqlsrv')->select(DB::raw($archive_db));
        } catch (\Exception $e) {
            //throw $th;
        }
        config(['database.connections.sqlsrv.database' => 'tmp_and_adhoc']);
        DB::connection('sqlsrv')->table("tmp_and_adhoc.dbo.nupi_dataset")->truncate();
        
        //Read the contents of the uploaded file 
        while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
            $num = count($filedata);
            // Skip first row (Remove below comment if you want to skip the first row)
            if ($i == 0) {
                $i++;
                continue;
            }
            
            config(['database.connections.sqlsrv.database' => 'nupi_dataset']);
            DB::connection('sqlsrv')->table('tmp_and_adhoc.dbo.nupi_dataset')->insert([
                'ccc_no'=>$filedata[0], 
                'origin_facility_kmfl_code'=>$filedata[1], 
                'client_number'=>$filedata[2],
                'date_created'=>$filedata[3], 

                // 'county'=>$d[0], 
                // 'sub_county'=>$d[], 
                // 'facility'=>$d[4], 
                // 'gender'=>$d[5],  
                // 'created_by'=>$d[7], 
                // 'date_of_initiation'=>$d[9], 
                // 'treatment_outcome'=>$d[10], 
                // 'date_of_last_encounter'=>$d[11], 
                // 'date_of_last_viral_load'=>$d[12], 
                // 'date_of_next_appointment'=>$d[13], 
                // 'last_regimen'=>$d[14], 
                // 'last_regimen_line'=>$d[15], 
                // 'current_on_art'=>$d[16], 
                // 'date_of_hiv_diagnosis'=>$d[17], 
                // 'last_viral_load_result'=>$d[18]
                ]);
            $i++;
        }
        fclose($file); //Close after reading
        $j = 0;
        
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