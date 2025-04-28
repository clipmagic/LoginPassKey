/*

*** No longer required ***

jQuery(document).ready(function($) {

    $('.btn-delete-checked').hide();

    $('.delete-checkbox').on('input', function() {
        var numChecked = $('.delete-checkbox:checked').length;
        var $otherButtons = $('.add-passkey');
        var $deleteButton = $('.btn-delete-checked');
        if(numChecked > 0) {
            $otherButtons.hide();
            $deleteButton.show();
        } else {
            $otherButtons.show();
            $deleteButton.hide();
        }
    });

});

let lpkConfig = ProcessWire.config.loginPassKey
let apiUrl = lpkConfig.apiUrl

let fwd = {
    un: lpkConfig.un,
    next: lpkConfig.next
}
let data = {
    fn: lpkConfig.fn,
    data: fwd
}
function startApi() {
    document.addEventListener('DOMContentLoaded', function () {
        let btns = document.querySelectorAll("a:has(.add-passkey)")
        if(!btns) return

        btns.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault()
                lpk.action(`${apiUrl}finduser`, data).then (res => {
                    console.log(res)
                    if(res && res.errno) {

                        if(res.errno === 50 || res.errno === 4) {
                            document.getElementById('end').textContent = res.msg
                        } else if(res.errno === 101) {
                            console.log(res.errno)
                            window.location.replace(window.location.href)
                        }
                    }
                })
            })
        })
    })
}
startApi()

 */