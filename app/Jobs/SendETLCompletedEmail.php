<?php

namespace App\Jobs;

use App\Mail\ETLCompleted;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendETLCompletedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        $user = User::find(1);
        $contact = new User;
        $contact->email = 'Koske.Kimutai@thepalladiumgroup.com';
        $contact->name = 'Koske Kimutai';
        $partner = 'Palladium';
        $refresh_date = now();
        $unsubscribe_url = "https://auth.kenyahmis.org/dwhidentity/api/EmailService/Unsubscribe/".$user->email;
        $file = storage_path('app/reports/palladium_ndwh_dqa.pdf');

        Mail::to('eric.ejimba@thepalladiumgroup.com')->send(new ETLCompleted(
            $user,
            $contact,
            $partner,
            $refresh_date,
            $unsubscribe_url,
            $file
        ));
    }
}
