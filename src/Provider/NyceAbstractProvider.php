<?php

namespace NowYouCan\NyceOAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Grant\AbstractGrant;
use NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken;


abstract class NyceAbstractProvider extends AbstractProvider
{

    /**
     * Creates an access token from a response.
     *
     * The grant that was used to fetch the response can be used to provide
     * additional context.
     *
     * @param  array $response
     * @param  AbstractGrant $grant
     * @return AccessTokenInterface
     */
    protected function createAccessToken (array $response, AbstractGrant $grant)
    {
        return new NyceAccessToken($response);
    }

}