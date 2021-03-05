@component('mail::message')
Dear {{ $user->name }}

Your data upload to the National Data Warehouse has been successfully refreshed as at {{ $refresh_date->format('d M Y') }}.<br>

Find attached a summary Data Quality Assurance report.

You are receiving this email as the contact person for {{ $partner }}. If you no longer wish to receive communication from us please unsubscribe from here {{ $unsubscribe_url }}

Do not reply to this address, this is a system generated email, if you have any questions/concerns, please contact {{ $contact->name }} on {{ $contact->email }}

Regards<br>
National EMR Data Warehouse

@endcomponent
