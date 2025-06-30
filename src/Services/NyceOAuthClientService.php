<?php

namespace NowYouCan\NyceOAuth2\Client\Services;

use GuzzleHttp\Client as HttpClient;
use NowYouCan\NyceOAuth2\Client\Provider\NyceGenericProvider;
use NowYouCan\NyceOAuth2\Client\Services\Contracts\AuthContract;
use NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken;
use Illuminate\Http\RedirectResponse;

class NyceOAuthClientService implements AuthContract
{

    protected string $service_name;
    protected $provider; // NowYouCan\NyceOAuth2\Client\Provider\NyceGenericProvider
    protected $token;    // NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken

    /**
     * The $config array format must be the same as is expected by PHPLeague's
     * Generic Provider class.
     *   @param string
     *   @param array
     *   @param array?
     */
    public function __construct (string $svc_name, array $config, array $http_options = [])
    {

        $this->service_name = $svc_name;

        $collaborators = [];
        if (!empty($http_options)) {
            $collaborators['httpClient'] = new HttpClient($http_options);
        }

        $this->provider = new NyceGenericProvider ($config, $collaborators);
        $this->token    = session()->get ("nyceoauth2client.{$svc_name}.token", new NyceAccessToken());

    }

    /**
     * A function to redirect our user to the resource-owner's website so that
     * they may log in with their credentials.  That site will send a response
     * and a "code" that we'll consume in getAccessTokenByAuthCode().
     * An alternate means of logging into a remote service is via the function
     * getAuthorizationUrl() below.  However, this requires us to ask the user
     * for their password at the resource owner, which requires their trust
     *   @param  array $options   OAuth2 opions list
     *   @return \Illuminate\Http\RedirectResponse
     */
    public function sendUserToResourceOwner (array $options = []): RedirectResponse {
        $cookie_state = 'nyceoauth2client.oauth2state';
        $request_url  = $this->provider->getAuthorizationUrl ($options);
        session()->put ($cookie_state, $this->provider->getState());
        session()->put ('url.intended', url()->current());
        return redirect($request_url);
    }

    /**
     * Fetch the access token by the "code" which has been returned via the 
     * given a the point of constructing the cl
     *   @param  string $code
     *   @return void
     */
    public function getAccessTokenByAuthCode (string $code): void {
        $this->token = $this->provider->getAccessToken ('authorization_code', ['code' => $code]);
        $this->saveTokenToSession();
    }

    /**
     * Fetch the access token by the clientId and clientSecret, which were all
     * given when constructing the class.  Because our token will be holding a
     * little more information than League's version supports, we also need to
     * extend League\OAuth2\Client\Token\AccessToken with our own Token class
     *   @return \NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken
     */
    public function getAccessTokenByClientCreds(): NyceAccessToken {
        $this->token = $this->provider->getAccessToken ('client_credentials');
        $this->saveTokenToSession();
        return $this->token;
    }

    /**
     * Fetch access token by username and password, which are the user's login
     * details held by the resource-owner
     *   @param string $userName
     *   @param string $password
     *   @return \NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken
     */
    public function getAccessTokenByPassword (string $username, string $password): NyceAccessToken {
        $this->token = $this->provider->getAccessToken ('password', ['username' => $username, 'password' => $password]);
        $this->saveTokenToSession();
        return $this->token;
    }

    /**
     * Use the token's refresh token to re renew the access token
     *   @return \NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken
     */
    public function getAccessTokenByRefresh(): NyceAccessToken {
        $this->token = $this->provider->getAccessToken ('refresh_token', [
            'refresh_token' => $this->token->getRefreshToken()
        ]);
        $this->saveTokenToSession();
        return $this->token;
    }

    /**
     * Save the token to the session
     *   @return void
     */
    public function saveTokenToSession(): void {
        $cookie_token_name = "nyceoauth2client.{$this->service_name}.token";
        session()->put ($cookie_token_name, $this->token);
    }

    /**
     * Get the token object (as opposed to the actual token string)
     */
    public function getTokenObj(): NyceAccessToken {
        return $this->token;
    }
    /**
     * Get the actual token in all its random character glory
     */
    public function getToken(): string {
        return $this->token->getToken();
    }
    /**
     * Get the token's expiration as a Unix stamp integer
     */
    public function getExpiration(): int {
        return $this->token->getExpires();
    }
    /**
     * Get boolean to denote whether the token has expired
     */
    public function hasExpired(): bool {
        return $this->token->hasExpired();
    }

    /**
     * Get the refresh token
     */
    public function getRefreshToken(): string {
        return $this->token->getRefreshToken();
    }
    /**
     * Get the refresh token expiry date
     */
    public function getRefreshExpiration(): int {
        return $this->token->getRefreshExpires();
    }
    /**
     * Get boolean to denote whether the refresh period has expired
     *   @return bool
     */
    public function refreshHasExpired(): bool {
        return $this->token->refreshHasExpired();
    }
    /**
     * Get boolean to denote whether the refresh can and should be used
     *   @return bool
     */
    public function tokenShouldRefresh(): bool {
        return $this->token->shouldRefresh();
    }

}
