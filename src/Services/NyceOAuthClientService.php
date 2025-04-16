<?php

namespace NowYouCan\NyceOAuth2\Client\Services;

use NowYouCan\NyceOAuth2\Client\Provider\NyceGenericProvider;
use NowYouCan\NyceOAuth2\Client\Services\Contracts\AuthContract;

class NyceOAuthClientService implements AuthContract
{

    protected $provider; // NowYouCan\NyceOAuth2\Client\Provider\NyceGenericProvider
    protected $token;    // NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken

    public function __construct(array $config)
    {
        $this->provider = new NyceGenericProvider ($config);
        $cookie_name   = config('nyceoauth2client.cookie_name');
        if (session()->has($cookie_name)) {
            $this->token = session()->get($cookie_name);
        }
    }

    /**
     * Fetch the access token by the clientId and clientSecret, which were all
     * given a the point of constructing the class.  Since we are to hold some
     * more information in our token than what is supported by League, we have
     * our own Token class, extending League\OAuth2\Client\Token\AccessToken.
     *   @param  array $http_options
     *   @param  bool $save_to_session
     *   @return void
     */
    public function getAccessTokenByClientCreds (array $http_options = [], bool $save_to_session = true) {
        $this->token = $this->provider->getAccessToken ('client_credentials', http_options: $http_options);
        $this->saveTokenToSession ($save_to_session);
    }

    /**
     * Fetch the access token by the "code" which has been returned via the 
     * given a the point of constructing the cl
     *   @param  string $code
     *   @param  array  $http_options
     *   @param  bool   $save_to_session
     *   @return void
     */
    public function getAccessTokenByAuthCode (string $code, array $http_options = [], bool $save_to_session = true) {
        $this->token = $this->provider->getAccessToken ('authorization_code', ['code' => $code], $http_options);
        $this->saveTokenToSession ($save_to_session);
    }

    /**
     * Use the token's refresh token to re renew the access token
     */
    public function getAccessTokenByRefresh (array $http_options = [], bool $save_to_session = true) {
        $this->token = $this->provider->getAccessToken ('refresh_token', [
            'refresh_token' => $this->token->getRefreshToken()
        ], $http_options);
        $this->saveTokenToSession ($save_to_session);
    }

    /**
     * Save the token to the session
     *   @param bool $save
     */
    public function saveTokenToSession (bool $save = true): void {
        if ($save) {
            $save_as     = config('nyceoauth2client.session_data');
            $cookie_name = config('nyceoauth2client.cookie_name');
            if ($save_as == 'object') {
                session()->put ($cookie_name, $this->token);
            } elseif ($save_as == 'values') {
                session()->put ($cookie_name, [
                    'generated'       => $this->token->getGenerated(),
                    'access_token'    => $this->token->getToken(),
                    'expires'         => $this->token->getExpires(),
                    'refresh_token'   => $this->token->getRefreshToken(),
                    'refresh_expires' => $this->token->getRefreshExpires(),
                ]);
            }
        }
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

}
