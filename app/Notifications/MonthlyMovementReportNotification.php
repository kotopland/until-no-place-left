<?php

namespace App\Notifications;

use App\Models\MonthlyReport;
use App\Models\MovementCelebration;
use App\Models\User;
use App\Notifications\Concerns\AttachesUnsubscribeButton;
use App\Services\EmailVisualService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MonthlyMovementReportNotification extends Notification implements ShouldQueue
{
    use AttachesUnsubscribeButton;
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

        $isComputerPlayer = $notifiable instanceof User && $notifiable->isComputerGeneratedPlayer();

        if (! $isComputerPlayer && ($notifiable->notify_email ?? true)) {
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
        $scorePrefix = $scoreGain >= 0 ? '+' : '';
        $totalScore = $this->report->total_generational_score;
        $multiplied = $this->report->disciples_multiplied_count;
        $churches = $this->report->churches_planted_count;

        $name = property_exists($notifiable, 'name') ? (string) $notifiable->name : 'Missionary';
        $visuals = app(EmailVisualService::class);
        $player = $notifiable instanceof User ? $notifiable : $this->report->user;

        $people = [];
        if ($player instanceof User) {
            $people[] = [
                'name' => $player->name,
                'subtitle' => $player->homeWard->name ?? 'Nagoya',
                'badge' => 'Missionary',
                'avatarUrl' => $visuals->avatarUrlForPerson($player, 128),
            ];
        } else {
            $people[] = [
                'name' => $name,
                'subtitle' => 'Nagoya Mission',
                'badge' => 'Missionary',
                'avatarUrl' => $visuals->avatarUrl(seed: $name, role: 'missionary', size: 128),
            ];
        }

        $celebrations = MovementCelebration::where('game_session_id', $this->report->game_session_id)
            ->where('game_month', $month)
            ->with(['npc', 'ward', 'church'])
            ->latest()
            ->take(3)
            ->get();

        $highlightWardIds = $player?->home_ward_id ? [(int) $player->home_ward_id] : [];
        $markers = [];

        foreach ($celebrations as $cel) {
            $people[] = [
                'name' => $cel->person_name,
                'subtitle' => $cel->ward_name,
                'badge' => ucfirst(str_replace('_', ' ', $cel->type)),
                'avatarUrl' => $cel->npc
                    ? $visuals->avatarUrlForPerson($cel->npc, 128)
                    : $visuals->avatarUrl(seed: $cel->person_name, size: 128),
            ];

            if ($cel->ward_id) {
                $highlightWardIds[] = (int) $cel->ward_id;
            }

            if ($cel->church && $cel->church->lat && $cel->church->lng) {
                $markers[] = [
                    'lat' => (float) $cel->church->lat,
                    'lng' => (float) $cel->church->lng,
                    'label' => $cel->church->name,
                    'type' => 'church',
                ];
            } elseif ($cel->npc && $cel->npc->lat && $cel->npc->lng) {
                $markers[] = [
                    'lat' => (float) $cel->npc->lat,
                    'lng' => (float) $cel->npc->lng,
                    'label' => $cel->npc->name,
                    'type' => 'person',
                ];
            }
        }

        $focusWard = $player instanceof User && $player->homeWard
            ? $player->homeWard
            : $celebrations->first()?->ward;

        $mail = (new MailMessage)
            ->subject("🌱 Nagoya Movement Report — Month {$month} Summary")
            ->greeting("Konnichiwa, {$name}!")
            ->line("Game Month {$month} in Nagoya has concluded and the server tick has resolved your relational actions.")
            ->line($visuals->renderPeopleAvatarsHtml($people, "Month {$month} Laborers & Breakthroughs"))
            ->line($visuals->renderWardMapHtml(
                focusWard: $focusWard,
                markers: $markers,
                highlightWardIds: $highlightWardIds,
                accentHex: '#10b981',
                title: "Nagoya Month {$month} Frontier",
                caption: $focusWard
                    ? "Movement activity centered in {$focusWard->name} ({$focusWard->name_ja}) and connected wards."
                    : 'Nagoya 16-Ward Movement Frontier'
            ))
            ->line('**Monthly Kingdom Impact Summary:**')
            ->line("• **Generational Score Gained:** {$scorePrefix}{$scoreGain} pts (Total: {$totalScore} pts)")
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

        $mail->action('Open Nagoya Movement HQ', url('/'))
            ->line('Your Action Points (Time & Energy Bandwidth) have been fully restored. Plan your next month of relational sowing now!');

        return $this->attachUnsubscribeButton($mail, $notifiable, 'monthly movement reports');
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
