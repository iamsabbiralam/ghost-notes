<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('ghost-notes', function () {
      $jsonPath = storage_path('app/ghost-notes/data.json');

      if (app()->environment('production')) {
            abort(403, 'Unauthorized in production.');
      }

      if (!File::exists($jsonPath)) {
            return "Ghost Notes data not found. <br> 
                1. Run: <b>php artisan ghost:write</b> <br> 
                2. Check if this file exists: <b>" . $jsonPath . "</b>";
      }

      // JSON Data
      $rows = json_decode(File::get($jsonPath), true);

      return view('ghost-notes::dashboard', ['rows' => $rows]);
})->middleware('web');
