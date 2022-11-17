<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function getImport()
    {
        ini_set('upload_max_filesize', -1);
        ini_set('post_max_size', -1);
        ini_set('max_execution_time', -1);
        ini_set('memory_limit', '4096M');
        return view('import');
    }

    public function parseImport(CsvImportRequest $request)
    {
        return view('import_success');

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
        
        config(['database.connections.sqlsrv.database' => 'tmp_and_adhoc']);
        $archived = DB::connection('sqlsrv')->statement($archive_db); 
        if (!$archived)
            return "Unable to archive nupi_dataset DB; try again later";
        config(['database.connections.sqlsrv.database' => 'tmp_and_adhoc']);
        DB::connection('sqlsrv')->table("tmp_and_adhoc.dbo.nupi_dataset")->truncate();
        
        $insert_data = collect();
        
        //Read the contents of the uploaded file 
        while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
            $num = count($filedata);
            // Skip first row (Remove below comment if you want to skip the first row)
            if ($i == 0) {
                $i++;
                continue;
            }
            
            config(['database.connections.sqlsrv.database' => 'nupi_dataset']);
            $insert_data->push([
                'ccc_no'=>$filedata[0], 
                'origin_facility_kmfl_code'=>$filedata[1], 
                'client_number'=>$filedata[2],
                'date_created'=>$filedata[3],
            ]);
            // DB::connection('sqlsrv')->table('tmp_and_adhoc.dbo.nupi_dataset')->insert([
            //     'ccc_no'=>$filedata[0], 
            //     'origin_facility_kmfl_code'=>$filedata[1], 
            //     'client_number'=>$filedata[2],
            //     'date_created'=>$filedata[3], 

            //     // 'county'=>$d[0], 
            //     // 'sub_county'=>$d[], 
            //     // 'facility'=>$d[4], 
            //     // 'gender'=>$d[5],  
            //     // 'created_by'=>$d[7], 
            //     // 'date_of_initiation'=>$d[9], 
            //     // 'treatment_outcome'=>$d[10], 
            //     // 'date_of_last_encounter'=>$d[11], 
            //     // 'date_of_last_viral_load'=>$d[12], 
            //     // 'date_of_next_appointment'=>$d[13], 
            //     // 'last_regimen'=>$d[14], 
            //     // 'last_regimen_line'=>$d[15], 
            //     // 'current_on_art'=>$d[16], 
            //     // 'date_of_hiv_diagnosis'=>$d[17], 
            //     // 'last_viral_load_result'=>$d[18]
            //     ]);
            $i++;
        }
        foreach ($insert_data->chunk(520) as $chunk)
        {
            DB::connection('sqlsrv')->table('tmp_and_adhoc.dbo.nupi_dataset')->insert($chunk->toArray());
        }
        fclose($file); //Close after reading
        
        return view('import_success');
    }
    
    /**
     * @return Application|Factory|View
     */
    public function uploadLargeFiles(Request $request)
    {
        ini_set('upload_max_filesize', -1);
        ini_set('post_max_size', -1);
        ini_set('max_execution_time', -1);
        ini_set('memory_limit', '4096M');
        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        if (!$receiver->isUploaded()) {
            // file not uploaded
        }

        $fileReceived = $receiver->receive(); // receive file
        if ($fileReceived->isFinished()) { // file uploading is complete / all chunks are uploaded
            $file2 = $fileReceived->getFile(); // get file
            $extension = $file2->getClientOriginalExtension();
            $fileName = str_replace('.'.$extension, '', $file2->getClientOriginalName()); //file name without extenstion
            $fileName .= '_' . md5(time()) . '.' . $extension; // a unique file name

            $disk = Storage::disk(config('filesystems.default'));
            $path = $disk->putFileAs('videos', $file2, $fileName);
            
        $dataPath = __DIR__ .'/../../../storage/app/' . $path;
        $file = fopen($dataPath, "r");
        $i = 0;

        $archive_db = "SELECT * INTO tmp_and_adhoc.dbo.nupi_dataset_" .Carbon::now()->format('dmY_His'). " FROM tmp_and_adhoc.dbo.nupi_dataset" ;
        
        config(['database.connections.sqlsrv.database' => 'tmp_and_adhoc']);
        $archived = DB::connection('sqlsrv')->statement($archive_db); 
        if (!$archived)
            return "Unable to archive nupi_dataset DB; try again later";
        config(['database.connections.sqlsrv.database' => 'tmp_and_adhoc']);
        DB::connection('sqlsrv')->table("tmp_and_adhoc.dbo.nupi_dataset")->truncate();
        
        $insert_data = collect();
        
        //Read the contents of the uploaded file 
        while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
            $num = count($filedata);
            // Skip first row (Remove below comment if you want to skip the first row)
            if ($i == 0) {
                $i++;
                continue;
            }
            
            config(['database.connections.sqlsrv.database' => 'nupi_dataset']);
            $insert_data->push([
                'ccc_no'=>$filedata[0], 
                'origin_facility_kmfl_code'=>$filedata[1], 
                'client_number'=>$filedata[2],
                'date_created'=>$filedata[3],
            ]);
            // DB::connection('sqlsrv')->table('tmp_and_adhoc.dbo.nupi_dataset')->insert([
            //     'ccc_no'=>$filedata[0], 
            //     'origin_facility_kmfl_code'=>$filedata[1], 
            //     'client_number'=>$filedata[2],
            //     'date_created'=>$filedata[3], 

            //     // 'county'=>$d[0], 
            //     // 'sub_county'=>$d[], 
            //     // 'facility'=>$d[4], 
            //     // 'gender'=>$d[5],  
            //     // 'created_by'=>$d[7], 
            //     // 'date_of_initiation'=>$d[9], 
            //     // 'treatment_outcome'=>$d[10], 
            //     // 'date_of_last_encounter'=>$d[11], 
            //     // 'date_of_last_viral_load'=>$d[12], 
            //     // 'date_of_next_appointment'=>$d[13], 
            //     // 'last_regimen'=>$d[14], 
            //     // 'last_regimen_line'=>$d[15], 
            //     // 'current_on_art'=>$d[16], 
            //     // 'date_of_hiv_diagnosis'=>$d[17], 
            //     // 'last_viral_load_result'=>$d[18]
            //     ]);
            $i++;
        }
        foreach ($insert_data->chunk(520) as $chunk)
        {
            DB::connection('sqlsrv')->table('tmp_and_adhoc.dbo.nupi_dataset')->insert($chunk->toArray());
        }
        fclose($file); //Close after reading

            // delete chunked file
            unlink($file2->getPathname());
            
            return view('import_success');
            return [
                'path' => asset('storage/' . $path),
                'filename' => $fileName
            ];
        }

        // otherwise return percentage information
        $handler = $fileReceived->handler();
        return [
            'done' => $handler->getPercentageDone(),
            'status' => true
        ];
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