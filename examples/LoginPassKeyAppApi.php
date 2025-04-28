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
        $modules = wire('modules');
        if(!$modules->isInstalled('LoginPassKey')) return false;

        $lpkData = new \stdClass();
        $lpkData->data = $data;
        return $lpkData;
    }

    public static function findUser($data) :\stdClass | bool
    {
        $modules = wire('modules');
        if(!$modules->isInstalled('LoginPassKey')) return false;
        $lpk = $modules->get('LoginPassKey');
        $session = wire('session');

        $lpkData = new \stdClass();
        $lpkData->data = $data;

        // Kick off the process
        $foundUser = $lpk->findUser($data->un);

        // save the username as we'll need it later
        $session->setFor('lpk', 'username', $data->un);

        if(empty($foundUser->msg)) {
            // any messages go straight to 'end'
            // now we need the ProcessWire User object
            $feUserObj = $lpk->getUserByField($foundUser->un);

            if ($feUserObj instanceof User) {
                $session->setFor('lpk', 'userid', $feUserObj->id);
                if (!$feUserObj->isLoggedin()) {
                    // they're not logged in but have registered for WebAuthn
                    $lpkData->data->verifyArgs = $lpk->verifyUser($feUserObj);
                    $lpkData->data->next = 'verify';

                }
                else {
                    // they're logged in but haven't registered for WebAuthn
                    $lpkData->data->pk = $lpk->preRegisterUser($feUserObj);
                    $lpkData->data->feUserid = $feUserObj->id;
                    $lpkData->data->next = 'register';
                }
            }
        }
        else {
//            $lpkData->data->msg = $foundUser->msg;
//            $lpkData->data->next = 'end';
            $lpkData->msg = $foundUser->msg;
            $lpkData->data->next = 'end';
        }
        return $lpkData;
    }

    public static function register($data) :\stdClass
    {
        // logged in and eligible to register for WebAuthn
        $modules = wire('modules');
        if(!$modules->isInstalled('LoginPassKey')) return false;
        $lpk = $modules->get('LoginPassKey');
        $session = wire('session');

        $lpkData = new \stdClass();
        $lpkData->data = new \stdClass();
        $user = wire('user');
        if(!empty($data->aarcreate)) {
            $created = $lpk->registerUser($user, $data->aarcreate);

            if(!!$created) {
                $session->setFor('lpk', 'success', 'success');
                $session->removeFor('lpk', 'username');
                $lpkData = $created;
            }
        } else {
            $lpkData->msg = $lpk->getErrorMessage(2);
            $lpkData->errno = 2;
        }
        return $lpkData;
    }

    public static function verify($data) :\stdClass | bool
    {
        // logged out but has previously registered. Verify to log in
        $modules = wire('modules');
        if(!$modules->isInstalled('LoginPassKey')) return false;
        $lpk = $modules->get('LoginPassKey');
        $session = wire('session');
        $pages = wire('pages');

        $lpkData = new \stdClass();
        $lpkData->data = $data;
        if((!empty($data->errno) && $data->errno !== 101) || empty($data->aarverify)) {
            $lpkData->msg = $lpk->getErrorMessage($data->errno);
            $lpkData->errno = $data->errno;
            $lpkData->data->next = 'end';
        } else {
            $verified = $lpk->verifyResponse($data->aarverify, $data->challenge, $data->signedData);

            $lpkData->data = $verified;
            if ($verified->errno === 101) {
                $lpkData->msg = $lpk->getErrorMessage(101);
                $lpkData->errno = 101;
                $username = $session->getFor('lpk', 'username');
                $feUser = $lpk->getUserByField($username);
                $session->setFor('lpk', 'success', 'success');
                $session->forceLogin($feUser);

                $goToPage = $lpk->getRedirectUrl();
                if($session->getFor('lpk', 'inadmin')) {
                    $processLogin = $modules->get('ProcessLogin');
                    $processLogin->execute();
                } else {
                    $lpkData->goto = !empty($goToPage) ? $goToPage : $pages->get(1)->httpUrl;
                }
            }
        }
        return $lpkData;
    }

    public static function end($data) :\stdClass | bool
    {
        $modules = wire('modules');
        if(!$modules->isInstalled('LoginPassKey')) return false;

        $lpkData = new \stdClass();
        $lpkData->data = $data;
        return $lpkData;
    }

}