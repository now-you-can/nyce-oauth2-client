<?php

namespace NowYouCan\NyceOAuth2\Client\Http\Controllers;

use NowYouCan\NyceOAuth2\Client\Services\Contracts\AuthContract;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Illuminate\Routing\Controller;

class NyceOAuth2ClientController extends Controller {

    /**
     * Establish IFS connection using  PHP League's OAuth2 Client.
     */
    public function setupNyceOAuth2 (AuthContract $svc) {
        try {
            $http_options = config('nyceoauth2client.default_http_options');
            $svc->getAccessTokenByClientCreds ($http_options);
        } catch (IdentityProviderException $e) {
            exit ($e->getMessage());
        }
        return redirect()->intended(route(config('nyceoauth2client.routes.oauth2fallback')));
    }

    /**
     * Refresh an expiring token whose refresh limit hasn't expired
     */
    public function refreshNyceOAuth2 (AuthContract $svc) {
        try {
            $http_options = config('nyceoauth2client.default_http_options');
            $svc->getAccessTokenByRefresh ($http_options);
        } catch (IdentityProviderException $e) {
            exit ($e->getMessage());
        }
        return redirect()->intended(route(config('nyceoauth2client.routes.oauth2fallback')));
    }
}
