<?php

namespace NowYouCan\NyceOAuth2\Client\Middleware;

use NowYouCan\NyceOAuth2\Client\Services\Contracts\AuthManagerContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Closure;

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
        $svc_names = $svc_names ?: [config('nyceoauth2client.default')];
        $svcs = app(AuthManagerContract::class);

        foreach ($svc_names as $svc_name) {

            if (!array_key_exists($svc_name, config('nyceoauth2client.connections'))) {
                return redirect()->route(config('nyceoauth2client.routes.oauth2fallback'))
                    ->with('error', 'Remote resource connection details have not been configured.');
            }

            $token = $svcs->getTokenObj ($svc_name);
            if ($token->isActive()) {
                continue;
            } elseif ($token->isNotSet() || ($token->hasExpired() && $token->refreshHasExpired())) {
                return $this->redirectWithIntended ($svc_name, $this->getOAuthMethodRoute($svc_name));
            } elseif ($token->refreshIsActive()) {
                return $this->redirectWithIntended ($svc_name, 'nyceoauth.auth-refresh');
            }
        }

        return $next($r);
    }
}