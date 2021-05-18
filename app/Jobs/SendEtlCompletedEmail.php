<?php

namespace App\Jobs;

use App\Mail\ETLCompleted;
use App\Models\EtlJob;
use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use LynX39\LaraPdfMerger\Facades\PdfMerger;
use stdClass;

class SendEtlCompletedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    protected $partner;
    protected $etlJob;
    protected $facilityIds;

    public function __construct(Partner $partner, EtlJob $etlJob, $facilityIds)
    {
        $this->partner = $partner;
        $this->etlJob = $etlJob;
        $this->facilityIds = $facilityIds;
    }

    public function handle()
    {
        $user = new stdClass();
        $user->email = nova_get_setting('test_person_email');
        $user->name = nova_get_setting('test_person_name');
        $contact = new stdClass();
        $contact->email = nova_get_setting('contact_person_email');
        $contact->name = nova_get_setting('contact_person_name');
        $partner = $this->partner->name;
        $refresh_date = now();
        $unsubscribe_url = str_replace(
            '{{email}}', $user->email,
            nova_get_setting(nova_get_setting('production') ? 'email_unsubscribe_url' : 'email_unsubscribe_url_staging')
        );

        $pdfMerger = PDFMerger::init();

        foreach ($this->facilityIds as $facility_id)
        {
            $path = storage_path('app/reports/etls/'.$this->etlJob->id.'_'.$facility_id.'.pdf');
            if (file_exists($path)) {
                $pdfMerger->addPDF($path, 'all');
            }
        }

        $file = storage_path(
            'app/reports/etls/'.
            $this->partner->clean_name.'_'.
            $this->etlJob->job_date->format('YmdHis').'_dqa.pdf'
        );

        if (file_exists($file)) {
            unlink($file);
        }

        $pdfMerger->merge();
        $pdfMerger->save($file);

        Mail::to($user->email)->send(new ETLCompleted(
            $user,
            $contact,
            $partner,
            $refresh_date,
            $unsubscribe_url,
            $file
        ));
    }
}
