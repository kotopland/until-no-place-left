<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * Opt out of all email notifications via signed link.
     */
    public function optOut(Request $request, User $user): View
    {
        $user->update([
            'notify_email' => false,
        ]);

        return view('notifications.opt-out-confirmed', [
            'user' => $user,
        ]);
    }

    /**
     * Re-enable email notifications via signed action.
     */
    public function optIn(Request $request, User $user): RedirectResponse
    {
        $user->update([
            'notify_email' => true,
        ]);

        return back()->with('success', 'Email notifications have been successfully re-enabled!');
    }
}
