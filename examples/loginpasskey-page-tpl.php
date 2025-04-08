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
    <title>LoginRegister Example</title>
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
    <form action="./">
        <label for="login_name">
            <?=__("Enter your username or email address")?>
        </label>
        <input id="login_name" type="text" class="ProcessLoginName webauthn" required>
        <button id="lpk">Passkey</button>
    </form>

    <div id="end">
        <p></p>
    </div>
</div>

<script>
    const apiUrl = "<?=$page->lpkGetApiUrl()?>";

    // hacky solution for iOS not always honouring DOMContentLoaded
    function runOnStart() {
        const btn = document.getElementById('lpk')
        let end = document.getElementById('end')

        btn.addEventListener('click', (e) => {
            e.preventDefault()
            lpk.action(`${apiUrl}start`).then (res => {
                console.log(res)
                if(res && res.msg) {
                    end.textContent = res.msg
                }
                if(res && res.error) {
                    end.textContent = res.error
                }
            })
        })
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