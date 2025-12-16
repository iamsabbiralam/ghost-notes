<?php

namespace iamsabbiralam\GhostNotes;

use Illuminate\Support\ServiceProvider;
use iamsabbiralam\GhostNotes\Commands\GhostWriterCommand;

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
