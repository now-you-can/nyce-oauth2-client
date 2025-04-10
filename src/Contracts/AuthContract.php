<?php

namespace NowYouCan\NyceOAuth2\Client\Contracts;

use NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken;

interface AuthContract {

    public function getAccessTokenByClientCreds();
    public function getAccessTokenByAuthCode (string $code);

    public function saveTokenToSession (NyceAccessToken $token, bool $save): void;
    public function getToken(): string;
    public function getExpiration(): int;
    public function hasExpired(): bool;
    public function getRefreshToken(): string;
    public function getRefreshExpiration(): int;
    public function refreshHasExpired(): bool;

}
