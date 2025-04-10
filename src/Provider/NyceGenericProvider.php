<?php

namespace NowYouCan\NyceOAuth2\Client\Provider;

use InvalidArgumentException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;


class NyceGenericProvider extends NyceAbstractProvider
{

    use BearerAuthorizationTrait;

    private string  $urlAuthorize;
    private string  $urlAccessToken;
    private string  $urlResourceOwnerDetails;
    private string  $accessTokenMethod = 'POST';
    private ?string $accessTokenResourceOwnerId = null;
    private ?array  $scopes = null;
    private string  $scopeSeparator;
    private string  $responseError = 'error';
    private string  $responseCode;
    private string  $responseResourceOwnerId = 'id';
    private ?string $pkceMethod = null;

    /**
     * @param array $options
     * @param array $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        $this->assertRequiredOptions($options);

        $possible   = $this->getConfigurableOptions();
        $configured = array_intersect_key ($options, array_flip($possible));

        foreach ($configured as $key => $value) {
            $this->$key = $value;
        }

        // Remove all options that are only used locally
        $options = array_diff_key ($options, $configured);

        parent::__construct($options, $collaborators);
    }

    /**
     * Returns all options that can be configured.
     *
     * @return array
     */
    protected function getConfigurableOptions() {
        return array_merge ($this->getRequiredOptions(), [
            'accessTokenMethod', 'accessTokenResourceOwnerId', 'scopeSeparator', 'responseError',
            'responseCode', 'responseResourceOwnerId', 'scopes', 'pkceMethod',
        ]);
    }

    /**
     * Returns all options that are required.
     *
     * @return array
     */
    protected function getRequiredOptions() {
        return ['urlAuthorize', 'urlAccessToken', 'urlResourceOwnerDetails'];
    }

    /**
     * Verifies that all required options have been passed.
     *
     * @param  array $options
     * @return void
     * @throws InvalidArgumentException
     */
    private function assertRequiredOptions(array $options) {
        $missing = array_diff_key(array_flip($this->getRequiredOptions()), $options);
        if (!empty($missing)) {
            throw new InvalidArgumentException (
                'Required options not defined: ' . implode(', ', array_keys($missing))
            );
        }
    }

    /**
     * Implementation of the abstract function
     */
    public function getBaseAuthorizationUrl() {
        return $this->urlAuthorize;
    }

    /**
     * Implementation of the abstract function
     */
    public function getBaseAccessTokenUrl(array $params) {
        return $this->urlAccessToken;
    }

    /**
     * Implementation of the abstract function
     */
    public function getResourceOwnerDetailsUrl (AccessToken $token) {
        return $this->urlResourceOwnerDetails;
    }

    /**
     * Implementation of the abstract function
     */
    public function getDefaultScopes() {
        return $this->scopes;
    }

    /**
     * Standard getter
     */
    protected function getAccessTokenMethod() {
        return $this->accessTokenMethod ?: parent::getAccessTokenMethod();
    }

    /**
     * Standard getter
     */
    protected function getAccessTokenResourceOwnerId() {
        return $this->accessTokenResourceOwnerId ?: parent::getAccessTokenResourceOwnerId();
    }

    /**
     * Standard getter
     */
    protected function getScopeSeparator() {
        return $this->scopeSeparator ?: parent::getScopeSeparator();
    }

    /**
     * Standard getter
     */
    protected function getPkceMethod() {
        return $this->pkceMethod ?: parent::getPkceMethod();
    }

    /**
     * Just a copy from the original base file so that we're able to change it
     * later if necessary.
     */
    protected function checkResponse (ResponseInterface $response, $data)
    {
        if (!empty($data[$this->responseError])) {
            $error = $data[$this->responseError];
            if (!is_string($error)) {
                $error = var_export($error, true);
            }
            $code  = $this->responseCode && !empty($data[$this->responseCode])? $data[$this->responseCode] : 0;
            if (!is_int($code)) {
                $code = intval($code);
            }
            throw new IdentityProviderException($error, $code, $data);
        }
    }

    /**
     * Implementation of the abstract function
     */
    protected function createResourceOwner(array $response, AccessToken $token) {
        return new GenericResourceOwner($response, $this->responseResourceOwnerId);
    }

}
