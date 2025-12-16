<?php

namespace Iamsabbiralam\GhostNotes;

use Illuminate\Support\ServiceProvider;
use Iamsabbiralam\GhostNotes\Commands\GhostWriterCommand;

class GhostNotesServiceProvider extends ServiceProvider
{
      public function boot()
      {
            if ($this->app->runningInConsole()) {
                  $this->commands([
                        GhostWriterCommand::class,
                  ]);
            }
      }

      public function register()
      {
            //
      }
}
