<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Phase 10: sysadmin tools (D7, NFR-03). MySQL-only paths are checked by hand, see PROGRESS.md. */
class SysadminToolsTest extends TestCase
{
    public function test_backup_needs_a_real_database_file(): void
    {
        // The test database lives in memory: nothing to back up, and it says so
        $this->artisan('app:backup')->expectsOutputToContain('There is no SQLite database file')->assertFailed();
    }

    public function test_backup_of_a_sqlite_file_includes_database_and_documents(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/AD-2026-00001/a.pdf', '%PDF-1.4');
        $dir = sys_get_temp_dir().'/adm_backup_test_'.uniqid();
        $file = $dir.'/db.sqlite';
        mkdir($dir);
        touch($file);
        config(['database.connections.filecopy' => ['driver' => 'sqlite', 'database' => $file, 'prefix' => ''], 'admission.backups.path' => $dir.'/backups']);
        \DB::purge('filecopy');

        $this->artisan('app:backup', ['--connection' => 'filecopy'])->expectsOutputToContain('1 document files')->assertSuccessful();

        $zips = glob($dir.'/backups/backup_*.zip');
        $this->assertCount(1, $zips);
        $zip = new \ZipArchive;
        $zip->open($zips[0]);
        $this->assertNotFalse($zip->locateName('database.sqlite'));
        $this->assertNotFalse($zip->locateName('documents/AD-2026-00001/a.pdf'));
        $zip->close();
    }

    public function test_privileges_command_is_for_mysql(): void
    {
        $this->artisan('app:db-privileges')->expectsOutputToContain('This command is for MySQL')->assertFailed();
    }
}
