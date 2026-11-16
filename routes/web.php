<?php

declare(strict_types=1);

use App\Http\Controllers\MediaVisualController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\PasswordlessAuthController;
use App\Livewire\Auth\PasswordlessLogin;
use App\Livewire\NagoyaMovementDashboard;
use App\Models\Church;
use App\Models\GameSession;
use App\Models\Ward;
use App\Services\NagoyaMiniMapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\MailPreview\Http\Controllers\ShowMailController;
use Spatie\MailPreview\MailPreviewServiceProvider;

// Public Invitation to Reach Nagoya with Jesus Until No Place Left (Root View)
Route::get('/', function (NagoyaMiniMapService $miniMap) {
    $activeSession = GameSession::where('is_active', true)->first();

    $wards = Ward::orderBy('name')
        ->withCount([
            'churches' => fn ($q) => $q->when($activeSession, fn ($sq) => $sq->where('game_session_id', $activeSession->id))
                ->where('is_dissolved', false),
            'npcs' => fn ($q) => $q->when($activeSession, fn ($sq) => $sq->where('game_session_id', $activeSession->id)),
        ])
        ->get();

    $churches = $activeSession
        ? Church::where('game_session_id', $activeSession->id)
            ->where('is_dissolved', false)
            ->get(['id', 'ward_id', 'name', 'generation', 'is_healthy_church', 'lat', 'lng', 'attenders_count'])
        : collect();

    $wardMapData = $miniMap->getInteractiveWardMapData($wards, 420, 300);

    $wardStatusById = $wards->mapWithKeys(fn (Ward $w): array => [
        $w->id => [
            'churches_count' => (int) ($w->churches_count ?? 0),
            'npcs_count' => (int) ($w->npcs_count ?? 0),
            'openness' => (int) $w->spiritual_openness,
            'poverty' => (int) $w->poverty_need_index,
        ],
    ])->all();

    $churchPins = $miniMap->projectMarkers(
        $churches->filter(fn (Church $c): bool => (bool) ($c->lat && $c->lng))
            ->map(fn (Church $c): array => [
                'lat' => (float) $c->lat,
                'lng' => (float) $c->lng,
                'label' => $c->name,
                'generation' => (int) $c->generation,
                'healthy' => (bool) $c->is_healthy_church,
            ])->values()->all(),
        420,
        300
    );

    return view('invitation', [
        'wardMapData' => $wardMapData,
        'wardStatusById' => $wardStatusById,
        'churchPins' => $churchPins,
        'totalChurches' => $churches->count(),
        'reachedWardsCount' => $wards->filter(fn (Ward $w): bool => (int) ($w->churches_count ?? 0) > 0)->count(),
        'totalWardsCount' => $wards->count(),
        'currentMonth' => (int) ($activeSession->current_game_month ?? 1),
        'currentWeek' => (int) ($activeSession->current_game_week ?? 1),
    ]);
})->name('invitation');

// Public Lore, Story & Comprehensive Game Rules Overview
Route::view('/welcome', 'welcome')->name('welcome');

// Public Email-Safe PNG Visuals (Logo, Avatars & Nagoya Ward Maps)
Route::get('/media/logo.png', [MediaVisualController::class, 'logo'])->name('media.logo');
Route::get('/media/avatar.png', [MediaVisualController::class, 'avatar'])->name('media.avatar');
Route::get('/media/ward-map.png', [MediaVisualController::class, 'wardMap'])->name('media.ward-map');

// Public Longitudinal Movement Simulation (Shared Daily SQLite DB for Non-Registered Players)
Route::get('/simulate', NagoyaMovementDashboard::class)->name('simulate');

// Guest Passwordless Auth
Route::middleware('guest')->group(function (): void {
    Route::get('/login', PasswordlessLogin::class)->name('login');
    Route::get('/register', PasswordlessLogin::class)->name('register');
});

// Signed Magic Link Verification
Route::get('/auth/magic-link', [PasswordlessAuthController::class, 'verify'])->name('auth.magic-link');

// Notification Opt-Out & Opt-In (Cryptographically Signed Links)
Route::match(['get', 'post'], '/notifications/opt-out/{user}', [NotificationPreferenceController::class, 'optOut'])
    ->name('notifications.opt-out')
    ->middleware('signed');

Route::post('/notifications/opt-in/{user}', [NotificationPreferenceController::class, 'optIn'])
    ->name('notifications.opt-in')
    ->middleware('signed');

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
