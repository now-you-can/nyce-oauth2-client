<?php

namespace NowYouCan\NyceOAuth2\Client\Services;

use NowYouCan\NyceOAuth2\Client\Services\Contracts\AuthManagerContract;
use NowYouCan\NyceOAuth2\Client\Services\NyceOAuthClientService;
use NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

class NyceOAuthClientServicesManager implements AuthManagerContract {

    protected array $services = []; // array<NowYouCan\NyceOAuth2\Client\Services\NyceOAuthClientService>

    public function __construct (array $http_options = []) {

        $connections = config('nyceoauth2client.connections');

        foreach ($connections as $svc_name => $configs) {
            $this->services[$svc_name] = new NyceOAuthClientService ($svc_name, $configs['oauth_generic_provider_details'], $http_options);
        }

    }

    public function getService (?string $service_name = null): NyceOAuthClientService {
        if ($service_name === null) {
            return $this->getDefaultService();
        } elseif (!isset($this->services[$service_name])) {
            throw new InvalidArgumentException("OAuth service [{$service_name}] is not defined");
        }
        return $this->services[$service_name];
    }

    public function getDefaultService(): NyceOauthClientService {
        return $this->services[config('nyceoauth2client.default')];
    }

    public function getAllServices(): array {
        return $this->services;
    }

    // -----------------------------------------------------------------------
    //
    // Contract implementation functions
    //
    // These functions merely pass on the call to the actual services named in
    // the incoming parameters.
    //
    //

    public function sendUserToResourceOwner (?string $svc_name, array $options = []): RedirectResponse {
        return $this->getService($svc_name)->sendUserToResourceOwner($options);
    }

    public function getAccessTokenByClientCreds (?string $svc_name): void {
        $this->getService($svc_name)->getAccessTokenByClientCreds();
    }

    public function getAccessTokenByAuthCode (?string $svc_name, string $code): void {
        $this->getService($svc_name)->getAccessTokenByAuthCode();
    }

    public function getAccessTokenByPassword (?string $svc_name, string $username, string $password): NyceAccessToken {
        return $this->getService($svc_name)->getAccessTokenByPassword ($username, $password);
    }

    public function getAccessTokenByRefresh (?string $svc_name): void {
        $this->getService($svc_name)->getAccessTokenByRefresh();
    }


    public function getToken (?string $svc_name): string {
        return $this->getService($svc_name)->getToken();
    }
    public function getExpiration (?string $svc_name): int {
        return $this->getService($svc_name)->getExpiration();
    }
    public function hasExpired (?string $svc_name): bool {
        return $this->getService($svc_name)->hasExpired();
    }
    public function getRefreshToken (?string $svc_name): string {
        return $this->getService($svc_name)->getRefreshToken();
    }
    public function getRefreshExpiration (?string $svc_name): int {
        return $this->getService($svc_name)->getRefreshExpiration();
    }
    public function refreshHasExpired (?string $svc_name): bool {
        return $this->getService($svc_name)->refreshHasExpired();
    }


}