<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ETLCompleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 1;

    public $user;
    public $contact;
    public $partner;
    public $refresh_date;
    public $unsubscribe_url;
    public $file;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        $user,
        $contact,
        $partner,
        $refresh_date,
        $unsubscribe_url,
        $file
    )
    {
        $this->user = $user;
        $this->contact = $contact;
        $this->partner = $partner;
        $this->refresh_date = $refresh_date;
        $this->unsubscribe_url = $unsubscribe_url;
        $this->file = $file;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.etl.completed', [
            'user' => $this->user,
            'contact' => $this->contact,
            'partner' => $this->partner,
            'refresh_date' => $this->refresh_date,
            'unsubscribe_url' => $this->unsubscribe_url,
        ])->subject('NDWH DQA Report')->attach($this->file);
    }
}
