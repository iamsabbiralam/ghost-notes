<?php

namespace Iamsabbiralam\GhostNotes\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GhostWriterCommand extends Command
{
      protected $signature = 'ghost:write';
      protected $description = 'Generate a dev-diary from @ghost tags in your code';

      public function handle()
      {
            $this->info('Ghost-Writer is scanning your files...');

            $directory = base_path('app');
            $files = File::allFiles($directory);
            $notes = [];

            foreach ($files as $file) {
                  $content = File::get($file);

                  if (preg_match_all('/@ghost:(.*)/', $content, $matches)) {
                        foreach ($matches[1] as $note) {
                              $notes[] = [
                                    'file' => $file->getRelativePathname(),
                                    'text' => trim($note),
                                    'date' => date('Y-m-d H:i'),
                              ];
                        }
                  }
            }

            if (empty($notes)) {
                  $this->warn("No @ghost tags found in your app folder!");
                  return;
            }

            $markdown = "# 👻 Ghost-Writer Dev Diary\n\n";
            $markdown .= "*Generated on: " . date('Y-m-d H:i:s') . "*\n\n---\n\n";

            foreach ($notes as $note) {
                  $markdown .= "### 📅 " . $note['date'] . "\n";
                  $markdown .= "- **File:** `" . $note['file'] . "`\n";
                  $markdown .= "- **Note:** " . $note['text'] . "\n\n";
                  $markdown .= "---\n\n";
            }

            File::put(base_path('GHOST_LOG.md'), $markdown);
            $this->info("Success! GHOST_LOG.md has been generated in your project root.");
      }
}
