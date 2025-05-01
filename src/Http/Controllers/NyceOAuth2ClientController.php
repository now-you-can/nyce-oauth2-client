<?php

namespace NowYouCan\NyceOAuth2\Client\Http\Controllers;

use NowYouCan\NyceOAuth2\Client\Services\Contracts\AuthContract;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Illuminate\Routing\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NyceOAuth2ClientController extends Controller {

    /**
     * Redirect the user to the resource owner's website for authentication
     */
    public function oauth2ResourceOwnerLogin (AuthContract $svc): RedirectResponse {
        return $svc->sendUserToResourceOwner();
    }
    /**
     * After being sent to the resource-owner's login page, that resource will
     * send us back a reply to this route
     */
    public function catchResourceOwnerReply (Request $r, AuthContract $svc): RedirectResponse {

        $cookie_state_name = config('nyceoauth2client.cookie_namespace') . 'oauth2state';

        if ($r->isNotFilled('code') || $r->isNotFilled('state')) {
            session()->forget($cookie_state_name);
            return redirect('home')->with ('error', 'Respoinse from the Resource Owner contained insufficient detail.');
        }

        $incoming_state = $r->query('state');
        $session_state  = session()->get($cookie_state_name);
        if ($incoming_state !== $session_state) {
            return redirect('home')->with ('error', 'Response from Resource Owner does not match our request, and so cannout be trusted.');
        }

        try {
            $svc->getAccessTokenByAuthCode ($r->query('code'));
        } catch (IdentityProviderException $e) {
            return redirect('home')->with ('error', $e->getMessage());
        }
        return redirect()->intended(route(config('nyceoauth2client.routes.oauth2fallback')));
    }

    /**
     * Establish IFS connection using  PHP League's OAuth2 Client.
     */
    public function setupNyceOAuth2 (AuthContract $svc): RedirectResponse {
        try {
            $svc->getAccessTokenByClientCreds();
        } catch (IdentityProviderException $e) {
            return redirect('home')->with ('error', $e->getMessage());
        }
        return redirect()->intended(route(config('nyceoauth2client.routes.oauth2fallback')));
    }

    /**
     * Refresh an expiring token whose refresh limit hasn't expired
     */
    public function refreshNyceOAuth2 (AuthContract $svc): RedirectResponse {
        try {
            $svc->getAccessTokenByRefresh();
        } catch (IdentityProviderException $e) {
            return redirect('home')->with ('error', $e->getMessage());
        }
        return redirect()->intended(route(config('nyceoauth2client.routes.oauth2fallback')));
    }

}
