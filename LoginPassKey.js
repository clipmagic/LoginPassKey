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
                if (typeof fwd === 'string') {
                    fwd = JSON.parse(fwd)
                }

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
                if (typeof fwd === 'string') {
                    fwd = JSON.parse(fwd)
                }

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
                    const clientDataHash = await crypto.subtle.digest("SHA-256", cred.response.clientDataJSON);
                    const signedData = new Uint8Array(cred.response.authenticatorData.byteLength + clientDataHash.byteLength);
                    signedData.set(new Uint8Array(cred.response.authenticatorData), 0);
                    signedData.set(new Uint8Array(clientDataHash), cred.response.authenticatorData.byteLength);

                    data = {
                        fn: "verify",
                        next: "end",
                        aarverify: await cred.toJSON(),
                        challenge:  credToJSON(va.publicKey.challenge),
                        signedData: bufferToBase64url(signedData),
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
                    result = {}
                    result.end = true
                    if(fwd && fwd.msg)
                        result.msg = fwd.msg
                    if(fwd && fwd.error)
                        result.error = fwd.error
                    if(fwd && fwd.un)
                        result.un = fwd.un
                    if(fwd && fwd.errno) {
                        result.errno = fwd.errno
                        if(fwd.errno === 101 && fwd.goto) {
                            window.location.href = fwd.goto
                        }
                    }
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

        await lpk.action("`${apiUrl}register`", data).then (fwd => {
            result = {}
            result.end = true

            if(fwd) {
                if (fwd.msg)
                    result.msg = fwd.msg
                if (fwd.error)
                    result.error = fwd.error
                if (fwd.un)
                    result.un = fwd.un
                if (fwd.errno) {
                    result.errno = fwd.errno
                }

            }
            console.log(result)
            return result
        })
    }
}

/*

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

*/


const connectPost = async (data, url) => {
    let body = JSON.stringify(data);
    try {
        let connect = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: body
        });
        const contentType = connect.headers.get("Content-Type");

        // If it's JSON, parse it
        if (contentType && contentType.includes("application/json")) {
            return await connect.json();
        }

        // Otherwise, log what went wrong
        const text = await connect.text();
        console.error("Expected JSON but got:", text);
        throw new Error(`Invalid response: ${text.substring(0, 100)}...`);

    } catch (error) {
        console.error('Error fetching post request data:', error.message);
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

function bufferToBase64url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary)
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');
}

