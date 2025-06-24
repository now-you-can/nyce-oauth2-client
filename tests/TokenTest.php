<?php

namespace NowYouCan\NyceOAuth2\Client\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use NowYouCan\NyceOAuth2\Client\Services\Contracts\AuthContract;
use NowYouCan\NyceOAuth2\Client\Services\Contracts\AuthManagerContract;
use NowYouCan\NyceOAuth2\Client\Services\NyceOAuthClientService;
use NowYouCan\NyceOAuth2\Client\Services\NyceOAuthClientServicesManager;
use NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken;

class TokenTest extends OAuthBaseTestCase
{

    protected function doBindings (string $svc_name, array $configs): void {

        // Step 1: Prepare mocked Guzzle response
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'access_token'  => 'mock_access_token',
                'expires_in'    => 3600,
                'refresh_token' => 'mock_refresh_token',
            ])),
        ]);
        $handler = HandlerStack::create($mock);

        // Step 2: Replace the real service with the mocked version
        $this->app->bind (AuthContract::class, function ($app) use ($svc_name, $configs, $handler) {
            return new NyceOAuthClientService ($svc_name, $configs['oauth_generic_provider_details'], ['handler' => $handler]);
        });
        $this->app->bind (AuthManagerContract::class, function ($app) use ($handler) {
            return new NyceOAuthClientServicesManager (['handler' => $handler]);
        });
    }


    public function test_client_configs_redirect_to_correct_auth_route() {
        foreach (config('nyceoauth2client.connections') as $svc_name => $configs) {

            $this->doBindings ($svc_name, $configs);

            // Step 3: Call the actual route
            $response = $this->get(route('nyceoauth.establish-remote-auth', ['service_name' => $svc_name]));

            $desired_redirect = 'nyceoauth.' . match ($configs['auth_type']) {
                'local-auth'  => 'resource-owner-pass',
                'remote-auth' => 'resource-owner-user-login',
                'client-id'   => 'resource-owner-client-creds',
                default       => '**error**',
            };

            // Step 4: Call assertions based on the response
            $response->assertStatus(302);
            $response->assertRedirectToRoute ($desired_redirect, ['service_name' => $svc_name]);

        }
    }

    public function test_password_route_returns_token() {
        $svc_name = 'local_conn';
        $this->doBindings ('local_conn', config("nyceoauth2client.connections.local_conn"));
        $response = $this->post (route('nyceoauth.resource-owner-pass', [
            'remoteuser' => 'dummy_user',
            'remotepw'   => 'dummy_pw',
            'service_name' => $svc_name,
        ]));
        $response->assertStatus(200);
        $response->assertSee('mock_access_token');
        $response->assertSee('mock_refresh_token');
    }

    public function test_remote_login_route_returns_redirect() {
        $svc_name = 'remote_conn';
        $this->doBindings ($svc_name, config("nyceoauth2client.connections.$svc_name"));
        $response = $this->get(route('nyceoauth.resource-owner-user-login', ['service_name' => $svc_name]));
        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('http://fake-server/auth?state=', $location);
    }

    public function test_client_credentials_route_returns_redirect() {
        $svc_name = 'clientid_conn';
        $this->doBindings ($svc_name, config("nyceoauth2client.connections.$svc_name"));
        $response = $this->get(route('nyceoauth.resource-owner-client-creds', ['service_name' => $svc_name]));
        $response->assertStatus(302);
        $response->assertRedirectToRoute (config('nyceoauth2client.routes.oauth2fallback'));
        $this->assertFalse (session()->has('error'));
    }

    public function test_token_refresh_redirects() {
        $svc_name = 'clientid_conn';
        session()->put("nyceoauth2client.{$svc_name}.token", new NyceAccessToken([
            'access_token'  => 'dummyToken',
            'refresh_token' => 'abcdefg',
            'expires_in'    => 3600,
        ]));
        $this->doBindings ($svc_name, config("nyceoauth2client.connections.$svc_name"));
        $response = $this->get(route('nyceoauth.auth-refresh', ['service_name' => $svc_name]));
        $response->assertStatus(302);
        $response->assertRedirectToRoute (config('nyceoauth2client.routes.oauth2fallback'));
        $this->assertTrue (session()->has('success'));
    }

}