<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showLinkRequestForm(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('password_reset.api.request_form'),
            ]);
        }

        $theme = config('sysconfig.theme', 'admin_lte');
        $view = "templates.{$theme}.passwords.email";

        if (!view()->exists($view)) {
            $view = 'auth.passwords.email';
        }

        return view($view);
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            // Most common cause: SMTP not configured / connection rejected.
            $msg = __('password_reset.errors.send_failed');

            if ($request->expectsJson()) {
                return response()->json(['message' => $msg . ' ' . $e->getMessage()], 500);
            }

            return back()->withErrors(['email' => $msg]);
        }

        if ($status === Password::RESET_LINK_SENT) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __($status)], 200);
            }

            return back()->with('status', __($status));
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __($status)], 422);
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
