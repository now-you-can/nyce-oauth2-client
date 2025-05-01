<?php

use Illuminate\Support\Facades\Route;
use NowYouCan\NyceOAuth2\Client\Http\Controllers\NyceOAuth2ClientController;


Route::group (['middleware' => 'web', 'prefix' => 'nyceoauth', 'as' => 'nyceoauth.'], function() {
    Route::get ('/resource-auth-send', [NyceOAuth2ClientController::class, 'oauth2ResourceOwnerLogin'])->name(config('nyceoauth2client.routes.oauth2ownerlogin'));
    Route::get ('/resource-owner-reply', [NyceOAuth2ClientController::class, 'catchResourceOwnerReply'])->name(config('nyceoauth2client.routes.oauth2ownerresponse'));
    Route::get ('/auth-setup',   [NyceOAuth2ClientController::class, 'setupNyceOAuth2'])->name(config('nyceoauth2client.routes.oauth2request'));
    Route::get ('/auth-refresh', [NyceOAuth2ClientController::class, 'refreshNyceOAuth2'])->name(config('nyceoauth2client.routes.oauth2refresh'));
});
