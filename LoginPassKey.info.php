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
    'title' => 'Login with a PassKey for ProcessWire',
    'author' => 'Clip magic',
    'version' => '0.0.2Beta',
    'summary' => 'ProcessWire module that enables login with a passkey',
    'icon' => 'key',
    'autoload' => true,
    'singular' => true,
    'requires' => ["PHP>=8.1", "ProcessWire>=3.0"],
    'installs' => ['ProcessLoginPassKey'],
    'href' => 'https://processwire.com/modules/login-pass-key/'
];
