<?php

namespace Database\Factories;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'actor_type' => 'system',
            'action' => AuditAction::Update,
            'entity_type' => null,
            'entity_id' => null,
            'changes' => null,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
