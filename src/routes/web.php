<?php

use Illuminate\Support\Facades\Route;


Route::group ([
    'middleware' => 'web', 'prefix' => 'nyceoauth', 'as' => 'nyceoauth.',
    'namespace' => 'NowYouCan\NyceOAuth2\Client\Http\Controllers',
], function() {
    Route::get ('/auth-setup',   'NyceOAuth2ClientController@setupNyceOAuth2')->name(config('nyceoauth2client.routes.oauth2request'));
    Route::get ('/auth-refresh', 'NyceOAuth2ClientController@refreshNyceOAuth2')->name(config('nyceoauth2client.routes.oauth2refresh'));
});
