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
      /*
    |--------------------------------------------------------------------------
    | Third-Party Notifications (Webhooks)
    |--------------------------------------------------------------------------
    */
      'notifications' => [
            'enabled' => env('GHOST_NOTES_NOTIFY_FREE', false),
            // Which levels of notes should be sent to the channels (e.g., only HIGH or MEDIUM)
            'notify_priorities' => ['HIGH'],
            'channels' => [
                  'slack' => [
                        'webhook_url' => env('GHOST_NOTES_SLACK_WEBHOOK', ''),
                  ],
                  'discord' => [
                        'webhook_url' => env('GHOST_NOTES_DISCORD_WEBHOOK', ''),
                  ],
            ],
      ],

      /*
    |--------------------------------------------------------------------------
    | Custom Tags & Dashboard Color Coding
    |--------------------------------------------------------------------------
    | Define your own tags, their dashboard label, and background/text colors.
    | Use standard HEX color codes.
    */
      'custom_tags' => [
            'review' => [
                  'label' => 'Code Review Needed',
                  'bg_color' => '#8b5cf6',   // Purple
                  'text_color' => '#ffffff',
            ],
            'optimize' => [
                  'label' => 'Performance Optimization',
                  'bg_color' => '#06b6d4',   // Cyan
                  'text_color' => '#0f172a',
            ],
            'todo' => [
                  'label' => 'To Do Task',
                  'bg_color' => '#f59e0b',   // Amber/Orange
                  'text_color' => '#ffffff',
            ],
      ],
];
