<?php

declare(strict_types=1);

use App\Http\Controllers\PasswordlessAuthController;
use App\Livewire\Auth\PasswordlessLogin;
use App\Livewire\NagoyaMovementDashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\MailPreview\Http\Controllers\ShowMailController;
use Spatie\MailPreview\MailPreviewServiceProvider;

// Public Invitation to Reach Nagoya with Jesus Until No Place Left (Root View)
Route::view('/', 'invitation')->name('invitation');

// Public Lore, Story & Comprehensive Game Rules Overview
Route::view('/welcome', 'welcome')->name('welcome');

// Public Longitudinal Movement Simulation (Shared Daily SQLite DB for Non-Registered Players)
Route::get('/simulate', NagoyaMovementDashboard::class)->name('simulate');

// Guest Passwordless Auth
Route::middleware('guest')->group(function (): void {
    Route::get('/login', PasswordlessLogin::class)->name('login');
    Route::get('/register', PasswordlessLogin::class)->name('register');
});

// Signed Magic Link Verification
Route::get('/auth/magic-link', [PasswordlessAuthController::class, 'verify'])->name('auth.magic-link');

// Authenticated Movement Dashboard & Logout
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', NagoyaMovementDashboard::class)->name('dashboard');
    Route::get('/home', fn () => redirect()->route('dashboard'))->name('home');
    Route::post('/logout', [PasswordlessAuthController::class, 'logout'])->name('logout');
});

// Mail Preview for Local Development
if (class_exists(MailPreviewServiceProvider::class)) {
    Route::get('/spatie-mail-preview', function (Request $request) {
        if (! config('mail-preview.enabled')) {
            abort(404);
        }

        $fileType = (string) $request->query('file_type', 'html');
        if (! in_array($fileType, ['html', 'eml'], true)) {
            $fileType = 'html';
        }
        $request->merge(['file_type' => $fileType]);

        $fileName = $request->query('mail_preview_file_name');
        $storagePath = (string) config('mail-preview.storage_path');

        $filePath = $fileName
            ? $storagePath.'/'.$fileName.'.'.$fileType
            : last(glob($storagePath.'/*.'.$fileType) ?: []);

        if (! $filePath || ! file_exists($filePath)) {
            return response()->view('mail-preview-empty', [], 200);
        }

        return app(ShowMailController::class)($request);
    })->name('mail.preview');
}
