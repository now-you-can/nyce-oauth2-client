<?php

namespace NowYouCan\NyceOAuth2\Client\Facades;

use Illuminate\Support\Facades\Facade;

class NyceOAuth2ClientFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        // This key matches the binding in the service provider.
        return 'nyceauthcli';
    }
}

