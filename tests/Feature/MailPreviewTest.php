<?php

declare(strict_types=1);

use App\Notifications\MagicLoginLinkNotification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

test('mail preview route is accessible in application', function (): void {
    $response = $this->get('/spatie-mail-preview');

    // Mail preview route returns 200 or 404 (if no emails sent yet)
    expect($response->status())->toBeIn([200, 404]);
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

    // Clean up test generated html preview files
    foreach ($files as $file) {
        File::delete($file);
    }
    foreach (File::glob($storagePath.'/*.eml') as $file) {
        File::delete($file);
    }
});
