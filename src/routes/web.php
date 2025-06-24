<?php

use Illuminate\Support\Facades\Route;
use NowYouCan\NyceOAuth2\Client\Http\Controllers\NyceOAuth2ClientController;


Route::group (['middleware' => 'web', 'prefix' => 'nyceoauth', 'as' => 'nyceoauth.'], function() {

    Route::get ('/establish-remote-auth/{service_name?}',       [NyceOAuth2ClientController::class, 'establishOAuthMethod'])->name('establish-remote-auth');
    Route::get ('/resource-owner-user-login/{service_name?}',   [NyceOAuth2ClientController::class, 'oauth2ResourceOwnerUserLogin'])->name('resource-owner-user-login');
    Route::post ('/resource-owner-pass/{service_name?}',        [NyceOAuth2ClientController::class, 'oauth2ByPassword'])->name('resource-owner-pass');
    Route::get ('/resource-owner-client-creds/{service_name?}', [NyceOAuth2ClientController::class, 'oauth2ByClientCreds'])->name('resource-owner-client-creds');

    Route::get ('/auth-refresh/{service_name?}', [NyceOAuth2ClientController::class, 'refreshOAuth2'])->name('auth-refresh');

    Route::get ('/resource-owner-reply/{service_name}', [NyceOAuth2ClientController::class, 'catchResourceOwnerReply'])->name('resource-owner-reply');

});
