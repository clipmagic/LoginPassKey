/*
 * Copyright (c) 2025.
 * Clip Magic - Prue Rowland
 * Web: www.clipmagic.com.au
 * Email: admin@clipmagic.com.au
 */


    // hacky solution for iOS not always honouring DOMContentLoaded
function runOnStart() {
    const btn = document.getElementById('lpk')
    let end = document.getElementById('end')
    if(!btn) return

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

if(document.readyState !== 'loading') {
    runOnStart();
} else {
    document.addEventListener('DOMContentLoaded', function () {
        runOnStart()
    });
}

