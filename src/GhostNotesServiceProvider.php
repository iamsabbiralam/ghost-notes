<?php

namespace Iamsabbiralam\GhostNotes;

use Illuminate\Support\ServiceProvider;
use Iamsabbiralam\GhostNotes\Commands\GhostWriterCommand;
use Iamsabbiralam\GhostNotes\Commands\GhostInstallCommand;

class GhostNotesServiceProvider extends ServiceProvider
{
      public function boot()
      {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
            $this->loadViewsFrom(__DIR__ . '/views', 'ghost-notes');
            $this->commands([
                  GhostWriterCommand::class,
                  GhostInstallCommand::class,
            ]);

            if ($this->app->runningInConsole()) {
                  $this->publishes([
                        __DIR__ . '/config/ghost-notes.php' => config_path('ghost-notes.php'),
                  ], 'ghost-notes-config');
            }
      }

      public function register()
      {
            $this->mergeConfigFrom(__DIR__ . '/config/ghost-notes.php', 'ghost-notes');
      }
}
