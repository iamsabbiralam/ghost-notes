<?php

namespace Iamsabbiralam\GhostNotes\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GhostInstallCommand extends Command
{
      protected $signature = 'ghost:install';
      protected $description = 'GhostNotes package installation command';

      public function handle()
      {
            $this->info('👻 GhostNotes installing...');
            $this->call('vendor:publish', [
                  '--tag' => 'ghost-notes-config'
            ]);

            $path = storage_path('app/ghost-notes');
            if (!File::exists($path)) {
                  File::makeDirectory($path, 0755, true);
                  $this->info('✅ storage directory has made at: ' . $path);
            }

            $this->newLine();
            $this->info('🚀 GhostNotes installed successfully!');
            $this->info('👉 for visiting dashboard: ' . url('/ghost-notes'));
            $this->info('👉 for generate notes, run this command: php artisan ghost:write');
      }
}
