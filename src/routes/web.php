<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

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
