<?php

namespace ProcessWire;

/*************************
 * Add the following to your Routes.php file:

require_once __DIR__ . '/LoginPassKeyAppApi.php';

 * and in your routes array:

'lpk' => [
    ['OPTIONS', 'test', ['GET']], // this is needed for CORS Requests
    ['POST', 'start',    LoginPassKeyAppApi::class, 'start'],
    ['POST', 'finduser', LoginPassKeyAppApi::class, 'findUser'],
    ['POST', 'register', LoginPassKeyAppApi::class, 'register'],
    ['POST', 'verify',   LoginPassKeyAppApi::class, 'verify'],
    ['POST', 'discover-start',  LoginPassKeyAppApi::class, 'discoverStart'],
    ['POST', 'discover-verify', LoginPassKeyAppApi::class, 'discoverVerify'],
    ['POST', 'end',      LoginPassKeyAppApi::class, 'end']
]

 * then change the API ENDPOINT in the LoginPassKey module configuration to /api/lpk/
 ************************/

class LoginPassKeyAppApi
{
    public static function test(): array
    {
        return ['test successful'];
    }

    public static function start($data) :\stdClass | bool
    {
        return self::handleStep($data, 'start');
    }

    public static function findUser($data) :\stdClass | bool
    {
        return self::handleStep($data, 'finduser');
    }

    public static function register($data) :\stdClass | bool
    {
        return self::handleStep($data, 'register');
    }

    public static function verify($data) :\stdClass | bool
    {
        return self::handleStep($data, 'verify');
    }

    public static function discoverStart($data) :\stdClass | bool
    {
        return self::handleStep($data, 'discover-start');
    }

    public static function discoverVerify($data) :\stdClass | bool
    {
        return self::handleStep($data, 'discover-verify');
    }

    public static function end($data) :\stdClass | bool
    {
        return self::handleStep($data, 'end');
    }

    protected static function handleStep($data, string $step): \stdClass | bool
    {
        $modules = wire('modules');
        if(!$modules->isInstalled('LoginPassKey')) return false;

        return $modules->get('LoginPassKey')->handleApiStep($data, $step);
    }

}
