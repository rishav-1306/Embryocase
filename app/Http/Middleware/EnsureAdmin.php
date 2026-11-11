<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('admin.login')->withErrors([
                'email' => 'Administrator authentication required.',
            ]);
        }

        $user = Auth::user();

        if (!$user->isActive()) {
            Auth::logout();
            return redirect()->route('admin.login')->withErrors([
                'email' => 'This administrator account is not active.',
            ]);
        }

        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized. Access restricted to System Administrators.');
        }

        return $next($request);
    }
}
