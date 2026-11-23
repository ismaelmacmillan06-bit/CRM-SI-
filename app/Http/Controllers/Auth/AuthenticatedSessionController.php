<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $userId = Auth::id();
        $prevSeenId = (int) Cache::get("sic_last_seen_notif_id_{$userId}", 0);
        $request->session()->flash('show_login_notifications_summary', true);
        $request->session()->flash('login_prev_seen_notif_id', $prevSeenId);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($userId = Auth::id()) {
            try {
                $maxId = (int) (UserNotification::forUser($userId)->max('id') ?? 0);
                Cache::forever("sic_last_seen_notif_id_{$userId}", $maxId);
            } catch (\Throwable) {
            }
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
