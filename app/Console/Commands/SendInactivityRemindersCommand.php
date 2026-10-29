<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PlayerInactivityReminderNotification;
use Illuminate\Console\Command;

class SendInactivityRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:send-inactivity-reminders {--days=2 : Number of consecutive real-life days of inactivity before notifying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders to players who have not played for 2 or more real-life days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $days = 2;
        }

        $this->info("Checking for players who forgot to play for {$days} or more consecutive real-life days...");

        $candidates = User::query()
            ->where('is_ai_colaborer', false)
            ->where('notify_email', true)
            ->whereNotNull('email')
            ->get();

        $sentCount = 0;

        foreach ($candidates as $user) {
            if (! $user->shouldReceiveInactivityReminder($days)) {
                continue;
            }

            $daysInactive = $user->realDaysSinceLastPlayed();
            $user->notify(new PlayerInactivityReminderNotification($daysInactive));
            $user->update(['last_inactivity_reminder_sent_at' => now()]);

            $sentCount++;
            $this->line("  ✓ Sent inactivity reminder to {$user->name} ({$user->email}, {$daysInactive} days inactive)");
        }

        $this->info("Completed. Successfully sent {$sentCount} inactivity reminder(s).");

        return self::SUCCESS;
    }
}
