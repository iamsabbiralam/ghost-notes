<?php

namespace IamSabbirAlam\GhostNotes\Services;

use Illuminate\Support\Facades\Http;

class NotificationService
{
      public function send($notes)
      {
            if (!config('ghost-notes.notifications.enabled', false) || empty($notes)) {
                  return;
            }

            $allowedPriorities = config('ghost-notes.notifications.notify_priorities', ['HIGH']);
            // only filter notes that have allowed priorities (like HIGH)
            $importantNotes = array_filter($notes, function ($note) use ($allowedPriorities) {
                  return in_array($note['priority'], $allowedPriorities);
            });

            if (empty($importantNotes)) {
                  return;
            }

            // format the message
            $messageBody = "👻 **GhostNotes Alert: New High-Priority Debt Found!**\n";
            $messageBody .= "=======================================\n\n";

            foreach ($importantNotes as $note) {
                  $messageBody .= "🚨 **[" . $note['priority'] . "] " . $note['type'] . "**\n";
                  $messageBody .= "📂 **File:** `" . $note['file'] . "`\n";
                  $messageBody .= "📝 **Note:** *" . $note['text'] . "*\n";
                  $messageBody .= "👤 **Author:** " . $note['author'] . "\n";

                  if (!empty($note['link'])) {
                        $messageBody .= "🔗 [View on GitHub](" . $note['link'] . ")\n";
                  }

                  $messageBody .= "\n---------------------------------------\n\n";
            }

            // Slack notification
            $slackUrl = config('ghost-notes.notifications.channels.slack.webhook_url');
            if (!empty($slackUrl)) {
                  // for slack markdown support
                  Http::post($slackUrl, ['text' => str_replace('**', '*', $messageBody)]);
            }

            // Discord notification
            $discordUrl = config('ghost-notes.notifications.channels.discord.webhook_url');
            if (!empty($discordUrl)) {
                  // for discord markdown support
                  Http::post($discordUrl, [
                        'content' => $messageBody
                  ]);
            }
      }
}
