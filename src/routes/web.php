<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('ghost-notes', function () {
      $path = base_path(config('ghost-notes.filename', 'GHOST_LOG.md'));

      if (!File::exists($path)) {
            return "Ghost Log not found. Run 'php artisan ghost:write' first.";
      }

      $content = File::get($path);
      // Markdown-ke HTML-e convert korar simple logic (Eikhane ParseDown library thakle bhalo hoto, kintu amra simple rakhi)
      return "<html><body style='font-family:sans-serif; padding:50px; background:#f4f4f4;'>
                <div style='background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1);'>
                    <pre>{$content}</pre>
                </div>
            </body></html>";
});
