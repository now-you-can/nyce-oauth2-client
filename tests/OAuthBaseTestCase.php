<?php

namespace NowYouCan\NyceOAuth2\Client\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class OAuthBaseTestCase extends BaseTestCase {

    protected function getPackageProviders ($app) {
        return [
            \NowYouCan\NyceOAuth2\Client\NyceOAuth2ClientServiceProvider::class,
        ];
    }

    protected function getNyceTestConfig(): array {
        return [
            'local_conn' => [
                'auth_type' => 'local-auth',
                'oauth_generic_provider_details' => [
                    'clientId'                => 'fake-client-id',
                    'clientSecret'            => 'fake-secret',
                    'redirectUri'             => 'http://lolcalhost/nyceoauth/resource-owner-reply/local_conn',
                    'urlAuthorize'            => 'http://fake-server/auth',
                    'urlAccessToken'          => 'http://fake-server/token',
                    'urlResourceOwnerDetails' => 'http://fake-server/user',
                ],
            ],
            'remote_conn' => [
                'auth_type' => 'remote-auth',
                'oauth_generic_provider_details' => [
                    'clientId'                => 'fake-client-id',
                    'clientSecret'            => 'fake-secret',
                    'redirectUri'             => 'http://lolcalhost/nyceoauth/resource-owner-reply/remote_conn',
                    'urlAuthorize'            => 'http://fake-server/auth',
                    'urlAccessToken'          => 'http://fake-server/token',
                    'urlResourceOwnerDetails' => 'http://fake-server/user',
                ],
            ],
            'clientid_conn' => [
                'auth_type' => 'client-id',
                'oauth_generic_provider_details' => [
                    'clientId'                => 'fake-client-id',
                    'clientSecret'            => 'fake-secret',
                    'redirectUri'             => 'http://lolcalhost/nyceoauth/resource-owner-reply/clientid_conn',
                    'urlAuthorize'            => 'http://fake-server/auth',
                    'urlAccessToken'          => 'http://fake-server/token',
                    'urlResourceOwnerDetails' => 'http://fake-server/user',
                ],
            ],
        ]; 
    }

    protected function getEnvironmentSetUp ($app) {
        $app['config']->set('nyceoauth2client.connections', $this->getNyceTestConfig());
        $app['config']->set('nyceoauth2client.routes.oauth2fallback', 'home');

        $app['router']->get('nyceoauth/establish-remote-auth/{service_name?}', [
            'uses' => 'NowYouCan\\NyceOAuth2\\Client\\Http\\Controllers\\NyceOAuth2ClientController@establishOAuthMethod',
            'as'   => 'nyceoauth.establish-remote-auth',
        ]);
        $app['router']->get('nyceoauth/resource-owner-user-login/{service_name?}', [
            'uses' => 'NowYouCan\\NyceOAuth2\\Client\\Http\\Controllers\\NyceOAuth2ClientController@oauth2ResourceOwnerUserLogin',
            'as'   => 'nyceoauth.resource-owner-user-login',
        ]);
        $app['router']->post('nyceoauth/resource-owner-pass/{service_name?}', [
            'uses' => 'NowYouCan\\NyceOAuth2\\Client\\Http\\Controllers\\NyceOAuth2ClientController@oauth2ByPassword',
            'as'   => 'nyceoauth.resource-owner-pass',
        ]);
        $app['router']->get('nyceoauth/resource-owner-client-creds/{service_name?}', [
            'uses' => 'NowYouCan\\NyceOAuth2\\Client\\Http\\Controllers\\NyceOAuth2ClientController@oauth2ByClientCreds',
            'as'   => 'nyceoauth.resource-owner-client-creds',
        ]);
        $app['router']->get('nyceoauth/auth-refresh/{service_name?}', [
            'uses' => 'NowYouCan\\NyceOAuth2\\Client\\Http\\Controllers\\NyceOAuth2ClientController@refreshOAuth2',
            'as'   => 'nyceoauth.auth-refresh',
        ]);
        $app['router']->get('home', ['as' => 'home', 'uses' => fn() => response('Dummy response')]);

    }
}