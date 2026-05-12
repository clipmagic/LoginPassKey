<?php namespace ProcessWire;

/*
 * Copyright (c) 2025.
 * Clip Magic
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

// Use this as an example page template. The template should be configured with auto append of _main disabled.

?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>LoginPassKey Example</title>
    <style>
        body {
            width: 100vw;
            min-height: 100vh;
            display: grid;
            align-content: center;
            margin-inline: auto;
            font-family: "Helvetica Neue", Helvetica, Verdana, Arial, sans-serif;
        }

        .container {
            width: fit-content;
            max-width: 80vw;
            text-align: center;
            margin-inline: auto;
        }

        form {
            display: grid;
            gap: 1rem;
            place-items: center;
        }

        input {
            width: 100%;
            padding: 0.5rem;
        }
        button {
            background-color: #93d17b;
            color: #fff;
            font-size: 2rem;
            padding: 0.5rem 1rem;
            border: 1px solid #7baa64;
            width: fit-content;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>
<div class="container">
    <form action="./" method="post">
        <label for="login_name">
            <?=__("Enter your username or email address")?>
        </label>
        <input id="login_name" name="login_name" type="text" class="ProcessLoginName webauthn" autocomplete="username webauthn" required>
        <button id="lpk" type="button">Passkey</button>
        <button id="lpk_discover" type="button">Passkey only</button>
    </form>

    <div id="end">
        <p></p>
    </div>
</div>

<script src="<?=$config->urls($modules->get('LoginPassKey'))?>LoginPassKey.js"></script>
<script>
    let apiUrl = "<?=$page->lpkGetApiUrl()?>";

    // hacky solution for iOS not always honouring DOMContentLoaded
    function runOnStart() {
        const btn = document.getElementById('lpk')
        const discoverBtn = document.getElementById('lpk_discover')
        let end = document.getElementById('end')
        if(!btn || !end) return

        const handleResult = (res) => {
            if(res && res.errno === 101 && res.goto) {
                window.location.href = res.goto
                return
            }

            if(res && res.msg) {
                end.textContent = res.msg
            } else if(res && res.error) {
                end.textContent = res.error
            }
        }

        btn.addEventListener('click', (e) => {
            e.preventDefault()
            lpk.action(apiUrl + 'start').then(handleResult)
        })

        if(discoverBtn) {
            discoverBtn.addEventListener('click', (e) => {
                e.preventDefault()
                lpk.discover(apiUrl).then(handleResult)
            })
        }
    }

    if(document.readyState !== 'loading') {
        runOnStart();
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            runOnStart()
        });
    }
</script>


</body>
</html>
