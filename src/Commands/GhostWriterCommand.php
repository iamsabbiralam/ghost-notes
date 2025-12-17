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

            $directory = base_path('app');
            $files = File::allFiles($directory);
            $notes = [];
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

                  // Regex for Tag, Priority and Message
                  $pattern = '/(' . implode('|', array_map('preg_quote', $tags)) . ')(?::(high|medium|low))?:(.*)/i';

                  foreach ($lines as $lineNumber => $lineContent) {
                        if (preg_match($pattern, $lineContent, $match)) {
                              $foundInFile = true;
                              $tagName = strtoupper(str_replace('@', '', $match[1]));
                              $priority = strtoupper($match[2] ?: 'NORMAL');
                              $message = trim($match[3]);

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

                              // Code Snippet (2 lines before and 2 lines after)
                              $start = max(0, $lineNumber - 2);
                              $end = min(count($lines) - 1, $lineNumber + 2);
                              $snippetLines = array_slice($lines, $start, ($end - $start) + 1);
                              $snippet = implode("\n", $snippetLines);

                              // VS Code Local Link
                              $absolutePath = $file->getRealPath();
                              $vscodeLink = "vscode://file/{$absolutePath}:" . ($lineNumber + 1);

                              $notes[] = [
                                    'date'     => date('Y-m-d H:i'),
                                    'tag'      => $tagName,
                                    'priority' => $priority,
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

            // 1. Save JSON for Dashboard (Super Fast & Detailed)
            $jsonDir = storage_path('app/ghost-notes');
            if (!File::exists($jsonDir)) File::makeDirectory($jsonDir, 0755, true);
            File::put($jsonDir . '/data.json', json_encode($notes));

            // 2. Save Markdown for GitHub (Clean & Simple)
            $markdown = "# 👻 GhostNotes - Dev Diary\n\n";
            $markdown .= "| Date | Tag | Priority | Author | File | Note |\n";
            $markdown .= "|------|-----|----------|--------|------|------|\n";

            foreach ($notes as $note) {
                  $fileCell = $note['link'] ? "[{$note['file']}]({$note['link']})" : $note['file'];
                  $markdown .= "| {$note['date']} | **{$note['tag']}** | {$note['priority']} | {$note['author']} | {$fileCell} | {$note['text']} |\n";
            }
            File::put(base_path($filename), $markdown);

            // Success Messages
            if ($shouldClear && count($modifiedFiles) > 0) {
                  $this->info("🧹 Cleared notes from: " . count($modifiedFiles) . " files.");
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
      }
}
