<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class RefreshJWTToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $payload = Auth::payload();
        // $refresh_ttl =  $payload['ttl'] + Config::get('jwt.refresh_ttl') * 60;
        // if ($refresh_ttl < time())
        // {

        //     // new TokenExpiredException("Token expired", 401);
        // }
        // $ret = Auth::setTTL(7200)->check();

        // $token = JWTAuth::getToken();
        // // $request->headers->set('Authorization', "Bearer {$token}");
        // if (!$token) {
        //     return response()->json(['error' => 'Token not provided'], 401);
        // }
        return $next($request);
    }
}
