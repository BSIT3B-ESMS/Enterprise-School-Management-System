<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FR-13 (Could): SMS for the exam schedule and result release, through
 * whichever SMS provider the university picks. SMS_DRIVER:
 *   none  off (default)
 *   log   written to the log (testing)
 *   http  POST {to, message, sender} as JSON with X-API-Key to SMS_URL
 *         (adjust the body here once the provider's API is chosen)
 * Sent from the queue with the notification; a failed send is retried by
 * the queue and never blocks the email or in-app notice.
 */
class SmsChannel
{
    public static function enabled(): bool
    {
        return in_array(config('admission.sms.driver'), ['log', 'http'], true);
    }

    public function send(object $notifiable, Notification $notification): void
    {
        $to = $notifiable->applicant?->phone;
        if (! $to || ! method_exists($notification, 'toSms')) {
            return;
        }
        $to = '+63'.substr(preg_replace('/\D/', '', $to), -10);
        $message = $notification->toSms($notifiable);

        match (config('admission.sms.driver')) {
            'http' => Http::timeout(10)->withHeaders(['X-API-Key' => (string) config('admission.sms.key')])
                ->post((string) config('admission.sms.url'), ['to' => $to, 'message' => $message, 'sender' => config('admission.sms.sender')])
                ->throw(),
            'log' => Log::info("SMS to {$to}: {$message}"),
            default => null,
        };
    }
}
