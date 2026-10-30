<?php

namespace App\Support;

/**
 * URL of a CSS/JS file under public/ with its modification time appended,
 * so browsers load the new file after each deploy instead of a cached copy
 * (Nginx caches static files for 7 days).
 */
class Asset
{
    public static function v(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
