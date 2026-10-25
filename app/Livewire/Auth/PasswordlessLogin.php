<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Models\User;
use App\Notifications\MagicLoginLinkNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Mission Access • Nagoya Oikos: The Four Fields')]
class PasswordlessLogin extends Component
{
    public string $email = '';

    public string $name = '';

    public bool $sent = false;

    public ?string $sentEmail = null;

    public ?string $localMagicLink = null;

    public bool $isExistingUser = false;

    /**
     * Mount the component with query params if available.
     */
    public function mount(): void
    {
        if (request()->has('email')) {
            $this->email = (string) request()->query('email');
            $this->checkExistingUser();
        }
    }

    /**
     * Reactively check if the entered email belongs to an existing missionary.
     */
    public function updatedEmail(): void
    {
        $this->checkExistingUser();
    }

    /**
     * Check database for an existing user.
     */
    protected function checkExistingUser(): void
    {
        $cleanEmail = strtolower(trim($this->email));

        if (filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $cleanEmail)->first();
            if ($user) {
                $this->isExistingUser = true;
                if ($this->name === '') {
                    $this->name = $user->name;
                }

                return;
            }
        }

        $this->isExistingUser = false;
    }

    /**
     * Send the signed passwordless magic link for login or user creation.
     */
    public function sendMagicLink(): void
    {
        $this->email = strtolower(trim($this->email));
        $this->name = trim($this->name);

        $existingUser = User::where('email', $this->email)->first();
        $this->isExistingUser = $existingUser !== null;

        if ($existingUser) {
            $this->validate([
                'email' => ['required', 'email:rfc'],
                'name' => ['nullable', 'string', 'max:50'],
            ]);

            $resolvedName = $this->name !== '' ? $this->name : $existingUser->name;
        } else {
            $this->validate([
                'email' => ['required', 'email:rfc'],
                'name' => ['required', 'string', 'min:2', 'max:50'],
            ], [
                'name.required' => 'Please create a player / missionary name to create your account.',
                'name.min' => 'Player name must be at least 2 characters long.',
            ]);

            $resolvedName = $this->name;
        }

        // Generate temporary cryptographic signed URL
        $magicUrl = URL::temporarySignedRoute(
            'auth.magic-link',
            now()->addMinutes(30),
            [
                'email' => $this->email,
                'name' => $resolvedName,
            ]
        );

        // Send email notification
        Notification::route('mail', $this->email)->notify(
            new MagicLoginLinkNotification(
                url: $magicUrl,
                name: $resolvedName,
                isNewUser: ! $existingUser,
            )
        );

        // Store local magic link for instant developer/testing verification without opening mail logs
        if (app()->environment('local', 'testing')) {
            $this->localMagicLink = $magicUrl;
        }

        $this->sent = true;
        $this->sentEmail = $this->email;
    }

    /**
     * Reset the form to send another link.
     */
    public function resetForm(): void
    {
        $this->sent = false;
        $this->localMagicLink = null;
    }

    public function render(): View
    {
        return view('livewire.auth.passwordless-login');
    }
}
