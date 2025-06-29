<?php

namespace NowYouCan\NyceOAuth2\Client\Token;

use League\OAuth2\Client\Token\AccessToken;
use League\Oauth2\Client\Token\AccessTokenInterface;
use League\Oauth2\Client\Token\ResourceOwnerAccessTokenInterface;
use League\Oauth2\Client\Token\SettableRefreshTokenInterface;


class NyceAccessToken extends AccessToken
      implements AccessTokenInterface, ResourceOwnerAccessTokenInterface, SettableRefreshTokenInterface {


    /**
     * @var int
     * Token was generated at this time.  Can be useful, especially since some
     * options like "expires_in" need to base themselves on something!
     */
    protected $generated;

    /**
     * @var int
     * Unix timestamp (seconds since 1970)
     */
    protected $refresh_expires;

    /**
     * Constructs an access token.  This is a copy/paste form the parent class
     * AccessToken::__construct().  However, parent::_construct() doesn't take
     * a refresh expiration into account, and we'd prefer not to undo what the
     * parent does to achieve our goals; easier to manage it first hand.
     * 
     * @param array $options An array of options returned by the service provider
     *     in the access token request. The `access_token` option is required.
     * @throws InvalidArgumentException if `access_token` is not provided in `$options`.
     */
    public function __construct(array $options = []) {

        $this->generated   = empty($options['generated']) ? $this->getTimeNow() : $options['generated'];
        $this->accessToken = empty($options['access_token']) ? '**unset**' : $options['access_token'];

        if (!empty($options['resource_owner_id'])) {
            $this->resourceOwnerId = $options['resource_owner_id'];
        }
        if (!empty($options['refresh_token'])) {
            $this->refreshToken = $options['refresh_token'];
        }

        // We need to know when the token expires. Preference for 'expires_in'
        // over 'expires', since it is defined in RFC6749 Section 5.1
        if (isset($options['expires_in'])) {
            if (!is_numeric($options['expires_in'])) {
                throw new \InvalidArgumentException('expires_in value must be an integer');
            }
            $this->expires = $options['expires_in'] != 0 ? $this->generated + $options['expires_in'] : 0;
        } elseif (!empty($options['expires'])) {
            $expires = (int) $options['expires'];
            if (!$this->isExpirationTimestamp($expires)) {
                $expires += $this->generated;
            }
            $this->expires = $expires;
        }

        if (isset($options['refresh_expires_in'])) {
            if (!is_numeric($options['refresh_expires_in'])) {
                throw new \InvalidArgumentException('refresh_expires_in value must be an integer');
            }
            $this->refresh_expires = $options['refresh_expires_in'] != 0 ? $this->generated + $options['refresh_expires_in'] : 0;
        } elseif (!empty($options['refresh_expires'])) {
            $expires = (int) $options['refresh_expires'];
            if (!$this->isExpirationTimestamp($expires)) {
                $expires += $this->generated;
            }
            $this->refresh_expires = $expires;
        }

        // Capture other values that may exist in the OAuth2 server's response
        // but which are not part of the standard specifications. Vendors will
        // sometimes pass additional user data this way.
        $this->values = array_diff_key($options, array_flip([
            'access_token', 'resource_owner_id', 'refresh_token', 'expires_in', 'expires', 'refresh_expires_in', 'refresh_expires'
        ]));
    }

    /**
     * Checker function to see if a token value has been assigned or not
     */
    public function isNotSet(): bool {
        return $this->accessToken === '**unset**';
    }

    /**
     * Get the token generated time
     */
    public function getGenerated() {
        return $this->generated;
    }

    /**
     * Standard getter for our refresh expiration time
     */
    public function getRefreshExpires() {
        return $this->refresh_expires;
    }

    /**
     * Override of the base function to avoid exception problems
     */
    public function hasExpired() {
        return empty($this->expires) ? true : $this->expires < $this->getTimeNow();
    }

    /**
     * Quick access to check that the token has not yet expired
     */
    public function isActive(): bool {
        if (empty($this->getExpires())) {
            return false;
        }
        return !$this->hasExpired();
    }

    /**
     * Quick access to see if the refresh has expired
     */
    public function refreshHasExpired(): bool {
        $refresh_expires = $this->getRefreshExpires();
        if (empty($refresh_expires)) {
            throw new \RuntimeException ('"refresh expires" is not set on the token');
        }
        return $refresh_expires < $this->getTimeNow();
    }

    /**
     * Quick access to see if the refresh token is unexpired
     */
    public function refreshIsActive(): bool {
        return !$this->refreshHasExpired();
    }

    /**
     * Shortcut to see if we should and are able to use the refresh token
     */
    public function shouldRefresh(): bool {
        return $this->hasExpired() && $this->refreshIsActive();
    }

    /**
     * Extend the base class' serialisation
     */
    public function jsonSerialize() {
        $parameters = parent::jsonSerialize();
        if ($this->refresh_expires) {
            $parameter['refresh_expires'] = $this->refresh_expires;
        }
        return $parameters;
    }

}