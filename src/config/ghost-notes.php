<?php

return [
      'tags' => ['@ghost', '@todo', '@fixme', '@note'],
      'filename' => 'GHOST_LOG.md',

      // Directories to scan for annotations (developers can add more folders here if needed)
      'scan_directories' => [
            'app',
            'routes',
            'resources/views',
      ],

      // Allowed file extensions to scan for annotations
      'allowed_extensions' => ['php', 'blade.php', 'js'],

      'ignore_folders' => ['vendor', 'node_modules', 'storage', 'tests'],
      'git_context' => true,
      'repo_url' => env('GHOST_NOTES_REPO_URL', ''),
      'default_branch' => 'main',
];
