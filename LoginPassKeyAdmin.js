/*
 * Copyright (c) 2025.
 * Clip Magic - Prue Rowland
 * Web: www.clipmagic.com.au
 * Email: admin@clipmagic.com.au
 */


    // hacky solution for iOS not always honouring DOMContentLoaded
function runOnStart() {
    const btn = document.getElementById('lpk')
    const btnDiscover = document.getElementById('lpk-discover')
    let end = document.getElementById('end')

    // At least one button must exist
    if (!btn && !btnDiscover) return

    // Original login with username
    if (btn) {
        btn.addEventListener('click', (e) => {
            e.preventDefault()

            lpk.action(`${apiUrl}start`).then (res => {
                console.log(res)
                if(res && res.errno) {
                    if(res.errno !== 101) {
                        document.getElementById('end').textContent = res.msg
                    } else {
                        window.location.replace(window.location.href)
                    }
                }
            })
        })
    }

    // Usernameless login (discoverable credentials)
    if (btnDiscover) {
        btnDiscover.addEventListener('click', (e) => {
            e.preventDefault()

            lpk.discoverLogin(apiUrl).then(res => {
                console.log(res)
                if (res && res.errno) {
                    if (res.errno !== 101) {
                        document.getElementById('end').textContent = res.msg
                    } else {
                        window.location.replace(window.location.href)
                    }
                }
            })
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

