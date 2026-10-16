<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent right away (not queued): the user is waiting on the login screen.
 */
class TwoFactorCode extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code, private int $minutes) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your PLP admission sign-in code')
            ->line('Use this code to finish signing in:')
            ->line("**{$this->code}**")
            ->line("It expires in {$this->minutes} minutes. If you didn't try to sign in, change your password.");
    }
}
