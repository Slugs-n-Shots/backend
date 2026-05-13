<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        $this->authenticate($request, $guards);
        $this->validateCustomClaims($request, $guards);

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    private function validateCustomClaims(Request $request, array $guards): void
    {
        foreach ($guards as $guard) {
            $auth = Auth::guard($guard);

            if (!$auth->check()) {
                continue;
            }

            $user = $auth->user();
            if (!method_exists($user, 'checkCustomClaims')) {
                continue;
            }

            if (!$request->bearerToken() || !method_exists($auth, 'payload')) {
                continue;
            }

            try {
                $payload = $auth->payload()->toArray();
            } catch (JWTException) {
                throw new AuthenticationException(__('Unauthenticated.'), $guards);
            }

            if (!$user->checkCustomClaims($payload)) {
                throw new AuthenticationException(__('Unauthenticated.'), $guards);
            }
        }
    }
}
