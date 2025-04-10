<?php namespace ProcessWire;

/*
 * Copyright (c) 2025.
 * Clip Magic - Prue Rowland
 * Web: www.clipmagic.com.au
 * Email: admin@clipmagic.com.au
 * Licensed under MIT, see LICENSE.TXT
 *
 * ProcessWire 3.x
 * Copyright (C) 2014 by R
 * Licensed under GNU/GPL
 *
 * https://processwire.com
 *
 * This template should be configured with:
 * 1. No children
 * 2. Allow page segments
 * 3. UrlSegments: 'start', 'register', 'verify', 'end'
 * 4. For use on one page only
 * 5. Disallow auto-append of _main.php
 * 6. It must be published and directly accessible from the web
 * 7. The only custom field required is "Title" which is added automatically
 * 8. Disable multi-language features
 *
 */

$post = trim(file_get_contents('php://input'));

if ($post) {
    $data = \json_decode($post, null, 512, 0);
    $next = $input->urlSegment(1);
    $lpkData = new \stdClass();
    $lpkData->data = $data;

    switch ($next) {
        case 'finduser':

            // Kick off the process
            $foundUser = $page->lpkFindUser($data->un);
            // save the username as we'll need it later
            $session->setFor('lpk', 'username', $data->un);
            if(empty($foundUser->msg)) {
                // any messages go straight to 'end'
                // now we need the ProcessWire User object
                $feUserObj = $page->lpkGetUserByField($foundUser->un);
                if ($feUserObj instanceof User) {
                    if (!$feUserObj->isLoggedin()) {
                        // they're not logged in but have registered for WebAuthn
                        $lpkData->data->verifyArgs = $page->lpkVerify($feUserObj);
                        $lpkData->data->next = 'verify';
                    }
                    else {
                        // they're logged in but haven't registered for WebAuthn
                        $lpkData->data->pk = $page->lpkPreRegisterUser($feUserObj);
                        $lpkData->data->feUserid = $feUserObj->id;
                        $lpkData->data->next = 'register';
                    }
                }
            }
            else {
                $lpkData->data->msg = $foundUser->msg;
                $lpkData->data->next = 'end';
            }
            break;

        case 'register':
            // logged in and eligible to register for WebAuthn
            $lpkData->data = new \stdClass();
            $lpkData->data->next = $data->next;
            if(!empty($data->aarcreate)) {
                $created = $page->lpkRegisterUser($user, $data->aarcreate);
                if(!!$created) {
                    $session->setFor('lpk', 'success', 'success');
                    $session->removeFor('lpk', 'username');
                    $lpkData->data->msg = $data->msg;
                }
            } else {
                $lpkData->data->msg = $page->lpkGetErrorMessage(2);
                $lpkData->data->errno = 2;
            }
            return \json_encode($data);
            break;

        case 'verify':
            // logged out but has previously registered. Verify to log in
            if(($data->errno && $data->errno !== 101) || \is_null($data->aarverify)) {
                $lpkData->msg = $page->lpkGetErrorMessage($data->errno);
                $lpkData->errno = $data->errno;
            } else {
                $verified = $page->lpkVerifyResponse($data->aarverify);
                $lpkData->data = $verified;
                if ($verified->errno === 101) {
                    $lpkData->msg = $page->lpkGetErrorMessage(101);
                    $username = $session->getFor('lpk', 'username');
                    $feUser = $page->lpkGetUserByField($username);
                    $session->setFor('lpk', 'success', 'success');
                    $session->forceLogin($feUser);
                }
                $lpkData->data->next = 'end';
            }
            break;

        case 'end':
        case 'start':
        default:
            break;
    }
    return json_encode($lpkData);
}
