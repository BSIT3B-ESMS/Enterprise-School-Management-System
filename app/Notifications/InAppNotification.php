<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * A message shown inside the system only (the bell in the header and the Notifications page). It never goes to email:
 * several staff mailboxes are real PLP addresses, so the system does not write to them.
 */
class InAppNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, url: ?string}
     */
    public function toDatabase(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }
}
