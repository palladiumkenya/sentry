<?php

namespace App\Jobs;

use App\Mail\ETLCompleted;
use App\Models\Facility;
use App\Models\FacilityPartner;
use App\Models\LiveSyncIndicator;
use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\EtlJob;
use Illuminate\Support\Str;
use stdClass;
use App\Models\EtlJob as EtlJobModel;
use Swift_IoException;
use \Mailjet\Resources;


class EmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $metrics, $spoturl, $dwhurl, $facility_partner, $ct_rr, $hts_rr, $partner, $difference;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($metrics, $spoturl, $dwhurl, $facility_partner, $ct_rr, $hts_rr, Partner $partner, $difference)
    {
        $this->metrics = $metrics;
        $this->spoturl = $spoturl;
        $this->dwhurl = $dwhurl;
        $this->facility_partner = $facility_partner;
        $this->ct_rr = $ct_rr;
        $this->hts_rr = $hts_rr;
        $this->partner = $partner;
        $this->difference = $difference;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

//        config(['database.connections.sqlsrv.database' => 'DWHIdentity']);
        $organization = DB::connection('sqlsrv')->table('DWHIdentity.dbo.Organizations')
            ->selectRaw('Name, Id, Code')
            ->where('UsgMechanism', $this->partner->name)
            ->whereNotNull('UsgMechanism')
            ->get();
        foreach ($organization as $org) {
            $contacts = DB::connection('sqlsrv')->table('DWHIdentity.dbo.OrganizationContactses')
                ->selectRaw('Email, Names')
                ->where('OrganizationId', $org->Id)
                ->where('Unsubscribe', 0)
                ->get();

            foreach ($contacts as $contact) {
                $user = new stdClass();
                $user->email = nova_get_setting('test_person_email');
                $user->name = nova_get_setting('test_person_name');

                $contactPerson = new stdClass();
                $contactPerson->email = nova_get_setting('contact_person_email');
                $contactPerson->name = nova_get_setting('contact_person_name');

                $email = $contact->Email;
                $name = $contact->Names;

                $unsubscribe_url = str_replace(
                    '{{email}}', $email,
                    nova_get_setting(nova_get_setting('production') ? 'email_unsubscribe_url' : 'email_unsubscribe_url_staging')
                );

                try {
                    Mail::send('reports.partner.metrics',
                        [
                            'name' => $name,
                            'contactPerson' => $contactPerson,
                            'unsubscribe_url' => $unsubscribe_url,
                            'metrics' => $this->metrics,
                            'spoturl' => $this->spoturl,
                            'dwhurl' => $this->dwhurl,
                            'facility_partner' => $this->facility_partner,
                            'ct_rr' => $this->ct_rr,
                            'hts_rr' => $this->hts_rr,
                            'partner' => $this->partner,
                            'difference' => $this->difference
                        ],
                        function ($message) use ($email) {
                            $message->to($email)->subject('NDWH DQA Report');
                        });
                } catch (Swift_IoException $e) {
                    Log::error($e);
                }

            }
        }

    }
}
