<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
   public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
        $unlocked = app(\App\Services\AchievementService::class)->evaluate($request->user());
            if (!empty($unlocked)) {
                session()->flash('unlocked', $unlocked);
            }

            $fallback = $request->user()->isAdmin()
                ? route('admin.dashboard')
                : route('home');

            return redirect()->intended($fallback);
        }

        return back()->withErrors([
            'email' => 'The provided credentials are incorrect.',
        ])->onlyInput('email');

    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect(route('login'));
    }
}
