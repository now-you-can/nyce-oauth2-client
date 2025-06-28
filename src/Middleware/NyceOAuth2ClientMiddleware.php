<?php

namespace NowYouCan\NyceOAuth2\Client\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

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
     * An approximate copy of function establishOAuthMethod() in the extension
     * Controller.  This function saves us a redirect.
     *   @param $svc_name
     *   @return string|\Illuminate\Http\RedirectResponse
     */
    public function getOAuthMethodRoute (string $svc_name): string|RedirectResponse {
        $desired_redirect = 'nyceoauth.' . match (config("nyceoauth2client.connections.{$svc_name}.auth_type")) {
            'local-auth'  => 'resource-owner-pass',
            'remote-auth' => 'resource-owner-user-login',
            'client-id'   => 'resource-owner-client-creds',
            default       => '**error**',
        };
        if ($desired_redirect === '**error**') {
            return redirect()->route(config('nyceoauth2client.routes.oauth2fallback'))
                ->with('error', 'Error trying to establish remote resource details.');
        }
        return $desired_redirect;
    }

    /**
     * Handle an incoming request.
     */
    public function handle (Request $r, Closure $next, ...$svc_names)
    {
        $oauth_attempt_required = false;
        $svc_names = $svc_names ?: [config('nyceoauth2client.default')];

        foreach ($svc_names as $svc_name) {

            if (!array_key_exists($svc_name, config('nyceoauth2client.connections'))) {
                return redirect()->route(config('nyceoauth2client.routes.oauth2fallback'))
                    ->with('error', 'Remote resource connection details have not been configured.');
            }

            $cookie_token_name = "nyceoauth2client.{$svc_name}.token";
            if (session()->has($cookie_token_name)) {
                $token = session()->get($cookie_token_name);
                if ($token->isActive()) {
                    continue;
                } elseif ($token->refreshIsActive()) {
                    return $this->redirectWithIntended ($svc_name, 'nyceoauth.auth-refresh');
                } elseif ($token->refreshHasExpired()) {
                    $oauth_attempt_required = true;
                }
            } else {
                $oauth_attempt_required = true;
            }

            if ($oauth_attempt_required) {
                return $this->redirectWithIntended ($svc_name, $this->getOAuthMethodRoute($svc_name));
            }
        }

        return $next($r);
    }
}