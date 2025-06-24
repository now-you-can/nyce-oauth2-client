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

    /**
     * This version supports multiple OAuth connections, the list of which are
     * defined below.  One of these connections must be our default
     */
    'default' => 'ifs_user',

    /**
     * The OAuth2Client middleware will try to keep tokens current by checking
     * to see if their expiry is approaching, and then requesting a refresh if
     * necessary.  The keep-alive list is defined here:
     */
    'keep_alive' => [ 'ifs_user', 'ifs_service' ],

    /**
     * Here's the list of the connections.  As discussed in the comment above,
     * the connection arrays must conform with League's connection options.
     * Of course, your applicatoin may add or remove list entries according to
     * how it needs to connect to remote resources.
     */
    'connections' => [

        'conn1' => [
            'auth_type' => 'local-auth', // remote-auth;local-auth;client-id
            'oauth_abstract_provider_details' => [
                'clientId'     => env('NYCE_OAUTH2_CLIENT_ID_1'),
                'clientSecret' => env('NYCE_OAUTH2_SECRET_1'),
                'redirectUri'  => env('APP_URL') . '/nyceoauth/resource-owner-reply/conn1',
            ],
            'oauth_generic_provider_details' => [
                'clientId'                => env('NYCE_OAUTH2_CLIENT_ID_1'),
                'clientSecret'            => env('NYCE_OAUTH2_SECRET_1'),
                'redirectUri'             => env('APP_URL') . '/nyceoauth/resource-owner-reply/conn1', // last param must match connection name
                'urlAuthorize'            => env('NYCE_WEB_SVC_PROTOCOL_1') . env('NYCE_WEB_SVC_SERVER_1') . env('NYCE_AUTH_PATH_1'),
                'urlAccessToken'          => env('NYCE_WEB_SVC_PROTOCOL_1') . env('NYCE_WEB_SVC_SERVER_1') . env('NYCE_TOKEN_REQUEST_PATH_1'),
                'urlResourceOwnerDetails' => env('NYCE_WEB_SVC_PROTOCOL_1') . env('NYCE_WEB_SVC_SERVER_1') . env('NYCE_RESOURCE_OWNER_PATH_1'),
            ],
        ],

        'conn2' => [
            'auth_type' => 'client-id',
            'oauth_abstract_provider_details' => [
                'clientId'     => env('NYCE_OAUTH2_CLIENT_ID_2'),
                'clientSecret' => env('NYCE_OAUTH2_SECRET_2'),
                'redirectUri'  => env('APP_URL') . '/nyceoauth/resource-owner-reply/conn2',
            ],
            'oauth_generic_provider_details' => [
                'clientId'                => env('NYCE_OAUTH2_CLIENT_ID_2'),
                'clientSecret'            => env('NYCE_OAUTH2_SECRET_2'),
                'redirectUri'             => env('APP_URL') . '/nyceoauth/resource-owner-reply/conn2',
                'urlAuthorize'            => env('NYCE_WEB_SVC_PROTOCOL_2') . env('NYCE_WEB_SVC_SERVER_2') . env('NYCE_AUTH_PATH_2'),
                'urlAccessToken'          => env('NYCE_WEB_SVC_PROTOCOL_2') . env('NYCE_WEB_SVC_SERVER_2') . env('NYCE_TOKEN_REQUEST_PATH_2'),
                'urlResourceOwnerDetails' => env('NYCE_WEB_SVC_PROTOCOL_2') . env('NYCE_WEB_SVC_SERVER_2') . env('NYCE_RESOURCE_OWNER_PATH_2'),
            ],
        ],

    ],

    'routes' => [
        'oauth2fallback' => 'home',
    ],

    'default_http_options' => [
        'verify' => false,
    ],

];