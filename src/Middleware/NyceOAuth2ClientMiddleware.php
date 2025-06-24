<?php

namespace NowYouCan\NyceOAuth2\Client\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

class NyceOAuth2ClientMiddleware
{

    /**
     * When the OAuth token needs to be generated or refreshed, we shall catch
     * the current route, before redirecting the user to the appropriate token
     * destination.
     */
    private function redirectWithIntended (string $svc_name, string $route_name): RedirectResponse {
        return redirect()
            ->route($route_name, ['service_name' => $svc_name])
            ->with('url.intended', url()->current());
    }

    /**
     * This function 
     * settings.  Given the service name, we can access that config
     *   @param $svc_name
     *   @return \Illuminate\Http\RedirectResponse
     *   @throws \InvalidArgumentException;
     */
    public function establishOAuthMethod (?string $svc_name): RedirectResponse {
        $desired_redirect = 'nyceoauth.' . match (config("nyceoauth2client.connections.{$svc_name}.auth_type")) {
            'local-auth'  => 'resource-owner-pass',
            'remote-auth' => 'resource-owner-user-login',
            'client-id'   => 'resource-owner-client-creds',
            default       => '**error**',
        };
        if ($desired_redirect === '**error**') {
            throw new InvalidArgumentException("OAuth method [{$method}] is not defined in service [{$svc_name}].");
        }
        return $this->redirectWithIntended ($svc_name, $desired_redirect);
    }

    /**
     * Handle an incoming request.
     */
    public function handle (Request $r, Closure $next, ...$svc_names)
    {
        $svc_names   = $svc_names ?: [config('nyceoauth2client.default')];
        $svc_configs = config('nyceoauth2client.connections');

        foreach ($svc_names as $svc_name) {

            if (!array_key_exists($svc_name, $svc_configs)) {
                abort (403, 'Authorised connection to remote resource not established.');
            }

            $cookie_token_name = "nyceoauth2client.{$svc_name}.token";
            if (session()->has($cookie_token_name)) {
                // A valid token was found, so now we have to work out whether
                // it has expired, and if so, how to refresh it
                $token = session()->get($cookie_token_name);
                if ($token->hasExpired() && $token->refreshHasExpired()) {
                    return $this->redirectWithIntended ($svc_name, 'nyceoauth.establish-remote-auth');
                } elseif ($token->hasExpired() && !$token->refreshHasExpired()) {
                    return $this->redirectWithIntended ($svc_name, 'nyceoauth.auth-refresh');
                }
            } else {
                // establish-remote-auth route checks the configuration of the
                // $svc_name and again redirects to the correct authentication
                // method accordingly.
                return $this->redirectWithIntended ($svc_name, 'nyceoauth.establish-remote-auth');
            }

            return redirect()->route(config('nyceoauth2client.routes.oauth2fallback'))->with('error', 'Unable to establish remote authentication.');

        }

        return $next($r);
    }
}