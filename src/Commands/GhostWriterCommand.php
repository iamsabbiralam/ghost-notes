<?php

namespace iamsabbiralam\GhostNotes\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GhostWriterCommand extends Command
{
      protected $signature = 'ghost:write';
      protected $description = 'Generate a dev-diary from @ghost tags in your code';

      public function handle()
      {
            // ... (Agei je logic-ta diyechi sheita ekhane hobe) ...
            // Shudhu app_path() er poriborte base_path('app') use kora safe
      }
}
