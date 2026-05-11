/*
 * Copyright (c) 2025.
 * Clip Magic - Prue Rowland
 * Web: www.clipmagic.com.au
 * Email: admin@clipmagic.com.au
 */

const lpkEndpointUrl = (url) => {
    const [path, query] = url.split('?')
    const normalizedPath = path.replace(/\/+$/, '')
    return `${normalizedPath}/${query ? `?${query}` : ''}`
}

const lpkStepUrl = (step) => {
    return lpkEndpointUrl(`${apiUrl.replace(/\/+$/, '')}/${step}`)
}

const lpkUrlSegment = (url) => {
    const path = url.split('?')[0].replace(/\/+$/, '')
    return path.split('/').pop()
}

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
        url = lpkEndpointUrl(url)
        let urlSegment = lpkUrlSegment(url)


        // check browser support
        if (!window.fetch || !navigator.credentials || !navigator.credentials.create) {
            const data = {
                fn: 'end',
                next: 'end',
                errno: 1
            }
             return await connectPost(data,url).then(
                 (res) => {
                     if (res && res.end) return res
                 }
             )
        }

        switch (urlSegment) {
            case 'start':
                const userFld = document.getElementById('login_name')
                // OK, start the process
                const startData = {
                    un: userFld.value.trim(),
                    fn: 'start',
                    next: 'finduser'
                }
                // Change the url to the next step in the process
                url.replace(urlSegment, startData.next)
                return await connectPost(startData,url)
                    .then(
                    (res) => {
                        if(res && res.end) return res
                        return lpk.action(lpkStepUrl(startData.next),res)
                    } )
                break;

            case 'finduser':
                if (typeof fwd === 'string') {
                    fwd = JSON.parse(fwd)
                }

                if (!fwd || !fwd.data) {
                    return {
                        end: true,
                        errno: 500,
                        msg: 'Passkey login failed before finduser returned usable data'
                    }
                }

                const findUserData = {
                    fn: 'finduser',
                    next: fwd.data.next,
                    un: fwd.data.un
                }

                // Change the url to the next step in the process
                url.replace(urlSegment, findUserData.next)
                 return await connectPost(findUserData,url)
                    .then(
                        (res) => {
                            if(res && res.end) return res
                            const next = res && res.data ? res.data.next : null
                            if(!next || next === 'end') return lpk.action(lpkStepUrl('end'), res)
                            return lpk.action(lpkStepUrl(next),res)
                        } )
               break;

            case 'register':
                if (typeof fwd === 'string') {
                    fwd = JSON.parse(fwd)
                }

                if (!fwd || !fwd.data || !fwd.data.pk) {
                    return {
                        end: true,
                        errno: 500,
                        msg: 'Passkey registration failed before register returned usable data'
                    }
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

                let registerData
                if(cred) {
                    registerData = {
                        fn: 'register',
                        next: 'end',
                        aarcreate: await cred.toJSON()
                    }
                } else {
                    registerData = {
                        fn: 'register',
                        next: 'end',
                        aarcreate: null
                    }
                }
                // Progress to the next step in the process
                url.replace(urlSegment, registerData.next)
                urlSegment = registerData.next
                return await connectPost(registerData,url)
                    .then(
                        (res) => {
                            if(res && res.end) return res
                            return lpk.action(lpkStepUrl(registerData.next),res)
                        }
                    )
                break;

            case 'verify':
                if (typeof fwd === 'string') {
                    fwd = JSON.parse(fwd)
                }

                if (!fwd || !fwd.data || !fwd.data.verifyArgs) {
                    return {
                        end: true,
                        errno: 500,
                        msg: 'Passkey verification failed before verify returned usable data'
                    }
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

                let verifyData
                if(cred) {
                    const clientDataHash = await crypto.subtle.digest("SHA-256", cred.response.clientDataJSON);
                    const signedData = new Uint8Array(cred.response.authenticatorData.byteLength + clientDataHash.byteLength);
                    signedData.set(new Uint8Array(cred.response.authenticatorData), 0);
                    signedData.set(new Uint8Array(clientDataHash), cred.response.authenticatorData.byteLength);

                    verifyData = {
                        fn: "verify",
                        next: "end",
                        aarverify: await cred.toJSON(),
                        signedData: bufferToBase64url(signedData),
                        errno: 101
                    }

                } else {
                    verifyData = {
                        fn: "verify",
                        next: "end",
                        aarverify: null,
                        errno: 4
                    }
                }

                    // Change the url to the next step in the process
                    url.replace(urlSegment, verifyData.next)
                    urlSegment = verifyData.next
                    return await connectPost(verifyData, url)
                        // Change the url to the next step in the process
                        .then(
                            (res) => {
                               if(res && res.end) return res
                                return lpk.action(lpkStepUrl(verifyData.next), res)
                            })
                break;

            case 'end':
                if(fwd) {
                    if(fwd.data) {
                        fwd = fwd.data
                    }
                    let result = {}
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

        return await lpk.action(lpkEndpointUrl(`${apiUrl.replace(/\/+$/, '')}/register`), data).then (fwd => {
            let result = {}
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
        url = lpkEndpointUrl(url)
        let connect = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: body
        });
        const contentType = connect.headers.get("Content-Type");
        const text = await connect.text();

        // If it's JSON, parse it
        if (contentType && contentType.includes("application/json")) {
            if(!text) {
                throw new Error(`Empty JSON response from ${url} (${connect.status})`);
            }
            return JSON.parse(text);
        }

        // Otherwise, log what went wrong
        console.error("Expected JSON but got:", text);
        throw new Error(`Invalid response: ${text.substring(0, 100)}...`);

    } catch (error) {
        console.error('Error fetching post request data:', error.message);
        console.log('data:', data);
        console.log('url:', url);
        return {
            end: true,
            errno: 500,
            msg: error.message
        }
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
