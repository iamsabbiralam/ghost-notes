<?php

namespace Iamsabbiralam\GhostNotes\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GhostWriterCommand extends Command
{
      protected $signature = 'ghost:write {--clear : Clear notes from code after generating diary}';
      protected $description = 'Generate a dev-diary from @ghost tags in your code';

      public function handle()
      {
            $tags = config('ghost-notes.tags', ['@ghost']);
            $filename = config('ghost-notes.filename', 'GHOST_LOG.md');
            $ignoreFolders = config('ghost-notes.ignore_folders', []);

            $this->info("Scanning for tags: " . implode(', ', $tags));

            $directory = base_path('app');
            $files = File::allFiles($directory);
            $notes = [];
            $pattern = '/(' . implode('|', array_map('preg_quote', $tags)) . '):(.*)/';
            $shouldClear = $this->option('clear');
            $modifiedFiles = [];

            foreach ($files as $file) {
                  foreach ($ignoreFolders as $folder) {
                        if (str_contains($file->getRelativePathname(), $folder . DIRECTORY_SEPARATOR)) {
                              continue 2;
                        }
                  }

                  $content = File::get($file);
                  $lines = explode("\n", $content);
                  $newLines = [];
                  $foundInFile = false;

                  foreach ($lines as $lineNumber => $lineContent) {
                        if (preg_match($pattern, $lineContent, $match)) {
                              $foundInFile = true;
                              $author = "Unknown";

                              if (config('ghost-notes.git_context', true)) {
                                    $realLineNumber = $lineNumber + 1;
                                    $filePath = $file->getRealPath();
                                    // escapeshellarg ব্যবহার করা হয়েছে যা সিকিউরিটির জন্য ভালো
                                    $command = "git blame -L {$realLineNumber},{$realLineNumber} --porcelain " . escapeshellarg($filePath);
                                    $output = shell_exec($command);

                                    if ($output) {
                                          preg_match('/^author (.*)$/m', $output, $authorMatch);
                                          $author = $authorMatch[1] ?? "Unknown";
                                    }
                              }

                              $notes[] = [
                                    'tag'    => strtoupper(str_replace('@', '', $match[1])),
                                    'file'   => $file->getRelativePathname(),
                                    'text'   => trim($match[2]),
                                    'date'   => date('Y-m-d H:i'),
                                    'author' => trim($author),
                              ];

                              // ক্লিয়ার মোড থাকলে এই লাইনটি স্কিপ করবে
                              if ($shouldClear) {
                                    continue;
                              }
                        }

                        $newLines[] = $lineContent;
                  }

                  if ($shouldClear && $foundInFile) {
                        // PHP_EOL ব্যবহার করা নিরাপদ
                        File::put($file->getRealPath(), implode("\n", $newLines));
                        $modifiedFiles[] = $file->getRelativePathname();
                  }
            }

            if ($shouldClear && count($modifiedFiles) > 0) {
                  $this->info("Cleared notes from: " . count($modifiedFiles) . " files.");
            }

            if (empty($notes)) {
                  $this->warn("No notes found!");
                  return;
            }

            $markdown = "# 👻 GhostNotes - Dev Diary\n\n";
            $markdown .= "| Date | Tag | Author | File | Note |\n";
            $markdown .= "|------|-----|--------|------|------|\n";

            foreach ($notes as $note) {
                  $markdown .= "| {$note['date']} | **{$note['tag']}** | {$note['author']} | `{$note['file']}` | {$note['text']} |\n";
            }

            File::put(base_path($filename), $markdown);
            $this->info("Success! {$filename} generated with " . count($notes) . " notes.");
      }
}
