<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

trait AttachesUnsubscribeButton
{
    /**
     * Attach an "Unsubscribe from all email notifications" button and List-Unsubscribe header to the MailMessage.
     */
    protected function attachUnsubscribeButton(MailMessage $mail, object $notifiable, string $contextLabel = 'movement updates'): MailMessage
    {
        $user = $notifiable instanceof User
            ? $notifiable
            : (property_exists($notifiable, 'routes') && isset($notifiable->routes['mail'])
                ? User::where('email', $notifiable->routes['mail'])->first()
                : null);

        if (! $user) {
            return $mail;
        }

        $optOutUrl = $user->signedNotificationOptOutUrl();

        $mail->withSymfonyMessage(function ($msg) use ($optOutUrl): void {
            $msg->getHeaders()->addTextHeader('List-Unsubscribe', "<{$optOutUrl}>");
            $msg->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        });

        // Clear statement that this is simply a game for entertainment and educational purposes
        $mail->line(new HtmlString(
            '<div style="text-align: center; margin-top: 24px; padding: 10px 14px; background-color: #0f172a; border: 1px solid #1e293b; border-radius: 8px; font-size: 11px; color: #94a3b8; line-height: 1.5;">'.
            '🎮 <strong>Game Notice:</strong> This is simply a game for entertainment and educational purposes.'.
            '</div>'
        ));

        $mail->line(new HtmlString(
            '<table align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 14px; text-align: center;">'.
            '<tr><td align="center">'.
            '<a href="'.$optOutUrl.'" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 10px 20px; font-size: 12px; font-weight: 700; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #cbd5e1; background-color: #1e293b; border: 1px solid #334155; border-radius: 8px; text-decoration: none;">'.
            '🔕 Unsubscribe from all email notifications'.
            '</a>'.
            '</td></tr></table>'
        ));

        $mail->line(new HtmlString(
            '<div style="text-align: center; margin-top: 6px; font-size: 11px; color: #94a3b8;">'.
            "Prefer not to receive {$contextLabel}? <a href=\"{$optOutUrl}\" style=\"color: #64748b; text-decoration: underline; word-break: break-all;\">Unsubscribe from all emails</a>".
            '</div>'
        ));

        return $mail;
    }
}
