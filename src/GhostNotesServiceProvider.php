<?php

namespace Iamsabbiralam\GhostNotes;

use Illuminate\Support\ServiceProvider;
use Iamsabbiralam\GhostNotes\Commands\GhostWriterCommand;

class GhostNotesServiceProvider extends ServiceProvider
{
      public function boot()
      {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
            $this->loadViewsFrom(__DIR__ . '/views', 'ghost-notes');

            if ($this->app->runningInConsole()) {
                  $this->publishes([
                        __DIR__ . '/config/ghost-notes.php' => config_path('ghost-notes.php'),
                  ], 'ghost-notes-config');

                  $this->commands([
                        \Iamsabbiralam\GhostNotes\Commands\GhostWriterCommand::class,
                  ]);
            }
      }

      public function register()
      {
            $this->mergeConfigFrom(__DIR__ . '/config/ghost-notes.php', 'ghost-notes');
      }
}
