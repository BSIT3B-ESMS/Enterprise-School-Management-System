<?php

namespace App\Services;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Sends email, but only to personal addresses. The official plpasig.edu.ph addresses in the database follow a pattern and
 * may belong to real people, so the system never writes to them; it returns false instead of sending.
 */
class PersonalMail
{
    public function send(?string $address, Mailable $mailable): bool
    {
        if (blank($address) || $this->isOfficialAddress($address)) {
            return false;
        }

        Mail::to($address)->send($mailable);

        return true;
    }

    public function isOfficialAddress(string $address): bool
    {
        $domain = strtolower((string) substr(strrchr($address, '@') ?: '', 1));

        return in_array($domain, array_map('strtolower', config('ems.never_email_domains')), true);
    }
}
