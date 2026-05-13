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
    protected const MAX_REQUEST_BODY_BYTES = 65536;

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
        self::sendJsonHeaders();

        if(self::requestBodyTooLarge()) {
            http_response_code(413);
            return self::requestBodyTooLargeResponse();
        }

        $modules = wire('modules');
        if(!$modules->isInstalled('LoginPassKey')) return false;

        return $modules->get('LoginPassKey')->handleApiStep($data, $step);
    }

    protected static function sendJsonHeaders(): void
    {
        if(headers_sent()) return;

        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
    }

    protected static function requestBodyTooLarge(): bool
    {
        return (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > self::MAX_REQUEST_BODY_BYTES;
    }

    protected static function requestBodyTooLargeResponse(): \stdClass
    {
        $response = new \stdClass();
        $response->end = true;
        $response->msg = 'Request body too large';
        $response->errno = 413;
        $response->next = 'end';
        return $response;
    }

}
