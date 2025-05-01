<?php

namespace NowYouCan\NyceOAuth2\Client;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use NowYouCan\NyceOAuth2\Client\Services\Contracts\AuthContract;
use NowYouCan\NyceOAuth2\Client\Services\NyceOAuthClientService;
use NowYouCan\NyceOAuth2\Client\Token\NyceAccessToken;
use NowYouCan\NyceOAuth2\Client\Middleware\NyceOAuth2ClientMiddleware;


class NyceOAuth2ClientServiceProvider extends ServiceProvider {

    /**
     * Register any application services.
     */
    public function register()
    {
        // Merge default configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/nyceoauth2client-config.php', 'nyceoauth2client');

        // Bind the OAuth2 provider as a singleton in the container.
        $this->app->singleton (AuthContract::class, function() {
            return new NyceOAuthClientService (
                config('nyceoauth2client.oauth_generic_provider_details'),
                config('nyceoauth2client.default_http_options')
            );
        });

        // Bind a token object as well
        $this->app->singleton (NyceAccessToken::class, function() {
            $cookie_token = config('nyceoauth2client.cookie_namespace') . config('nyceoauth2client.cookie_token');
            if (session()->has(config($cookie_token))) {
                return config('nyceoauth2client.session_data')==='object' ? session()->get($cookie_token) : new NyceAccessToken(config($cookie_token));
            }
            return new NyceAccessToken();
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot (Router $router)
    {
        $this->loadRoutesFrom (__DIR__ . '/routes/web.php');
        $router->aliasMiddleware (NyceOAuth2ClientMiddleware::class, NyceOAuth2ClientMiddleware::class);

        $this->publishes([
            __DIR__ . '/../config/nyceoauth2client-config.php' => config_path('nyceoauth2client.php'),
        ], 'config');
        $this->publishes([
            __DIR__ . '/routes/web.php' => base_path('/routes/vendor/nyce-oauth2client.php'),
        ], 'routes');
    }

}
