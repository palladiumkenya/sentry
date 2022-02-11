<?php

namespace App\Mail;

use App\Models\FacilityPartner;
use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ETLCompleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 1;

    public $user;
    public $contact;
    public $partner;
    public $unsubscribe_url;
    public $file;
    protected $metrics, $spoturl, $dwhurl, $facility_partner, $ct_rr, $hts_rr;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        $user,
        $contact,
        $unsubscribe_url, $metrics, $spoturl, $dwhurl,
        $facility_partner, $ct_rr, $hts_rr, Partner $partner
    )
    {
        $this->user = $user;
        $this->contact = $contact;
        $this->unsubscribe_url = $unsubscribe_url;
        $this->metrics = $metrics;
        $this->spoturl = $spoturl;
        $this->dwhurl = $dwhurl;
        $this->facility_partner = $facility_partner;
        $this->ct_rr = $ct_rr;
        $this->hts_rr = $hts_rr;
        $this->partner = $partner;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        Log::info($this->user);
        Log::info($this->metrics);
        return $this->markdown('reports.partner.metrics', [
            'metrics' => $this->metrics,
            'spoturl' => $this->spoturl,
            'dwhurl' => $this->dwhurl,
            'facility_partner' => $this->facility_partner,
            'ct_rr' => $this->ct_rr,
            'hts_rr' => $this->hts_rr,
            'user' => $this->user,
            'contact' => $this->contact,
            'partner' => $this->partner,
            'unsubscribe_url' => $this->unsubscribe_url,
        ])->subject('NDWH DQA Report');
    }
}
