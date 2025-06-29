<?php

namespace NowYouCan\NyceOAuth2\Client\Http\Controllers;

use NowYouCan\NyceOAuth2\Client\Services\Contracts\AuthManagerContract;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Illuminate\Routing\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use InvalidArgumentException;

class NyceOAuth2ClientController extends Controller {

    /**
     * The method of establishing an OAuth2 token depends on the configuration
     * settings.  Given the service name, we can access that config
     *   @param $svc_name
     *   @return \Illuminate\Http\RedirectResponse
     *   @throws \InvalidArgumentException;
     */
    public function establishOAuthMethod (?string $svc_name = null): RedirectResponse {

        session()->reflash(); // in particular, we need to keep url.intended
        $svc_name = $svc_name ?? config ('nyceoauth2client.default');
        $method = config ("nyceoauth2client.connections.{$svc_name}.auth_type");
        $desired_redirect = 'nyceoauth.' . match ($method) {
            'local-auth'  => 'resource-owner-pass',
            'remote-auth' => 'resource-owner-user-login',
            'client-id'   => 'resource-owner-client-creds',
            default       => '**error**',
        };

        if ($desired_redirect === '**error**') {
            throw new InvalidArgumentException("OAuth method [{$method}] is not defined in service [{$svc_name}].");
        }
        return redirect()->route($desired_redirect, ['service_name' => $svc_name]);

    }

    /**
     * Redirect the user to the resource owner's website for authentication
     */
    public function oauth2ResourceOwnerUserLogin (AuthManagerContract $svcs, ?string $svc_name = null): RedirectResponse {
        return $svcs->sendUserToResourceOwner($svc_name);
    }

    /**
     * After being sent to the resource-owner's login page, that resource will
     * authenticate the user, and then reply to us on this route.  Indeed this
     * is the "redirect route" that we told the resource-owner we're expecting 
     * to receive a response upon, once it has finished its validation process
     * with its user.
     * This route expects to receive a `code` from the resource-owner, that we
     * shall use to exchange for the actual auth-token.
     */
    public function catchResourceOwnerReply (Request $r, AuthManagerContract $svcs, ?string $svc_name = null): RedirectResponse {

        $cookie_state_name = "nyceoauth2client.{$svc_name}.oauth2state";

        if ($r->isNotFilled('code') || $r->isNotFilled('state')) {
            session()->forget($cookie_state_name);
            return redirect(route(config('nyceoauth2client.routes.oauth2fallback')))
                ->with ('error', 'Response from the Resource Owner contained insufficient detail.');
        }

        $incoming_state = $r->query('state');
        $session_state  = session()->get($cookie_state_name);
        if ($incoming_state !== $session_state) {
            return redirect(route(config('nyceoauth2client.routes.oauth2fallback')))
                ->with ('error', 'Response from Resource Owner does not match our request, and so cannout be trusted.');
        }

        try {
            $svcs->getAccessTokenByAuthCode ($svc_name, $r->query('code'));
        } catch (IdentityProviderException $e) {
            return redirect(route(config('nyceoauth2client.routes.oauth2fallback')))
                ->with ('error', $e->getMessage());
        }
        return redirect()->intended(route(config('nyceoauth2client.routes.oauth2fallback')))
            ->with ('success', 'Successfully logged in');
    }

    /**
     * Sometimes an OAuth2 client is configured (on the resource-owner's side)
     * to work with a "Service Account", allowing our app to interact with the
     * resource-owner without our end-user having to log in to *their* account
     * at the resource-owner's side.  All we need to know is the client Id and 
     * secret held on the resource-owner's side.
     * Note that you don't really want to be doing this kind of authentication
     * across the open internet, but when you're building in-house apps it can
     * be a seamless way to interact with external resources.
     */
    public function oauth2ByClientCreds (AuthManagerContract $svcs, ?string $svc_name = null): RedirectResponse {
        try {
            $svcs->getAccessTokenByClientCreds($svc_name);
        } catch (IdentityProviderException $e) {
            return redirect(route(config('nyceoauth2client.routes.oauth2fallback')))
                ->with ('error', $e->getMessage());
        }
        return redirect()->intended(route(config('nyceoauth2client.routes.oauth2fallback')));
    }

    /**
     * Another way of obtaining an OAuth2 Token is to log in on our end-user's
     * behalf.  If they are kind enough to offer us their login credentials at
     * the resource-owner's site, then wen can obtain a token for them.
     * 
     * Note: The injected AuthManagerContract{} service has already loaded our
     * token from the session.  getAccessTokenByPassword() is clever enough to
     * check its validity before going to fetch a new one
     */
    public function oauth2ByPassword (Request $r, AuthManagerContract $svcs, ?string $svc_name = null): JsonResponse {
        try {
            $token = $svcs->getAccessTokenByPassword ($svc_name, $r->input('remoteuser'), $r->input('remotepw'));
        } catch (IdentityProviderException $e) {
            return response()->json ([
                'success'    => false,
                'error_type' => 'Identity Provider',
                'error_msg'  => $e->getMessage(),
            ], Response::HTTP_UNAUTHORIZED);
        } catch (\Exception $e) {
            return response()->json ([
                'success'    => false,
                'error_type' => 'Internal problem',
                'error_msg'  => 'Unexpected error: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return response()->json([
            'success'         => true,
            'access_token'    => $token->getToken(),
            'creaated_at'     => $token->getGenerated(),
            'expires'         => $token->getExpires(),
            'refresh_token'   => $token->getRefreshToken(),
            'refresh_expires' => $token->getRefreshExpires(),
        ]);
    }

    /**
     * Tokens handed out by resource-owners have a limited life-span.  When we
     * receive a token we are also told what it's expiry date is, meaning that
     * we can request a refresh token when that expiry time is close at hand.
     * are doing in this function
     */
    public function refreshOAuth2 (AuthManagerContract $svcs, ?string $svc_name = null): RedirectResponse {
        try {
            $svcs->getAccessTokenByRefresh($svc_name);
        } catch (IdentityProviderException $e) {
            return redirect(route(config('nyceoauth2client.routes.oauth2fallback')))
                ->with ('error', $e->getMessage());
        }
        return redirect()->intended(route(config('nyceoauth2client.routes.oauth2fallback')))
            ->with ('success', 'Token refreshed');
    }

}