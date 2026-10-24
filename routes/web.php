<?php

declare(strict_types=1);

use App\Http\Controllers\PasswordlessAuthController;
use App\Livewire\Auth\PasswordlessLogin;
use App\Livewire\NagoyaMovementDashboard;
use Illuminate\Support\Facades\Route;

// Public Lore, Story & Rules
Route::view('/welcome', 'welcome')->name('welcome');

// Guest Passwordless Auth
Route::middleware('guest')->group(function (): void {
    Route::get('/login', PasswordlessLogin::class)->name('login');
    Route::get('/register', PasswordlessLogin::class)->name('register');
});

// Signed Magic Link Verification
Route::get('/auth/magic-link', [PasswordlessAuthController::class, 'verify'])->name('auth.magic-link');

// Authenticated Movement Dashboard & Logout
Route::middleware('auth')->group(function (): void {
    Route::get('/', NagoyaMovementDashboard::class)->name('home');
    Route::post('/logout', [PasswordlessAuthController::class, 'logout'])->name('logout');
});

// Mail Preview for Local Development
if (class_exists(\Spatie\MailPreview\MailPreviewServiceProvider::class)) {
    Route::mailPreview();
}
