<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordlessAuthController extends Controller
{
    /**
     * Verify the signed magic login link and log the user in or create their account.
     */
    public function verify(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()
                ->route('login')
                ->with('error', 'This sign-in link is invalid or has expired. Please enter your email to request a new magic link.');
        }

        $email = strtolower(trim((string) $request->query('email', '')));
        $rawName = trim((string) $request->query('name', ''));
        $rawNationality = trim((string) $request->query('nationality', ''));
        $rawAge = $request->query('age');

        if ($email === '') {
            return redirect()
                ->route('login')
                ->with('error', 'Invalid sign-in request. Email address is missing.');
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            // Existing user login: update name/nationality/age if explicitly specified
            $updates = [];
            if ($rawName !== '' && $rawName !== $user->name) {
                $updates['name'] = $rawName;
            }
            if ($rawNationality !== '' && (! $user->nationality || $user->nationality !== $rawNationality)) {
                $updates['nationality'] = $rawNationality;
            }
            if ($rawAge !== null && is_numeric($rawAge) && (int) $rawAge > 18) {
                $updates['age'] = (int) $rawAge;
            }
            if ($updates !== []) {
                $user->update($updates);
            }

            Auth::login($user, remember: true);
            $request->session()->regenerate();
            $user->touchActive();

            return redirect()
                ->intended(route('home'))
                ->with('success', "Welcome back, {$user->name}! Your mission awaits.");
        }

        // New user creation: establish missionary profile and game session
        $session = GameSession::where('is_active', true)->first();
        $finalName = $rawName !== ''
            ? $rawName
            : (Str::headline(Str::before($email, '@')) ?: 'Missionary Disciple');

        $newUser = User::create([
            'name' => $finalName,
            'nationality' => $rawNationality !== '' ? $rawNationality : 'Japanese',
            'age' => ($rawAge !== null && is_numeric($rawAge) && (int) $rawAge > 18) ? (int) $rawAge : 30,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(32)),
            'current_game_session_id' => $session?->id,
            'current_game_month' => 1,
            'current_game_week' => 1,
            'weeks_processed_this_month' => 0,
            'weeks_played_today' => 0,
            'action_points' => 20,
            'max_action_points' => 26,
            'weekly_action_points' => 20,
            'daily_action_points' => 20,
            'total_generational_score' => 0,
            'coop_synergy_score' => 0,
            'days_played_today' => 0,
            'last_played_real_date' => now()->toDateString(),
            'last_active_at' => now(),
            'next_weekly_tick_at' => now()->addMinutes(15),
            'next_daily_tick_at' => now()->addMinutes(15),
        ]);

        Auth::login($newUser, remember: true);
        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with('success', "Welcome to Nagoya, {$newUser->name}! Your missionary disciple profile has been created.");
    }

    /**
     * Log the authenticated user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('welcome')
            ->with('info', 'You have been safely signed out. Return anytime to continue the harvest.');
    }
}
