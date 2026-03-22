<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showResetForm(Request $request, string $token)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('password_reset.api.reset_form'),
                'token' => $token,
                'email' => $request->query('email'),
            ]);
        }

        $theme = config('sysconfig.theme', 'admin_lte');
        $view = "templates.{$theme}.passwords.reset";

        if (!view()->exists($view)) {
            $view = 'auth.passwords.reset';
        }

        return view($view, [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __($status)], 200);
            }

            return redirect()->route('login')->with('status', __($status));
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __($status)], 422);
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
