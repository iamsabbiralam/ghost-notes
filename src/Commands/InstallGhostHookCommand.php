<?php

namespace Iamsabbiralam\GhostNotes\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallGhostHookCommand extends Command
{
      // command signature
      protected $signature = 'ghost:install-hook';

      // command description
      protected $description = 'Install a Git hook to automatically update GhostNotes on every commit';

      public function handle()
      {
            $gitHooksDir = base_path('.git/hooks');

            // check if the project has a Git repository initialized
            if (!File::isDirectory($gitHooksDir)) {
                  $this->error('❌ Git repository not found in this project. Please run "git init" first.');
                  return 1;
            }

            $hookFile = $gitHooksDir . '/post-commit';

            // the script that will run in the hook (your main command)
            $script = "#!/bin/sh\n\nphp artisan ghost:write\n";

            // check if the hook file exists and if it already contains our command
            if (File::exists($hookFile)) {
                  $currentContent = File::get($hookFile);

                  // check if our command is already integrated
                  if (str_contains($currentContent, 'ghost:write')) {
                        $this->info('ℹ️ Git hook is already installed and up to date!');
                        return 0;
                  }

                  // append our command to the existing script
                  File::append($hookFile, "\n" . $script);
            } else {
                  // create a new post-commit hook file with our command
                  File::put($hookFile, $script);
            }

            // set executable permissions for the file on Linux or macOS
            if (function_exists('chmod')) {
                  @chmod($hookFile, 0755);
            }

            $this->info('🚀 Success! Git hook installed. GhostNotes will now auto-generate on every commit.');
            return 0;
      }
}
