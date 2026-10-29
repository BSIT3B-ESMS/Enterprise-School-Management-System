<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells a new hire how to sign in as an employee. It is sent only to the applicant's personal email, never to the official
 * plpasig.edu.ph address (see App\Services\PersonalMail).
 */
class HiredMail extends Mailable
{
    public function __construct(
        public string $name,
        public string $position,
        public string $loginEmail,
        public string $employeeNo,
        public CarbonInterface $startDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to PLP: your employee account');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.hired');
    }
}
