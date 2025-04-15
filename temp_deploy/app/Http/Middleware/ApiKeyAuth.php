<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if API key is provided in the header
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey) {
            return response()->json([
                'message' => 'API key is missing'
            ], 401);
        }

        // Find user with the given API key
        $user = User::where('api_key', $apiKey)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid API key'
            ], 401);
        }

        // Set the authenticated user
        auth()->login($user);

        return $next($request);
    }
}
