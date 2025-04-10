<?php

namespace NowYouCan\NyceOAuth2\Client\Services\Contracts;

interface AuthContract {

    public function getAccessTokenByClientCreds (array $http_options = [], bool $save_to_session = true);
    public function getAccessTokenByAuthCode (string $code, array $http_options = [], bool $save_to_session = true);
    public function getAccessTokenByRefresh (array $http_options = [], bool $save_to_session = true);

    public function saveTokenToSession (bool $save = true): void;
    public function getToken(): string;
    public function getExpiration(): int;
    public function hasExpired(): bool;
    public function getRefreshToken(): string;
    public function getRefreshExpiration(): int;
    public function refreshHasExpired(): bool;

}
