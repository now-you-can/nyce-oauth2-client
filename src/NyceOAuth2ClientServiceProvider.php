<?php

namespace NowYouCan\NyceOAuth2\Client;

use NowYouCan\NyceOAuth2\Client\Services\Contracts\AuthManagerContract;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use NowYouCan\NyceOAuth2\Client\Services\NyceOAuthClientServicesManager;
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

        // Bind a single instance of NyceOAuthClientServicesManager.  It holds
        // an instance of NyceOAuthClientService for each configured service
        $this->app->singleton (AuthManagerContract::class, function () {
            return new NyceOAuthClientServicesManager(config('nyceoauth2client.default_http_options'));
        });

        // Bind the token objects as well
        // Fetch by: app('nyceoauth.token.{$name}')
        foreach (config('nyceoauth2client.connections') as $name => $config) {
            $this->app->bind ("nyceoauth.token.{$name}", function() use ($name) {
                $cookie_token = "nyceoauth2client.{$name}.token";
                return session()->has($cookie_token) ?  session()->get($cookie_token) : new NyceAccessToken();
            });
        }

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
