<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmbryologist
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->withErrors([
                'auth_failed' => 'Please enter your laboratory credentials to access this workspace.',
            ]);
        }

        $user = Auth::user();

        if (!$user->isActive()) {
            $msg = $user->isSuspended()
                ? 'Your laboratory account has been suspended by the Administrator.'
                : 'Your laboratory account is currently inactive.';
            Auth::logout();
            return redirect()->route('login')->withErrors(['auth_failed' => $msg]);
        }

        return $next($request);
    }
}

