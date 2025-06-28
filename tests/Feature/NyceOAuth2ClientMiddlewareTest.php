<?php

namespace NowYouCan\NyceOAuth2\Client\Tests\Feature;

use NowYouCan\NyceOAuth2\Client\Tests\OAuthBaseTestCase;
use Illuminate\Support\Facades\Session;

class FakeOAuthToken {

    private bool $isActive = false;
    private bool $refreshIsActive = false;
    private bool $refreshHasExpired = false;

    public function __construct (array $flags) {
        foreach ($flags as $prop => $val) { $this->{$prop} = $val; }
    }
    // public function setFlags(array $flags): void {
    //     foreach ($flags as $prop => $val) {
    //         $this->{$prop} = $val;
    //     }
    // }
    public function isActive(): bool { return $this->isActive; }
    public function refreshIsActive(): bool { return $this->refreshIsActive; }
    public function refreshHasExpired(): bool { return $this->refreshHasExpired; }
}


class NyceOAuth2ClientMiddlewareTest extends OAuthBaseTestCase
{
    public function test_redirects_when_no_token_present() {
        $response = $this->get('/protected');
        $response->assertRedirect(route('nyceoauth.resource-owner-pass', ['service_name' => 'local_conn']));
        $response->assertSessionHas('url.intended', url('/protected'));
    }

    public function test_redirects_to_auth_on_expired_token_and_expired_refresh() {
        Session::put ('nyceoauth2client.local_conn.token', new FakeOAuthToken ([
            'isActive' => false,
            'refreshIsActive' => false,
            'refreshHasExpired' => true,
        ]));
        $response = $this->get('/protected');

        $response->assertRedirect(route('nyceoauth.resource-owner-pass', ['service_name' => 'local_conn']));
        $response->assertSessionHas('url.intended', url('/protected'));
    }

    public function test_redirects_to_refresh_on_expired_token_and_active_refresh() {
        Session::put ('nyceoauth2client.local_conn.token', new FakeOAuthToken ([
            'isActive' => false,
            'refreshIsActive' => true,
            'refreshHasExpired' => false,
        ]));
        $response = $this->get('/protected');

        $response->assertRedirect(route('nyceoauth.auth-refresh', ['service_name' => 'local_conn']));
        $response->assertSessionHas('url.intended', url('/protected'));
    }

    public function test_allows_request_when_token_is_active() {
        Session::put ('nyceoauth2client.local_conn.token', new FakeOAuthToken ([
            'isActive' => true,
            'refreshIsActive' => true,
            'refreshHasExpired' => false,
        ]));
        $response = $this->get('/protected');

        $response->assertOk();
        $response->assertSee('protected content');
    }

    protected function mockToken(array $flags): object
    {
        $mock = new class {
            public bool $isActive = false;
            public bool $refreshIsActive = false;
            public bool $refreshHasExpired = false;

            public function isActive(): bool
            {
                return $this->isActive;
            }

            public function refreshIsActive(): bool
            {
                return $this->refreshIsActive;
            }

            public function refreshHasExpired(): bool
            {
                return $this->refreshHasExpired;
            }
        };

        foreach ($flags as $method => $value) {
            $mock->$method = $value;
        }

        return $mock;
    }
}