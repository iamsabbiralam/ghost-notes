<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('ghost-notes', function () {
      $jsonPath = storage_path('app/ghost-notes.json');

      if (app()->environment('production')) abort(403);

      if (!File::exists($jsonPath)) {
            return "Ghost Notes data not found. Please run 'php artisan ghost:write' first.";
      }

      $rows = json_decode(File::get($jsonPath), true);

      return view('ghost-notes::dashboard', ['rows' => $rows]);
})->middleware('web');