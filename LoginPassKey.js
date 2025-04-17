/*
 * Copyright (c) 2025.
 * Clip Magic - Prue Rowland
 * Web: www.clipmagic.com.au
 * Email: admin@clipmagic.com.au
 */

let lpk = {
    hello: () => {
        console.log("hello")
    },
    action: async (url, fwd= null) => {

        const encoder = new TextEncoder()
        const decoder = new TextDecoder()
        let authenticatorAttestationResponse
        let cred
        let result

        // urlSegment refers to the urlSegment/endpoint of the api
        // initated with 'start' in the page template js
        let pathArray = url.split('/');
        let urlSegment = pathArray.pop();

        // check browser support
        if (!window.fetch || !navigator.credentials || !navigator.credentials.create) {
            $data = {
                fn: 'end',
                next: 'end',
                errno: 1
            }
             return await connectPost(data,url).then(
                 (res) => {
                     if (res.end) return res

                 }
             )
        }

        switch (urlSegment) {
            case 'start':
                const userFld = document.getElementById('login_name')
                // OK, start the process
                data = {
                    un: userFld.value.trim(),
                    fn: 'start',
                    next: 'finduser'
                }
                // Change the url to the next step in the process
                url.replace(urlSegment, data.next)
                return await connectPost(data,url)
                    .then(
                    (res) => {
                        if(res && res.end) return res
                        return lpk.action(`${apiUrl}${data.next}`,res)
                    } )
                break;

            case 'finduser':
                data = {
                    fn: 'finduser',
                    next: fwd.data.next,
                    un: fwd.data.un
                }

                // Change the url to the next step in the process
                url.replace(urlSegment, data.next)
                 return await connectPost(data,url)
                    .then(
                        (res) => {
                            if(res.end) return res
                            return lpk.action(`${apiUrl}${data.next}`,res)
                        } )
               break;

            case 'register':
                if (typeof fwd === 'string') {
                    fwd = JSON.parse(fwd)
                }

                let pk = fwd.data.pk

                pk.publicKey.user.id  = encoder.encode(pk.publicKey.user.id ).buffer;
                pk.publicKey.challenge  = encoder.encode(pk.publicKey.challenge).buffer

                if(pk.publicKey.excludeCredentials.length > 0) {
                    pk.publicKey.excludeCredentials.forEach(c => {
                        c.id = encoder.encode(c.id).buffer
                        return c
                    });
                }

                //create credentials
                try {
                    cred = await navigator.credentials.create(pk)
                } catch (err) {
                   if (err instanceof DOMException) {
                       console.log('Create action cancelled')
                   }
               }

                if(cred) {
                    data = {
                        fn: 'register',
                        next: 'end',
                        aarcreate: await cred.toJSON()
                    }

                } else {
                    data = {
                        fn: 'register',
                        next: 'end',
                        aarcreate: null
                    }
                }
                // Progress to the next step in the process
                url.replace(urlSegment, data.next)
                urlSegment = data.next
                return await connectPost(data,url)
                    .then(
                        (res) => {
                            if(res && res.end) return res
                            return lpk.action(`${apiUrl}${data.next}`,res)
                        }
                    )
                break;

            case 'verify':
                let va = fwd.data.verifyArgs
                va.publicKey.challenge  = encoder.encode(va.publicKey.challenge).buffer;
                va.publicKey.allowCredentials = []


                //get credentials
                try {
                    cred = await navigator.credentials.get(va);
                } catch (err) {
                    if (err instanceof DOMException) {
                        console.log('Verfication action cancelled')
                    }
                }

                if(cred) {
                    data = {
                        fn: "verify",
                        next: "end",
                        aarverify: await cred.toJSON(),
                        challenge:  credToJSON(va.publicKey.challenge),
                        errno: 101
                    }

                } else {
                    data = {
                        fn: "verify",
                        next: "end",
                        aarverify: null,
                        errno: 4
                    }
                }

                    // Change the url to the next step in the process
                    url.replace(urlSegment, data.next)
                    urlSegment = data.next
                    return await connectPost(data, url)
                        // Change the url to the next step in the process
                        .then(
                            (res) => {
                               if(res && res.end) return res
                                return lpk.action(`${apiUrl}${data.next}`, res)
                            })
                break;

            case 'end':
                if(fwd) {

                    // TODO resolve why register and verify return different objects in end
                    result = {}
                    result.end = true
                    if(fwd && fwd.msg)
                        result.msg = fwd.msg
                    if(fwd && fwd.error)
                        result.error = fwd.error
                    if(fwd && fwd.un)
                        result.un = fwd.un
                    if(fwd && fwd.errno)
                        result.errno = fwd.errno

                    if(fwd && fwd.data && fwd.data.msg)
                        result.msg = fwd.data.msg
                    if(fwd && fwd.data && fwd.data.error)
                        result.error = fwd.data.error
                    if(fwd && fwd.data && fwd.data.un)
                        result.un = fwd.data.un
                    if(fwd && fwd.data && fwd.data.errno)
                        result.errno = fwd.data.errno
                    return result
                }
                break;

            default:
                break;
        }
    },

    // used when user logged in to create a new passkey
    registerOnly: async (apiUrl, data) => {
        data.fn = 'register'
        data.end = 'end'
        return await lpk.action(`${apiUrl}register`, data).then (res => {
        })
    }
}

const connectPost = async (data, url) => {
    let body = JSON.stringify(data)

    try {
        let connect = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: body
        })
        return await connect.json()

    } catch (error) {
        console.log('Error fetching post request data:', `${error.message} in ${body}`);
        console.log('data:', data);
        console.log('url:', url);
    }
}


function recursiveBase64StrToArrayBuffer(obj) {
    let prefix = '=?BINARY?B?';
    let suffix = '?=';
    if (typeof obj === 'object') {
        for (let key in obj) {
            if (typeof obj[key] === 'string') {
                let str = obj[key];
                if (str.substring(0, prefix.length) === prefix && str.substring(str.length - suffix.length) === suffix) {
                    str = str.substring(prefix.length, str.length - suffix.length);

                    let binary_string = window.atob(str);
                    let len = binary_string.length;
                    let bytes = new Uint8Array(len);
                    for (let i = 0; i < len; i++)        {
                        bytes[i] = binary_string.charCodeAt(i);
                    }
                    obj[key] = bytes.buffer;
                }
            } else {
                recursiveBase64StrToArrayBuffer(obj[key]);
            }
        }
    }
}


/**
 * Convert a ArrayBuffer to Base64
 * @param {ArrayBuffer} buffer
 * @returns {String}
 */
function arrayBufferToBase64(buffer) {
    let binary = '';
    let bytes = new Uint8Array(buffer);
    let len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode( bytes[ i ] );
    }
    return window.btoa(binary);
}

function credToJSON(item) {
    return JSON.parse(JSON.stringify(arrayBufferToBase64(item)));
}

function hex2bin(hex) {
    var length = hex.length / 2;
    var result = new Uint8Array(length);
    for (var i = 0; i < length; ++i) {
        result[i] = parseInt(hex.slice(i * 2, i * 2 + 2), 16);
    }
    return result;
}
