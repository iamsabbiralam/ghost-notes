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

            // --- GitHub Auto-detect ---
            $repoUrl = config('ghost-notes.repo_url');
            if (empty($repoUrl)) {
                  $remote = shell_exec('git remote get-url origin');
                  if ($remote) {
                        $repoUrl = str_replace([':', 'git@'], ['/', 'https://'], trim($remote));
                        $repoUrl = str_replace('.git', '', $repoUrl);
                  }
            }

            $this->info("Scanning for tags: " . implode(', ', $tags));

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

                  // Priority Support Regex
                  $pattern = '/(' . implode('|', array_map('preg_quote', $tags)) . ')(?::(high|medium|low))?:(.*)/i';

                  foreach ($lines as $lineNumber => $lineContent) {
                        if (preg_match($pattern, $lineContent, $match)) {
                              $foundInFile = true;
                              $tagName = strtoupper(str_replace('@', '', $match[1]));
                              $priority = strtoupper($match[2] ?: 'NORMAL');
                              $message = trim($match[3]);

                              // Link Build
                              $githubLink = "";
                              if (!empty($repoUrl)) {
                                    $branch = config('ghost-notes.default_branch', 'main');
                                    $realLine = $lineNumber + 1;
                                    $githubLink = "{$repoUrl}/blob/{$branch}/" . $file->getRelativePathname() . "#L{$realLine}";
                              }

                              // Author Logic
                              $author = "Unknown";
                              if (config('ghost-notes.git_context', true)) {
                                    $realLineNumber = $lineNumber + 1;
                                    $command = "git blame -L {$realLineNumber},{$realLineNumber} --porcelain " . escapeshellarg($file->getRealPath());
                                    $output = shell_exec($command);
                                    if ($output) {
                                          preg_match('/^author (.*)$/m', $output, $authorMatch);
                                          $author = $authorMatch[1] ?? "Unknown";
                                    }
                              }

                              $notes[] = [
                                    'date'     => date('Y-m-d H:i'),
                                    'tag'      => $tagName,
                                    'priority' => $priority,
                                    'author'   => trim($author),
                                    'file'     => $file->getRelativePathname(),
                                    'link'     => $githubLink,
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

            // Generate Markdown Table
            $markdown = "# 👻 GhostNotes - Dev Diary\n\n";
            $markdown .= "| Date | Tag | Priority | Author | File | Note |\n";
            $markdown .= "|------|-----|----------|--------|------|------|\n";

            foreach ($notes as $note) {
                  $fileCell = $note['link'] ? "[{$note['file']}]({$note['link']})" : $note['file'];
                  $markdown .= "| {$note['date']} | **{$note['tag']}** | {$note['priority']} | {$note['author']} | {$fileCell} | {$note['text']} |\n";
            }

            File::put(base_path($filename), $markdown);

            if ($shouldClear) $this->info("Cleared notes from: " . count($modifiedFiles) . " files.");
            $this->info("Success! {$filename} generated with " . count($notes) . " notes.");
      }
}
