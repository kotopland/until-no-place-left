<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PlayerInactivityReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public int $daysInactive = 2
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->notify_email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $name = ! empty($notifiable->name)
            ? (string) $notifiable->name
            : 'Missionary Disciple';

        $wardName = null;
        if ($notifiable instanceof User && $notifiable->homeWard) {
            $wardName = "{$notifiable->homeWard->name} ({$notifiable->homeWard->name_ja})";
        }

        $optOutUrl = $notifiable instanceof User
            ? URL::signedRoute('notifications.opt-out', ['user' => $notifiable->id])
            : url('/welcome');

        $mail = (new MailMessage)
            ->subject("🌸 Nagoya misses you, {$name}! The harvest is waiting")
            ->greeting("Konnichiwa, {$name}!")
            ->line("It has been {$this->daysInactive} real-life days since your last missionary presence in Nagoya.")
            ->line('In Japanese culture, relational trust (*Oikos*) is built through steady, faithful presence. When workers step back, seeking friends and young disciples notice the silence.');

        if ($wardName) {
            $mail->line("Your home base in **{$wardName}** and your neighborhood prayer routes are ready for your return.");
        }

        $actionUrl = $notifiable instanceof User && ! empty($notifiable->email)
            ? route('login')
            : url('/');

        return $mail
            ->line('Your **20 Action Points (AP)** are ready to steward into prayer walking, sharing the 3 Circles Gospel, and discipling local Japanese seekers.')
            ->action('⚡ Return to Nagoya Action HQ', $actionUrl)
            ->line('*"The harvest is plentiful, but the workers are few. Therefore pray earnestly to the Lord of the harvest to send out workers into His harvest." — Matthew 9:37-38*')
            ->line('---')
            ->line("Prefer not to receive these movement reminder emails? You can [opt out of email notifications]({$optOutUrl}) at any time.");
    }

    /**
     * Get the array representation of the notification for Database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inactivity_reminder',
            'days_inactive' => $this->daysInactive,
            'title' => "Nagoya misses you ({$this->daysInactive} days inactive)",
            'message' => "It has been {$this->daysInactive} days since your last missionary action. Nagoya's 16 wards are waiting for your faithful presence!",
        ];
    }
}
