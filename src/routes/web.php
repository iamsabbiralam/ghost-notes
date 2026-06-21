<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Response;
use Iamsabbiralam\GhostNotes\Commands\GhostWriterCommand;
use Carbon\Carbon;
use Illuminate\Http\Request;

Route::get('ghost-notes', function () {
      $jsonPath = storage_path('app/ghost-notes/data.json');
      $historyPath = storage_path('app/ghost-notes/history.json');

      if (app()->environment('production')) abort(403);

      $activeNotes = File::exists($jsonPath) ? json_decode(File::get($jsonPath), true) : [];
      $resolvedNotes = File::exists($historyPath) ? json_decode(File::get($historyPath), true) : [];

      // Developer-wise count of active notes --- IGNORE ---
      $authorCounts = [];
      foreach ($activeNotes as $note) {
            $note['due_status'] = null;
            $note['days_left'] = null;

            if (!empty($note['due_date'])) {
                  $dueDate = Carbon::parse($note['due_date'])->startOfDay();
                  $today = Carbon::now()->startOfDay();

                  if ($today->gt($dueDate)) {
                        $note['due_status'] = 'OVERDUE';
                        $note['days_left'] = $today->diffInDays($dueDate); // কতদিন ওভারডিউ
                  } elseif ($today->equalTo($dueDate)) {
                        $note['due_status'] = 'TODAY';
                  } else {
                        $note['due_status'] = 'PENDING';
                        $note['days_left'] = $today->diffInDays($dueDate); // কতদিন বাকি
                  }
            }

            $author = $note['author'] ?? 'Unknown';
            if (!isset($authorCounts[$author])) {
                  $authorCounts[$author] = 0;
            }
            $authorCounts[$author]++;
      }
      unset($note);

      // Prepare data for the last 7 days --- IGNORE ---
      $last7Days = [];
      for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));

            $addedCount = 0;
            foreach ($activeNotes as $note) {
                  if (isset($note['date']) && str_contains($note['date'], $date)) {
                        $addedCount++;
                  }
            }

            $resolvedCount = 0;
            foreach ($resolvedNotes as $history) {
                  if (isset($history['resolved_at']) && str_contains($history['resolved_at'], $date)) {
                        $resolvedCount++;
                  }
            }

            $last7Days[$date] = [
                  'added' => $addedCount,
                  'resolved' => $resolvedCount
            ];
      }

      $customTags = config('ghost-notes.custom_tags', []);
      return view('ghost-notes::dashboard', [
            'rows'         => $activeNotes,
            'history'      => $resolvedNotes,
            'authorCounts' => $authorCounts,
            'weeklyStats'  => $last7Days,
            'customTags'   => $customTags,
      ]);
})->middleware('web');

Route::get('ghost-notes/export/{format}', function ($format) {
      try {
            $exitCode = Artisan::call(GhostWriterCommand::class, [
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

Route::post('ghost-notes/resolve', function (Request $request) {
      try {
            $jsonPath = storage_path('app/ghost-notes/data.json');
            $historyPath = storage_path('app/ghost-notes/history.json');

            $relativeFile = $request->input('file');
            $lineNumber = intval($request->input('line')); // Force Integer

            if (!$relativeFile || !$lineNumber) {
                  return response()->json(['success' => false, 'message' => 'Invalid file or line number.'], 400);
            }

            if (str_ends_with($relativeFile, '.blade.php') && !str_contains($relativeFile, 'resources/views')) {
                  $absolutePath = base_path('resources/views/' . $relativeFile);
            } else {
                  $absolutePath = base_path($relativeFile);
            }

            if (!File::exists($absolutePath)) {
                  return response()->json(['success' => false, 'message' => "Source file not found at: {$relativeFile}"], 404);
            }

            // --- ১. মূল কোড ফাইল থেকে ওই নির্দিষ্ট লাইনটি ব্ল্যাঙ্ক করা ---
            $content = File::get($absolutePath);
            // উইন্ডোজ ও লিনাক্স দুই লাইনের ব্রেক হ্যান্ডেল করার জন্য রেজেক্স স্প্লিট
            $lines = preg_split("/\r\n|\n|\r/", $content);

            $targetIndex = $lineNumber - 1;

            if (isset($lines[$targetIndex])) {
                  // লাইনটি ডিলিট না করে খালি স্ট্রিং করছি যাতে নিচের অন্য লাইনের নম্বরগুলো ঠিক থাকে
                  $lines[$targetIndex] = '';
                  File::put($absolutePath, implode(PHP_EOL, $lines));
            } else {
                  return response()->json(['success' => false, 'message' => 'Target line does not exist in file.'], 422);
            }

            // --- ২. data.json (Active Notes) থেকে রিমুভ করা ---
            $activeNotes = File::exists($jsonPath) ? json_decode(File::get($jsonPath), true) : [];
            if (!is_array($activeNotes)) {
                  $activeNotes = [];
            }

            $resolvedNote = null;

            $filteredNotes = array_filter($activeNotes, function ($note) use ($relativeFile, $lineNumber, &$resolvedNote) {
                  // সহজে ম্যাচ করার জন্য ভিএসকোড লিংক থেকে শুধু লাইন নম্বরটা চেক করা
                  $isTarget = ($note['file'] === $relativeFile && str_ends_with(trim($note['vscode']), ":{$lineNumber}"));
                  if ($isTarget) {
                        $resolvedNote = $note;
                  }
                  return !$isTarget;
            });

            // ক্যাশ বা জেসন ফাইল আপডেট
            File::put($jsonPath, json_encode(array_values($filteredNotes), JSON_PRETTY_PRINT));

            // --- ৩. history.json (Graveyard) এ পুশ করা ---
            if ($resolvedNote) {
                  $history = File::exists($historyPath) ? json_decode(File::get($historyPath), true) : [];
                  if (!is_array($history)) {
                        $history = [];
                  }

                  $resolvedNote['resolved_at'] = date('Y-m-d H:i');
                  $history[] = $resolvedNote;
                  File::put($historyPath, json_encode(array_values($history), JSON_PRETTY_PRINT));
            }

            return response()->json([
                  'success' => true,
                  'message' => 'Task resolved successfully and code updated! 🏆',
                  'active_notes' => array_values($filteredNotes),
                  'history_notes' => File::exists($historyPath) ? json_decode(File::get($historyPath), true) : []
            ]);
      } catch (\Exception $e) {
            // ক্র্যাশ করলে ৫০০ এরর না দিয়ে সরাসরি আসল মেসেজটি রেসপন্সে পাঠাবে
            return response()->json([
                  'success' => false,
                  'message' => 'Backend Error: ' . $e->getMessage()
            ], 500);
      }
})->middleware('web');
