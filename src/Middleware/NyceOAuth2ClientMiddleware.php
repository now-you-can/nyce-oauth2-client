<?php

namespace NowYouCan\NyceOAuth2\Client\Middleware;

use Closure;
use Illuminate\Http\Request;
use NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken;

class NyceOAuth2ClientMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle (Request $r, Closure $next)
    {
        $cookie_name   = config('nyceoauth2client.cookie_name');
        $token_request = 'nyceoauth.' . config('nyceoauth2client.routes.oauth2request');
        $token_refresh = 'nyceoauth.' . config('nyceoauth2client.routes.oauth2refresh');

        if (session()->has($cookie_name)) {
            $token = config('nyceoauth2client.session_data') == 'object'
                ? session()->get($cookie_name)
                : new NyceAccessToken (session()->get($cookie_name));
            if ($token->hasExpired() && $token->refreshHasExpired()) {
                return redirect()->route($token_request)->with('url.intended', url()->current());
            } elseif ($token->hasExpired() && !$token->refreshHasExpired()) {
                return redirect()->route($token_refresh)->with('url.intended', url()->current());
            }
        } else {
            return redirect()->route($token_request)->with('url.intended', url()->current());
        }

        return $next($r);
    }
}