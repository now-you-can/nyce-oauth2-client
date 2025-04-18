<?php

// config/nyceoauth2client-config.php
//
return [

    /*
    | League\Oauth2\Client is a library to provide OAuth2 connection to remote
    | services.  Oddly, when extending the AbstractProvider class,  the config
    | details are split between the instantiation of the class and some static
    | functions in the extending class.  That's why we're splitting them up in
    | the following configs.
    |   > oauth_abstract_provider_details: may be passed directly to League\OAuth2\Client\Provider\AbstractProvider
    |   > oauth_generic_provider_details:  may be passed directly to League\OAuth2\Client\Provider\GenericProvider
    | The keys of the above arrays must match exactly the property names found
    | in their respective classes.
    |   See: League\OAuth2\Client\Tool\GuardedPropertyTrait::fillProperties()
    */

    'oauth_abstract_provider_details' => [
        'clientId'     => env('NYCE_OAUTH2_CLIENT_ID'),
        'clientSecret' => env('NYCE_OAUTH2_SECRET'),
        'redirectUri'  => env('APP_URL') . '/auth-response',
    ],

    'oauth_generic_provider_details' => [
        'clientId'                => env('NYCE_OAUTH2_CLIENT_ID'),
        'clientSecret'            => env('NYCE_OAUTH2_SECRET'),
        'redirectUri'             => env('APP_URL'), // probably should be changed
        'urlAuthorize'            => env('NYCE_WEB_SVC_PROTOCOL') . env('NYCE_WEB_SVC_SERVER') . env('NYCE_AUTH_PATH'),
        'urlAccessToken'          => env('NYCE_WEB_SVC_PROTOCOL') . env('NYCE_WEB_SVC_SERVER') . env('NYCE_TOKEN_REQUEST_PATH'),
        'urlResourceOwnerDetails' => env('NYCE_WEB_SVC_PROTOCOL') . env('NYCE_WEB_SVC_SERVER') . env('NYCE_RESOURCE_OWNER_PATH'),
    ],

    'cookie_namespace' => 'nyceoauth2client.',
    'cookie_token'     => 'NyceOAuth2',
    'session_data'     => 'object',  // object|values; denotes how data is saved to Laravel's session()

    'routes' => [
        'oauth2fallback'      => 'home',
        'oauth2request'       => 'establishIfsAuth',
        'oauth2refresh'       => 'refresnIfsAuth',
        'oauth2ownerlogin'    => 'oauth2ResourceOwnerLogin',
        'oauth2ownerresponse' => 'oauth2ResourceOwnerResponse',
    ],

    'default_http_options' => [
        'verify' => false,
    ],

];