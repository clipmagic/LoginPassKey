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
                error: 1
            }
             return connectPost(data,url)
        }

        switch (urlSegment) {
            case 'start':
                const userFld = document.querySelector('.webauthn')
                // OK, start the process
                data = {
                    un: userFld.value,
                    fn: 'start',
                    next: 'finduser'
                }
                // Change the url to the next step in the process
                url.replace(urlSegment, data.next)
                return connectPost(data,url)
                    .then(
                    (res) => {
                        if(res.end) return res
                        return lpk.action(`${apiUrl}${data.next}`,res)
                    } )
                break;

            case 'finduser':
                console.log('fwd in finduser: ', fwd)
                data = {
                    fn: 'finduser',
                    next: fwd.data.next,
                    un: fwd.data.un
                }

                // Change the url to the next step in the process
                url.replace(urlSegment, data.next)
                 return connectPost(data,url)
                    .then(
                        (res) => {
                            if(res.end) return res
                            return lpk.action(`${apiUrl}${data.next}`,res)
                        } )
               break;

            case 'register':
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
                        aarcreate: authenticatorAttestationResponse
                    }
                } else {
                    data = {
                        fn: 'register',
                        next: 'end',
                        aarcreate: null,
                        error: 2
                    }
                }
                // Progress to the next step in the process
                url.replace(urlSegment, data.next)
                urlSegment = data.next
                return connectPost(data,url)
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
                        fn: 'verify',
                        next: 'end',
                        aarverify: authenticatorAttestationResponse
                    }

                } else {
                    data = {
                        fn: 'verify',
                        next: 'end',
                        aarverify: null,
                        error: 4
                    }
                }

                    // Change the url to the next step in the process
                    url.replace(urlSegment, data.next)
                    urlSegment = data.next
                    return connectPost(data, url)
                        // Change the url to the next step in the process
                        .then(
                            (res) => {
                               if(res.end) return res
                                return lpk.action(`${apiUrl}${data.next}`, res)
                            })
                break;

            case 'end':
                console.log('fwd in end: ', fwd)
                if(fwd) {
                    result = {}
                    result.end = true
                    if(fwd && fwd.data.msg)
                        result.msg = fwd.data.msg
                    if(fwd && fwd.data.error)
                        result.error = fwd.data.error
                    if(fwd && fwd.data.un)
                        result.un = fwd.data.un

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
