<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\AttachesUnsubscribeButton;
use App\Services\EmailVisualService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLoginLinkNotification extends Notification
{
    use AttachesUnsubscribeButton;
    use Queueable;

    /**
     * Create a new magic login link notification.
     */
    public function __construct(
        public readonly string $url,
        public readonly ?string $name = null,
        public readonly bool $isNewUser = false,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof User && $notifiable->isComputerGeneratedPlayer()) {
            return [];
        }

        if (method_exists($notifiable, 'routeNotificationFor')) {
            $mailRoute = $notifiable->routeNotificationFor('mail', $this);
            if (is_string($mailRoute) && str_ends_with(strtolower($mailRoute), '.invalid')) {
                return [];
            }
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $displayName = $this->name ?: (property_exists($notifiable, 'name') && $notifiable->name ? $notifiable->name : 'Missionary Disciple');
        $visuals = app(EmailVisualService::class);
        $focusWard = $notifiable instanceof User ? $notifiable->homeWard : null;

        $people = [[
            'name' => $displayName,
            'subtitle' => $focusWard->name ?? 'Nagoya Mission Frontier',
            'badge' => $this->isNewUser ? 'New Missionary' : 'Missionary',
            'avatarUrl' => $notifiable instanceof User
                ? $visuals->avatarUrlForPerson($notifiable, 128)
                : $visuals->avatarUrl(seed: $displayName, role: 'missionary', size: 128),
        ]];

        $mail = (new MailMessage)
            ->subject($this->isNewUser ? '✨ Your Nagoya Oikos Activation Link — Enter the Mission' : '⚡ Your Nagoya Oikos Magic Sign-In Link')
            ->greeting("Shalom, {$displayName}!")
            ->line($visuals->renderPeopleAvatarsHtml($people, 'Your Missionary Profile'));

        if ($this->isNewUser) {
            $mail->line('Welcome to Nagoya Oikos: The Four Fields. You are taking your first steps toward reaching the 2.3 million souls of Nagoya.')
                ->line("Your player profile **{$displayName}** is ready to be activated.");
        } else {
            $mail->line('We received a request to access your Nagoya Oikos mission dashboard.');
        }

        $mail->line('Click the secure button below to instantly sign in to your mission headquarters without passwords:')
            ->action('⚡ Enter Nagoya Mission', $this->url)
            ->line($visuals->renderWardMapHtml(
            focusWard: $focusWard,
            accentHex: '#6366f1',
            title: $focusWard ? "Nagoya: {$focusWard->name}" : 'Nagoya • 16 Wards',
            caption: $focusWard
                ? "Your mission base in {$focusWard->name} ({$focusWard->name_ja}) is ready."
                : '2.3 million people across 16 wards in Nagoya, Japan.'
        ))
            ->line('**Security Notice:** This link is cryptographically signed, valid for 30 minutes, and can only be used by you.')
            ->line('If you did not request this link, you can safely disregard this email.');

        return $this->attachUnsubscribeButton($mail, $notifiable, 'emails from Nagoya Oikos');
    }
}
