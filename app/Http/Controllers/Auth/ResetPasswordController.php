<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function show(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'token' => ['required', 'uuid'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $reset = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (
            ! $reset ||
            ! Hash::check($validated['token'], $reset->token) ||
            now()->subMinutes(5)->greaterThan($reset->created_at)
        ) {
            throw ValidationException::withMessages([
                'email' => 'This password reset link is invalid or expired.',
            ]);
        }

        $user = User::query()
            ->where('email', $validated['email'])
            ->firstOrFail();

        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => null,
        ])->save();

        DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->delete();

        return redirect()
            ->route('login')
            ->with('status', 'Your password has been reset. You can sign in now.');
    }
}
