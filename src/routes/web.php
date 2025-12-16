<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('ghost-notes', function () {
      $path = base_path(config('ghost-notes.filename', 'GHOST_LOG.md'));

      // Production security check
      if (app()->environment('production')) {
            abort(403, 'Unauthorized in production.');
      }

      if (!File::exists($path)) {
            return "Ghost Log not found. Run 'php artisan ghost:write' first.";
      }

      $content = File::get($path);
      $lines = explode("\n", $content);
      $rows = [];
      foreach ($lines as $line) {
            if (str_contains($line, '|') && !str_contains($line, '---') && !str_contains($line, 'Date | Tag')) {
                  $rows[] = array_map('trim', explode('|', trim($line, '|')));
            }
      }

      return view('ghost-notes::dashboard', ['rows' => $rows]);
})->middleware('web');
