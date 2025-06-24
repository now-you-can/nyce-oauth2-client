<?php

namespace NowYouCan\NyceOAuth2\Client\Services\Contracts;

use NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken;
use Illuminate\Http\RedirectResponse;

interface AuthContract {

    public function sendUserToResourceOwner (array $options = []): RedirectResponse;
    public function getAccessTokenByClientCreds(): void;
    public function getAccessTokenByAuthCode(string $code): void;
    public function getAccessTokenByPassword (string $username, string $password): NyceAccessToken;
    public function getAccessTokenByRefresh(): void;

    public function saveTokenToSession(): void;
    public function getToken(): string;
    public function getExpiration(): int;
    public function hasExpired(): bool;
    public function getRefreshToken(): string;
    public function getRefreshExpiration(): int;
    public function refreshHasExpired(): bool;

}
