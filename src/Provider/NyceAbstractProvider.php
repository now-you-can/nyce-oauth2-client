<?php

namespace NowYouCan\NyceOAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Grant\AbstractGrant;
use NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;


abstract class NyceAbstractProvider extends AbstractProvider
{

    /**
     * Returns the base URL for authorizing a client.
     *
     * Eg. https://oauth.service.com/authorize
     *
     * @return string
     */
    abstract public function getBaseAuthorizationUrl();

    /**
     * Returns the base URL for requesting an access token.
     *
     * Eg. https://oauth.service.com/token
     *
     * @param array $params
     * @return string
     */
    abstract public function getBaseAccessTokenUrl (array $params);

    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param AccessToken $token
     * @return string
     */
    abstract public function getResourceOwnerDetailsUrl (AccessToken $token);

    /**
     * Returns the default scopes used by this provider.
     *
     * This should only be the scopes that are required to request the details
     * of the resource owner, rather than all the available scopes.
     *
     * @return array
     */
    abstract protected function getDefaultScopes();

    /**
     * Checks a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  array|string $data Parsed response data
     * @return void
     */
    abstract protected function checkResponse (ResponseInterface $response, $data);

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param  array $response
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     */
    abstract protected function createResourceOwner (array $response, AccessToken $token);

    /**
     * Requests and returns the resource owner of given access token.
     *
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     * @throws IdentityProviderException
     * @throws UnexpectedValueException
     * @throws GuzzleException
     */
    public function getResourceOwner (AccessToken $token)
    {
        $response = $this->fetchResourceOwnerDetails($token);
        return $this->createResourceOwner($response, $token);
    }

    /**
     * Requests resource owner details.
     *
     * @param  AccessToken $token
     * @param  array           $http_options
     * @return mixed
     * @throws IdentityProviderException
     * @throws UnexpectedValueException
     * @throws GuzzleException
     */
    protected function fetchResourceOwnerDetails (AccessToken $token, array $http_options = [])
    {
        $url = $this->getResourceOwnerDetailsUrl ($token);
        $request = $this->getAuthenticatedRequest (self::METHOD_GET, $url, $token);
        $response = $this->getParsedResponse ($request, $http_options);
        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }
        return $response;
    }



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

    /**
     * Requests an access token using a specified grant and option set.
     *
     * @param  mixed                $grant
     * @param  array<string, mixed> $options
     * @param  array                $http_options
     * @return AccessTokenInterface
     * @throws IdentityProviderException
     * @throws UnexpectedValueException
     * @throws GuzzleException
     */
    public function getAccessToken ($grant, array $options = [], array $http_options = [])
    {
        $grant = $this->verifyGrant($grant);

        if (isset($options['scope']) && is_array($options['scope'])) {
            $separator = $this->getScopeSeparator();
            $options['scope'] = implode($separator, $options['scope']);
        }

        $params = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
        ];

        if (!empty($this->pkceCode)) {
            $params['code_verifier'] = $this->pkceCode;
        }

        $params   = $grant->prepareRequestParameters($params, $options);
        $request  = $this->getAccessTokenRequest($params);
        $response = $this->getParsedResponse($request, $http_options);
        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }
        $prepared = $this->prepareAccessTokenResponse($response);
        $token    = $this->createAccessToken($prepared, $grant);

        return $token;
    }

    /**
     * Sends a request instance and returns a response instance.
     *
     * WARNING: This method does not attempt to catch exceptions caused by HTTP
     * errors! It is recommended to wrap this method in a try/catch block.
     *
     * This extended version allows us to pass in HTTP options to the `send()`
     * request, which allows us (for example) to ignore certificate validation
     * problems.
     *
     * @param  RequestInterface $request
     * @param  array            $http_options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function getResponse(RequestInterface $request, array $http_options = [])
    {
        return $this->getHttpClient()->send($request, $http_options);
    }

    /**
     * Sends a request and returns the parsed response.
     *
     * @param  RequestInterface $request
     * @param  array            $http_options
     * @return mixed
     * @throws IdentityProviderException
     * @throws UnexpectedValueException
     * @throws GuzzleException
     */
    public function getParsedResponse(RequestInterface $request, array $http_options = [])
    {
        try {
            $response = $this->getResponse($request, $http_options);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
        }

        $parsed = $this->parseResponse($response);

        $this->checkResponse($response, $parsed);

        return $parsed;
    }

}
