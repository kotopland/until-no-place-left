<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLoginLinkNotification extends Notification
{
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $displayName = $this->name ?: (property_exists($notifiable, 'name') && $notifiable->name ? $notifiable->name : 'Missionary Disciple');

        $mail = (new MailMessage)
            ->subject($this->isNewUser ? '✨ Your Nagoya Oikos Activation Link — Enter the Mission' : '⚡ Your Nagoya Oikos Magic Sign-In Link')
            ->greeting("Shalom, {$displayName}!");

        if ($this->isNewUser) {
            $mail->line('Welcome to Nagoya Oikos: The Four Fields. You are taking your first steps toward reaching the 2.3 million souls of Nagoya.')
                ->line("Your player profile **{$displayName}** is ready to be activated.");
        } else {
            $mail->line('We received a request to access your Nagoya Oikos mission dashboard.');
        }

        return $mail
            ->line('Click the secure button below to instantly sign in to your mission headquarters without passwords:')
            ->action('⚡ Enter Nagoya Mission', $this->url)
            ->line('**Security Notice:** This link is cryptographically signed, valid for 30 minutes, and can only be used by you.')
            ->line('If you did not request this link, you can safely disregard this email.');
    }
}
