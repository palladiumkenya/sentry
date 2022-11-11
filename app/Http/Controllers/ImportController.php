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
        
            $archive_db = "SELECT * INTO PortalDev.dbo.FACT_NUPI_" .Carbon::now()->format('dMY'). " FROM PortalDev.dbo.FACT_NUPI; DELETE FROM PortalDev.dbo.FACT_NUPI;" ;
            $import_data = "";

            config(['database.connections.sqlsrv.database' => 'PortalDev']);
            DB::connection('sqlsrv')->raw($archive_db);

            foreach ($data[0] as $d) {
                config(['database.connections.sqlsrv.database' => 'PortalDev']);
                DB::connection('sqlsrv')->table('PortalDev.dbo.FACT_NUPI')->insert([
                    'MFLCode'=>$d[5],
                    'FacilityName'=>$d[0],
                    'County'=>$d[2],
                    'Subcounty'=>$d[3],
                    'CTPartner'=>$d[1],
                    'CTAgency'=>$d[4],
                    'NumNUPI'=>$d[6],
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