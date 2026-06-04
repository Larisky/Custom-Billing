<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateByUserId
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->header('X-User-Id');

        if ($userId && ! Auth::check()) {
            $user = User::find((int) $userId);
            if ($user) {
                Auth::setUser($user);
            }
        }

        return $next($request);
    }
}
