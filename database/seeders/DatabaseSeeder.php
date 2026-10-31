<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
 public function run(): void { $this->command?->info('No default accounts or employee IDs are created. For synthetic local data use: php artisan pms:demo-data --confirm'); }
}
