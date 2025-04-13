<?php namespace ProcessWire;
/*
 * Copyright (c) 2025.
 * Clip Magic - Prue Rowland
 * Web: www.clipmagic.com.au
 * Email: admin@clipmagic.com.au
 *
 * ProcessWire 3.x
 * Copyright (C) 2014 by R
 * Licensed under GNU/GPL
 *
 * https://processwire.com
 */

$info = [
    'title' => 'ProcessLoginPassKey',
    'summary' => 'Manage your LoginPassKeys',
    'version' =>' 0.0.2Beta',
    'author' => 'Clip Magic',
    'icon' => 'key',
    'requires' =>[ "ProcessWire>=3.0,", "LoginPassKey"],

    'href' => 'https://processwire.com/modules/login-pass-key/',
    'permission' => 'passkeys',
    'permissions' => [
        'passkeys' => 'Add or remove passkeys'
    ],
    'page' => [
        'name' => 'manage-passkeys',
        'parent' => 'access',
        'title' => 'PassKeys'
    ]
];
