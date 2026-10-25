<?php

namespace App\Notifications;

use App\Models\MonthlyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MonthlyMovementReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public MonthlyReport $report
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notify_email ?? true) {
            $channels[] = 'mail';
        }

        if ($notifiable->notify_in_app ?? true) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $month = $this->report->game_month;
        $scoreGain = $this->report->generational_score_gained;
        $totalScore = $this->report->total_generational_score;
        $multiplied = $this->report->disciples_multiplied_count;
        $churches = $this->report->churches_planted_count;

        $name = property_exists($notifiable, 'name') ? (string) $notifiable->name : 'Missionary';

        $mail = (new MailMessage)
            ->subject("🌱 Nagoya Movement Report — Month {$month} Summary")
            ->greeting("Konnichiwa, {$name}!")
            ->line("Game Month {$month} in Nagoya has concluded and the server tick has resolved your relational actions.")
            ->line('**Monthly Kingdom Impact Summary:**')
            ->line("• **Generational Score Gained:** +{$scoreGain} pts (Total: {$totalScore} pts)")
            ->line("• **Autonomous Disciples Multiplied:** {$multiplied}")
            ->line("• **Churches Planted:** {$churches}");

        if (! empty($this->report->highlights_json)) {
            $mail->line('**Key Relational Breakthroughs & Answers to Prayer:**');
            foreach (array_slice($this->report->highlights_json, 0, 4) as $highlight) {
                $mail->line("• {$highlight}");
            }
        }

        if (! empty($this->report->coachTips_json)) {
            $mail->line('**Kingdom Principles Coach Tip:**');
            $firstTip = $this->report->coachTips_json[0] ?? null;
            if ($firstTip) {
                $mail->line("> _{$firstTip['tip']}_");
            }
        }

        return $mail
            ->action('Open Nagoya Movement HQ', url('/'))
            ->line('Your 12 monthly Action Points (Time & Energy Bandwidth) have been fully restored. Plan your next month of relational sowing now!');
    }

    /**
     * Get the array representation of the notification for Database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'game_session_id' => $this->report->game_session_id,
            'game_month' => $this->report->game_month,
            'title' => $this->report->summary_title,
            'score_gained' => $this->report->generational_score_gained,
            'total_score' => $this->report->total_generational_score,
            'disciples_multiplied' => $this->report->disciples_multiplied_count,
            'churches_planted' => $this->report->churches_planted_count,
            'highlights_count' => count($this->report->highlights_json ?? []),
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Real-time Broadcast representation (Laravel Reverb).
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'report_id' => $this->report->id,
            'game_session_id' => $this->report->game_session_id,
            'game_month' => $this->report->game_month,
            'title' => $this->report->summary_title,
            'score_gained' => $this->report->generational_score_gained,
            'message' => "Month {$this->report->game_month} resolved! You gained +{$this->report->generational_score_gained} pts.",
        ]);
    }
}
