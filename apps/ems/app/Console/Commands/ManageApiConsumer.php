<?php

namespace App\Console\Commands;

use App\Models\ApiConsumer;
use Illuminate\Console\Command;

class ManageApiConsumer extends Command
{
    protected $signature = 'api:consumer {action : create, list, rotate, revoke, or restore}
        {name? : The consumer name (not needed for list)}
        {--ability=* : What the consumer may read (employees:read, reference:read); both by default}
        {--agreement= : The data sharing agreement reference}';

    protected $description = 'Create, list, rotate, revoke, or restore an external API consumer and its token';

    public function handle(): int
    {
        $action = (string) $this->argument('action');
        $name = $this->argument('name');

        if ($action === 'list') {
            $this->table(
                ['Name', 'Active', 'Abilities', 'Token issued', 'Last used'],
                ApiConsumer::query()->orderBy('name')->get()->map(fn (ApiConsumer $consumer): array => [
                    $consumer->name,
                    $consumer->is_active ? 'yes' : 'no',
                    implode(', ', $consumer->abilities),
                    $consumer->token_issued_at->toDateString(),
                    $consumer->last_used_at?->toDateTimeString() ?? 'never',
                ])->all(),
            );

            return self::SUCCESS;
        }

        if (! in_array($action, ['create', 'rotate', 'revoke', 'restore'], true) || blank($name)) {
            $this->error('Use: api:consumer create|rotate|revoke|restore <name>, or api:consumer list.');

            return self::INVALID;
        }

        return $action === 'create' ? $this->create((string) $name) : $this->change($action, (string) $name);
    }

    private function create(string $name): int
    {
        $abilities = $this->option('ability') ?: ApiConsumer::ABILITIES;
        $unknown = array_diff($abilities, ApiConsumer::ABILITIES);

        if ($unknown !== []) {
            $this->error('Unknown ability: '.implode(', ', $unknown).'. Allowed: '.implode(', ', ApiConsumer::ABILITIES).'.');

            return self::INVALID;
        }

        if (ApiConsumer::query()->where('name', $name)->exists()) {
            $this->error("A consumer named {$name} already exists.");

            return self::FAILURE;
        }

        $consumer = new ApiConsumer(['name' => $name, 'abilities' => array_values($abilities), 'is_active' => true, 'agreement_ref' => $this->option('agreement')]);
        $token = ApiConsumer::generateToken();
        $consumer->forceFill(['token_hash' => ApiConsumer::hashToken($token), 'token_issued_at' => now()])->save();

        $this->showToken($consumer, $token);

        return self::SUCCESS;
    }

    private function change(string $action, string $name): int
    {
        $consumer = ApiConsumer::query()->where('name', $name)->first();

        if (! $consumer) {
            $this->error("No consumer named {$name}.");

            return self::FAILURE;
        }

        if ($action === 'rotate') {
            $this->showToken($consumer, $consumer->issueToken());

            return self::SUCCESS;
        }

        $consumer->update(['is_active' => $action === 'restore']);
        $this->info("{$name} is now ".($action === 'revoke' ? 'revoked' : 'active').'.');

        return self::SUCCESS;
    }

    private function showToken(ApiConsumer $consumer, string $token): void
    {
        $this->info("Token for {$consumer->name} (shown once, store it safely):");
        $this->line($token);
    }
}
