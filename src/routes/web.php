<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Response;

Route::get('ghost-notes', function () {
      $jsonPath = storage_path('app/ghost-notes/data.json');
      $historyPath = storage_path('app/ghost-notes/history.json');

      if (app()->environment('production')) abort(403);

      $activeNotes = File::exists($jsonPath) ? json_decode(File::get($jsonPath), true) : [];
      $resolvedNotes = File::exists($historyPath) ? json_decode(File::get($historyPath), true) : [];

      return view('ghost-notes::dashboard', [
            'rows' => $activeNotes,
            'history' => $resolvedNotes
      ]);
})->middleware('web');

Route::get('ghost-notes/export/{format}', function ($format) {
      try {
            $exitCode = Artisan::call('ghost:write', [
                  '--format' => $format
            ]);

            if ($exitCode !== 0) {
                  return "Command failed with exit code: " . $exitCode;
            }

            $filename = config('ghost-notes.filename', 'GHOST_LOG');
            $baseFileName = str_replace(['.md', '.json', '.csv'], '', $filename);
            $extension = ($format == 'markdown') ? 'md' : $format;
            $path = base_path($baseFileName . '.' . $extension);

            if (File::exists($path)) {
                  return Response::download($path);
            }

            return "File not found at: " . $path;
      } catch (\Exception $e) {
            return "GhostNotes Error: " . $e->getMessage();
      }
});
