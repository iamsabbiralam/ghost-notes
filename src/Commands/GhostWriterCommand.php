<?php

namespace Iamsabbiralam\GhostNotes\Commands;

use IamSabbirAlam\GhostNotes\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GhostWriterCommand extends Command
{
      protected $signature = 'ghost:write 
            {--clear : Clear notes from code after generating diary} 
            {--format=markdown : Output format (markdown, json, csv)}';
      protected $description = 'Generate a dev-diary from @ghost tags and export in multiple formats';

      public function handle()
      {
            $format = $this->option('format');
            $tags = config('ghost-notes.tags', ['@ghost', '@todo', '@fixme']);
            $filename = config('ghost-notes.filename', 'GHOST_LOG.md');
            $ignoreFolders = config('ghost-notes.ignore_folders', ['vendor', 'node_modules', 'storage']);

            // --- GitHub Auto-detect ---
            $repoUrl = config('ghost-notes.repo_url');
            if (empty($repoUrl)) {
                  $remote = shell_exec('git remote get-url origin');
                  if ($remote) {
                        $repoUrl = str_replace([':', 'git@'], ['/', 'https://'], trim($remote));
                        $repoUrl = str_replace('.git', '', $repoUrl);
                  }
            }

            $this->info("🔍 Scanning for tags: " . implode(', ', $tags));

            // Get directories and extensions from config
            $scanDirectories = config('ghost-notes.scan_directories', ['app']);
            $allowedExtensions = config('ghost-notes.allowed_extensions', ['php']);

            $notes = [];
            $modifiedFiles = [];
            $shouldClear = $this->option('clear');

            // Loop through each configured directory to scan
            foreach ($scanDirectories as $scanDir) {
                  $directoryPath = base_path($scanDir);

                  // check if the directory exists in the project
                  if (!File::isDirectory($directoryPath)) {
                        continue;
                  }

                  $files = File::allFiles($directoryPath);

                  foreach ($files as $file) {
                        // ignore check folders
                        foreach ($ignoreFolders as $folder) {
                              if (str_contains($file->getRelativePathname(), $folder . DIRECTORY_SEPARATOR)) {
                                    continue 2;
                              }
                        }

                        // Handle double extensions like blade.php
                        $extension = $file->getExtension();
                        $fullFilename = $file->getFilename();
                        $isBlade = str_ends_with($fullFilename, '.blade.php');

                        if (!in_array($extension, $allowedExtensions) && !($isBlade && in_array('blade.php', $allowedExtensions))) {
                              continue;
                        }

                        // Read file content and look for tags using regex
                        $content = File::get($file);
                        $lines = explode("\n", $content);
                        $newLines = [];
                        $foundInFile = false;

                        // 🛠️ আপডেট করা রেজেক্স: অপশনাল YYYY-MM-DD ডেডলাইন প্যাটার্ন ম্যাচ করার জন্য
                        $pattern = '/(' . implode('|', array_map('preg_quote', $tags)) . ')(?::(fix|feature|breaking|todo|change|note))?(?::(high|medium|low))?(?::(\d{4}-\d{2}-\d{2}))?:\s*(.*)/i';

                        foreach ($lines as $lineNumber => $lineContent) {
                              if (preg_match($pattern, $lineContent, $match)) {
                                    $foundInFile = true;
                                    $tagName = strtoupper(str_replace('@', '', $match[1]));
                                    $type = !empty($match[2]) ? strtoupper($match[2]) : 'GENERAL';
                                    $priority = !empty($match[3]) ? strtoupper($match[3]) : 'NORMAL';

                                    // 📅 ডেডলাইন এবং মেসেজ কন্ডিশনাল হ্যান্ডেলিং
                                    $dueDate = !empty($match[4]) ? $match[4] : null;
                                    $message = isset($match[5]) ? trim($match[5]) : trim($match[4]);

                                    if (empty($message) && $dueDate) {
                                          $message = $dueDate;
                                          $dueDate = null;
                                    }

                                    // GitHub Link
                                    $githubLink = "";
                                    if (!empty($repoUrl)) {
                                          $branch = config('ghost-notes.default_branch', 'main');
                                          $realLine = $lineNumber + 1;
                                          $githubLink = "{$repoUrl}/blob/{$branch}/" . $file->getRelativePathname() . "#L{$realLine}";
                                    }

                                    // Author Logic via Git Blame
                                    $author = "Unknown";
                                    if (config('ghost-notes.git_context', true)) {
                                          $realLineNumber = $lineNumber + 1;
                                          $blame = shell_exec("git blame -L {$realLineNumber},{$realLineNumber} --porcelain " . escapeshellarg($file->getRealPath()));
                                          if ($blame) {
                                                preg_match('/^author (.*)$/m', $blame, $authorMatch);
                                                $author = $authorMatch[1] ?? "Unknown";
                                          }
                                    }

                                    // Code Snippet
                                    $start = max(0, $lineNumber - 2);
                                    $end = min(count($lines) - 1, $lineNumber + 2);
                                    $snippetLines = array_slice($lines, $start, ($end - $start) + 1);
                                    $snippet = implode("\n", $snippetLines);

                                    $absolutePath = str_replace('\\', '/', $file->getRealPath());
                                    $vscodeLink = "vscode://file/{$absolutePath}:" . ($lineNumber + 1);

                                    $notes[] = [
                                          'date'     => date('Y-m-d H:i'),
                                          'tag'      => $tagName,
                                          'type'     => $type,
                                          'priority' => $priority,
                                          'due_date' => $dueDate, // 📅 নতুন ফিল্ড যোগ হলো JSON এবং ড্যাশবোর্ডের জন্য
                                          'author'   => trim($author),
                                          'file'     => $file->getRelativePathname(),
                                          'link'     => $githubLink,
                                          'vscode'   => $vscodeLink,
                                          'snippet'  => base64_encode($snippet),
                                          'text'     => $message,
                                    ];

                                    if ($shouldClear) continue;
                              }
                              $newLines[] = $lineContent;
                        }

                        if ($shouldClear && $foundInFile) {
                              File::put($file->getRealPath(), implode("\n", $newLines));
                              $modifiedFiles[] = $file->getRelativePathname();
                        }
                  }
            }

            // Save JSON for Dashboard
            $jsonDir = storage_path('app/ghost-notes');
            if (!File::exists($jsonDir)) File::makeDirectory($jsonDir, 0755, true);
            File::put($jsonDir . '/data.json', json_encode($notes));

            $this->generateMarkdown($notes, base_path($filename));

            // Success Messages
            if ($shouldClear && count($modifiedFiles) > 0) {
                  $this->info("🧹 Cleared notes from: " . count($modifiedFiles) . " files.");
            }

            $historyPath = storage_path('app/ghost-notes/history.json');
            $history = File::exists($historyPath) ? json_decode(File::get($historyPath), true) : [];
            if ($shouldClear && count($notes) > 0) {
                  foreach ($notes as $note) {
                        $note['resolved_at'] = date('Y-m-d H:i');
                        $history[] = $note;
                  }

                  File::put($historyPath, json_encode($history, JSON_PRETTY_PRINT));
            }

            $this->info("✅ Success! {$filename} and Dashboard cache updated.");

            // Gitignore Check
            $gitignorePath = base_path('.gitignore');
            if (File::exists($gitignorePath)) {
                  $gitignoreContent = File::get($gitignorePath);
                  if (!str_contains($gitignoreContent, $filename)) {
                        if ($this->confirm("Do you want to add {$filename} to .gitignore?", true)) {
                              File::append($gitignorePath, "\n{$filename}\n");
                              $this->info("📌 Added to .gitignore");
                        }
                  }
            }

            $this->export($notes, $format);

            // রিলিজের পর অটো কনফিগ পাবলিশ কমানো যেতে পারে, তবে এটি আগের মতোই রইল
            $this->call('vendor:publish', ['--tag' => 'ghost-notes-config']);

            $notificationService = new NotificationService();
            $notificationService->send($notes);

            return Command::SUCCESS;
      }

      protected function generateMarkdown($notes, $path)
      {
            $markdown = "# 👻 GhostNotes - Dev Diary\n\n";
            $markdown .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";

            if (empty($notes)) {
                  $markdown .= "*No notes found in the codebase.* 🎉\n";
                  File::put($path, $markdown);
                  return;
            }

            // Group notes by type/category
            $groupedNotes = [];
            foreach ($notes as $note) {
                  $groupedNotes[$note['type']][] = $note;
            }

            $categoryTitles = [
                  'FIX'      => '🔧 Bug Fixes',
                  'FEATURE'  => '🚀 New Features',
                  'BREAKING' => '💥 Breaking Changes',
                  'TODO'     => '📝 Tasks / To-Do',
                  'CHANGE'   => '🔄 General Changes',
                  'NOTE'     => '💡 Important Notes',
                  'GENERAL'  => '📦 General Logs'
            ];

            foreach ($groupedNotes as $type => $typeNotes) {
                  $title = $categoryTitles[$type] ?? "📂 " . $type;
                  $markdown .= "## {$title}\n\n";
                  // 📅 মার্কডাউনেও ডেডলাইন কলাম যুক্ত করা হলো
                  $markdown .= "| Date | Tag | Priority | Due Date | Author | File | Note |\n";
                  $markdown .= "|------|-----|----------|----------|--------|------|------|\n";

                  foreach ($typeNotes as $note) {
                        $fileCell = $note['link'] ? "[{$note['file']}]({$note['link']})" : $note['file'];
                        $dueDateCell = $note['due_date'] ? "`{$note['due_date']}`" : "---";
                        $markdown .= "| {$note['date']} | **{$note['tag']}** | {$note['priority']} | {$dueDateCell} | {$note['author']} | {$fileCell} | {$note['text']} |\n";
                  }
                  $markdown .= "\n";
            }

            File::put($path, $markdown);
      }

      protected function export($notes, $format)
      {
            $filename = config('ghost-notes.filename', 'GHOST_LOG');
            $baseFileName = str_replace(['.md', '.json', '.csv'], '', $filename);

            switch ($format) {
                  case 'json':
                        $path = base_path($baseFileName . '.json');
                        File::put($path, json_encode($notes, JSON_PRETTY_PRINT));
                        $this->info("📂 Exported as JSON: {$path}");
                        break;

                  case 'csv':
                        $path = base_path($baseFileName . '.csv');
                        $this->generateCsv($notes, $path);
                        $this->info("📊 Exported as CSV: {$path}");
                        break;

                  default:
                        $path = base_path($baseFileName . '.md');
                        $this->generateMarkdown($notes, $path);
                        $this->info("📝 Exported as Markdown: {$path}");
                        break;
            }
      }

      protected function generateCsv($notes, $path)
      {
            $handle = fopen($path, 'w');
            // 📅 সিএসভিতেও ডেডলাইন কলাম যুক্ত করা হলো
            fputcsv($handle, ['Date', 'Tag', 'Priority', 'Due Date', 'Author', 'File', 'Note']);

            foreach ($notes as $note) {
                  fputcsv($handle, [
                        $note['date'],
                        $note['tag'],
                        $note['priority'],
                        $note['due_date'] ?? '---',
                        $note['author'],
                        $note['file'],
                        $note['text']
                  ]);
            }
            fclose($handle);
      }
}
