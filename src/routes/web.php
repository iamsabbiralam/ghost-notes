<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('ghost-notes', function () {
      $path = base_path(config('ghost-notes.filename', 'GHOST_LOG.md'));

      if (app()->environment('production')) abort(403);
      if (!File::exists($path)) return "Ghost Log not found.";

      $content = File::get($path);
      $lines = explode("\n", $content);
      $rows = [];

      foreach ($lines as $line) {
            if (str_contains($line, '|') && !str_contains($line, '---') && !str_contains($line, 'Date | Tag')) {
                  $parts = array_map('trim', explode('|', trim($line, '|')));

                  // Extract URL from Markdown link: [filename](url)
                  $fileData = $parts[4] ?? '';
                  $link = "";
                  $fileName = $fileData;
                  if (preg_match('/\[(.*?)\]\((.*?)\)/', $fileData, $matches)) {
                        $fileName = $matches[1];
                        $link = $matches[2];
                  }

                  $rows[] = [
                        'date'     => $parts[0] ?? '',
                        'tag'      => $parts[1] ?? '',
                        'priority' => strtoupper($parts[2] ?? 'NORMAL'),
                        'author'   => $parts[3] ?? 'Unknown',
                        'file'     => $fileName,
                        'link'     => $link,
                        'message'  => $parts[5] ?? '',
                  ];
            }
      }

      return view('ghost-notes::dashboard', ['rows' => $rows]);
})->middleware('web');
