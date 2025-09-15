<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MockAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Create a mock user for demonstration
        if (!Auth::check()) {
            $mockUser = new User([
                'id' => 1,
                'name' => 'Admin Demo',
                'email' => 'admin@mozoqr.com',
                'is_system_super_admin' => true,
            ]);

            // Set additional attributes that might be needed
            $mockUser->exists = true;
            $mockUser->setAttribute('id', 1);

            // Manually login the mock user
            Auth::login($mockUser);
        }

        return $next($request);
    }
}