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

$post = trim(file_get_contents('php://input'));

if ($post) {
    $data = \json_decode($post, null, 512, 0);
    $lpk = $modules->get('LoginPassKey');
    return \json_encode($lpk->handleApiStep($data, $input->urlSegment(1)));
}
