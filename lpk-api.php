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
 */

$lpkMaxRequestBodyBytes = 65536;

if(!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    header('Referrer-Policy: no-referrer');
}

if((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $lpkMaxRequestBodyBytes) {
    http_response_code(413);
    return \json_encode([
        'end' => true,
        'msg' => 'Request body too large',
        'errno' => 413,
        'next' => 'end',
    ]);
}

$rawPost = file_get_contents('php://input', false, null, 0, $lpkMaxRequestBodyBytes + 1);
$rawPost = $rawPost === false ? '' : $rawPost;
if(strlen($rawPost) > $lpkMaxRequestBodyBytes) {
    http_response_code(413);
    return \json_encode([
        'end' => true,
        'msg' => 'Request body too large',
        'errno' => 413,
        'next' => 'end',
    ]);
}

$post = trim($rawPost);

if ($post) {
    $data = \json_decode($post, null, 512, 0);
    $lpk = $modules->get('LoginPassKey');
    return \json_encode($lpk->handleApiStep($data, $input->urlSegment(1)));
}
