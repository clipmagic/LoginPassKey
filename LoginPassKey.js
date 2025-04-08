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
                console.log(fwd)
                let pk = fwd.data.pk

                pk.publicKey.user.id  = encoder.encode(pk.publicKey.user.id ).buffer;
                pk.publicKey.challenge  = hex2bin(pk.publicKey.challenge)
                if(pk.publicKey.excludeCredentials.length > 0) {
                    pk.publicKey.excludeCredentials.forEach(c => {
                        c.id = hex2bin(c.id)
                        return c
                    });
                }
                //create credentials
                try {
                    cred = await navigator.credentials.create(pk);
               } catch (err) {
                   if (err instanceof DOMException) {
                       console.log('Create action cancelled')
                   }
               }
                if(cred) {
                    // Credentials created
                    authenticatorAttestationResponse = {
                        transports: cred.response.getTransports ? cred.response.getTransports() : null,
                        clientDataJSON: cred.response.clientDataJSON ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
                        attestationObject: cred.response.attestationObject ? arrayBufferToBase64(cred.response.attestationObject) : null,
                    }
                    data = {
                        fn: 'register',
                        next: 'end',
                        aarcreate: authenticatorAttestationResponse,
                        errno: 101
                    }
                } else {
                    data = {
                        fn: 'register',
                        next: 'end',
                        aarcreate: null,
                        errno: 2
                    }
                }
                // Progress to the next step in the process
                url.replace(urlSegment, data.next)
                urlSegment = data.next
                return await connectPost(data,url)
                    .then(
                        (res) => {
                            if(res.end) return res
                            return lpk.action(`${apiUrl}${data.next}`,res)
                        }
                    )
                break;

            case 'verify':
                let va = fwd.data.verifyArgs

                va.publicKey.challenge  = hex2bin(va.publicKey.challenge);
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
                    //create object for transmission to server
                    authenticatorAttestationResponse = {
                        id: cred.rawId ? arrayBufferToBase64(cred.rawId) : null,
                        clientDataJSON: cred.response.clientDataJSON ? arrayBufferToBase64(cred.response.clientDataJSON) : null,
                        authenticatorData: cred.response.authenticatorData ? arrayBufferToBase64(cred.response.authenticatorData) : null,
                        signature: cred.response.signature ? arrayBufferToBase64(cred.response.signature) : null,
                        userHandle: cred.response.userHandle ? arrayBufferToBase64(cred.response.userHandle) : null
                    }
                    data = {
                        fn: "verify",
                        next: "end",
                        aarverify: authenticatorAttestationResponse,
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
                               if(res.end) return res
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

function hex2bin(hex) {
    var length = hex.length / 2;
    var result = new Uint8Array(length);
    for (var i = 0; i < length; ++i) {
        result[i] = parseInt(hex.slice(i * 2, i * 2 + 2), 16);
    }
    return result;
}
