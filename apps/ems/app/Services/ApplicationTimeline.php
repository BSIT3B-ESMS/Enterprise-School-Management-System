<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationEvent;
use App\Models\User;

/**
 * Adds a line to an application's timeline.
 */
class ApplicationTimeline
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function add(Application $application, string $type, ?User $actor = null, array $details = []): ApplicationEvent
    {
        return $application->events()->create([
            'type' => $type,
            'actor_id' => $actor?->id,
            'details' => $details === [] ? null : $details,
        ]);
    }
}
