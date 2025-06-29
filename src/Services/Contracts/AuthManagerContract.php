<?php

namespace NowYouCan\NyceOAuth2\Client\Services\Contracts;

use NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken;
use Illuminate\Http\RedirectResponse;

interface AuthManagerContract {

    public function sendUserToResourceOwner (?string $svc_name, array $options = []): RedirectResponse;
    public function getAccessTokenByClientCreds (?string $svc_name): void;
    public function getAccessTokenByAuthCode (?string $svc_name, string $code): void;
    public function getAccessTokenByPassword (?string $svc_name, string $username, string $password): NyceAccessToken;
    public function getAccessTokenByRefresh (?string $svc_name): NyceAccessToken;

    public function getTokenObj (?string $svc_name): NyceAccessToken;
    public function getToken (?string $svc_name): string;
    public function getExpiration (?string $svc_name): int;
    public function hasExpired (?string $svc_name): bool;
    public function getRefreshToken (?string $svc_name): string;
    public function getRefreshExpiration (?string $svc_name): int;
    public function refreshHasExpired (?string $svc_name): bool;
    public function tokenShouldRefresh (?string $svc_name): bool;

}
