<?php

namespace App\Notifications;

use App\Services\TwoFactorService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent right away (not queued): the user is waiting for it on screen.
 */
class TwoFactorCode extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        private int $minutes,
        private string $purpose = TwoFactorService::LOGIN,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptance = $this->purpose === TwoFactorService::SLOT_ACCEPTANCE;

        return (new MailMessage)
            ->subject($acceptance ? 'Your code to accept your slot' : 'Your PLP admission sign-in code')
            ->line($acceptance ? 'Use this code to confirm that you accept your slot:' : 'Use this code to finish signing in:')
            ->line("**{$this->code}**")
            ->line("It expires in {$this->minutes} minutes. If this wasn't you, change your password.");
    }
}
