<?php

namespace NowYouCan\NyceOAuth2\Client;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use NowYouCan\NyceOAuth2\Client\Services\Contracts\AuthContract;
use NowYouCan\NyceOAuth2\Client\Services\OAuthClientService;
use NowYouCan\NyceOAuth2\Client\Middleware\NyceOAuth2ClientMiddleware;


class NyceOauth2ClientServiceProvider extends ServiceProvider {

    /**
     * Register any application services.
     */
    public function register()
    {
        // Merge default configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/nyceoauth2client-config.php', 'nyceoauth2client');

        // Bind the OAuth2 provider as a singleton in the container.
        $this->app->singleton (AuthContract::class, function($app) {
            return new OAuthClientService (config('nyceoauth2client.oauth_generic_provider_details'));
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
