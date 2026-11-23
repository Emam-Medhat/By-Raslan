<?php

namespace App\Modules\Identity\Http\Controllers\Auth;

use App\Modules\Audit\Services\AuditService;
use App\Modules\Identity\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController
{
    public function __construct(private readonly AuditService $audit)
    {
    }
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $throttleKey = 'login:'.Str::lower($request->loginKey()).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors(['login' => "تجارب دخول كثيرة. حاول بعد {$seconds} ثانية"]);
        }

        if (! Auth::attempt($request->credentials(), $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 300);

            return back()->withErrors(['login' => 'الموبايل/الإيميل أو الرقم السري غلط']);
        }

        if (! $request->user()->isActive()) {
            Auth::logout();

            return back()->withErrors(['login' => 'الحساب معطّل']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $this->audit->log('login', 'users', $request->user()->id);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}