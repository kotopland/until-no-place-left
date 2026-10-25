<?php

declare(strict_types=1);

use App\Notifications\MagicLoginLinkNotification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunTransportFactory;

test('mail preview route returns clean 200 empty state when no emails are in storage', function (): void {
    $storagePath = storage_path('email-previews');
    File::ensureDirectoryExists($storagePath);
    File::cleanDirectory($storagePath);

    $response = $this->get('/spatie-mail-preview');

    expect($response->status())->toBe(200)
        ->and($response->getContent())->toContain('Local Mail Preview');
});

test('mail preview driver captures sent mail and writes preview file', function (): void {
    config(['mail.default' => 'preview']);
    config(['mail-preview.enabled' => true]);

    $storagePath = storage_path('email-previews');
    File::ensureDirectoryExists($storagePath);

    Notification::route('mail', 'tester@example.com')->notify(
        new MagicLoginLinkNotification(
            url: 'http://epic-ledger.test/auth/magic-link?signature=test',
            name: 'Test Missionary',
            isNewUser: false,
        )
    );

    $files = File::glob($storagePath.'/*.html');
    expect(count($files))->toBeGreaterThan(0);

    // Clean up test generated files
    File::cleanDirectory($storagePath);
});

test('visiting mail preview renders latest captured email', function (): void {
    config(['mail.default' => 'preview']);
    config(['mail-preview.enabled' => true]);

    $storagePath = storage_path('email-previews');
    File::ensureDirectoryExists($storagePath);

    Notification::route('mail', 'seeker@nagoyaharvest.jp')->notify(
        new MagicLoginLinkNotification(
            url: 'http://epic-ledger.test/auth/magic-link?test=1',
            name: 'Seeker Nagoya',
            isNewUser: true,
        )
    );

    $response = $this->get('/spatie-mail-preview');
    expect($response->status())->toBe(200)
        ->and($response->getContent())->toContain('Enter Nagoya Mission');

    // Clean up test generated files
    File::cleanDirectory($storagePath);
});

test('mailgun driver resolves transport with configured credentials', function (): void {
    if (! class_exists(MailgunTransportFactory::class)) {
        test()->markTestSkipped('symfony/mailgun-mailer is not installed yet in this environment.');
    }

    config([
        'mail.mailers.mailgun' => [
            'transport' => 'mailgun',
        ],
        'services.mailgun' => [
            'domain' => 'mg.epic-network.info',
            'secret' => 'test-secret-key',
            'endpoint' => 'api.mailgun.net',
            'scheme' => 'https',
        ],
    ]);

    $mailer = app('mail.manager')->mailer('mailgun');
    expect($mailer)->not->toBeNull();
});
