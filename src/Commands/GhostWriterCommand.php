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
            $tags = config('ghost-notes.tags', ['@ghost']); // get tags from config
            $filename = config('ghost-notes.filename', 'GHOST_LOG.md');
            $this->info("Scanning for tags: " . implode(', ', $tags));

            $directory = base_path('app');
            $files = File::allFiles($directory);
            $notes = [];

            // dynamic regex pattern making
            $pattern = '/' . implode('|', array_map('preg_quote', $tags)) . ':(.*)/';
            foreach ($files as $file) {
                  $content = File::get($file);

                  if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $match) {
                              // which tag was found
                              $foundTag = explode(':', $match[0])[0];
                              $notes[] = [
                                    'tag'   => strtoupper(str_replace('@', '', $foundTag)),
                                    'file'  => $file->getRelativePathname(),
                                    'text'  => trim($match[1]),
                                    'date'  => date('Y-m-d H:i'),
                              ];
                        }
                  }
            }

            if (empty($notes)) {
                  $this->warn("No notes found!");
                  return;
            }

            // Markdown Table Format
            $markdown = "# 👻 GhostNotes - Dev Diary\n\n";
            $markdown .= "| Date | Tag | File | Note |\n";
            $markdown .= "|------|-----|------|------|\n";
            foreach ($notes as $note) {
                  $markdown .= "| {$note['date']} | **{$note['tag']}** | `{$note['file']}` | {$note['text']} |\n";
            }

            File::put(base_path($filename), $markdown);
            $this->info("Success! {$filename} generated with " . count($notes) . " notes.");
      }
}
