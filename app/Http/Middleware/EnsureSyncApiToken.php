<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSyncApiToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = config('sync.api_token');
        $providedToken = $request->bearerToken() ?: $request->header('X-Sync-Token');

        if (! $expectedToken || ! $providedToken || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid or missing sync API token.',
            ], 401);
        }

        return $next($request);
    }
}
