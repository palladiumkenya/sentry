<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\NUPI;
use Maatwebsite\Excel\Facades\Excel;

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

            $csv_data_file = "1";
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
            foreach ($csv_data as $row) {
                $contact = new Contact();
                foreach (config('app.db_fields') as $index => $field) {
                    $contact->$field = $row[$request->fields[$index]];
                }
                $contact->save();
            }

            //CsvData::create([
            //     'csv_filename' => $request->file('csv_file')->getClientOriginalName(),
            //     'csv_header' => $request->has('header'),
            //     'csv_data' => json_encode($data)
            // ]);
        } else {
            return redirect()->back();
        }
        // $data = NUPI::find($request->csv_data_file_id);
        $csv_data = json_decode($data->csv_data, true);
        foreach ($csv_data as $row) {
            $contact = new Contact();
            foreach (config('app.db_fields') as $index => $field) {
                $contact->$field = $row[$request->fields[$index]];
            }
            $contact->save();
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