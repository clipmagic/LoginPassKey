

<a id="loginpasskey-for-processwire"></a>
# LoginPassKey for ProcessWire
This module enables users to log in to ProcessWire with a WebAuthn passkey rather than a password.

## Table of Contents

   * [Passwords vs PassKeys](#passwords-vs-passkeys)
   * [Features](#features)
   * [How it works (Short version)](#how-it-works-short-version)
   * [How it works (Long version TLDR;)](#how-it-works-long-version-tldr)
   * [Installation ](#installation)
   * [Configuration](#configuration)
      + [Activate module](#activate-module)
      + [Authentication options](#authentication-options)
      + [ProcessWire user info](#processwire-user-info)
   * [Customising the frontend](#customising-the-frontend)
   * [LoginPassKey in Admin](#loginpasskey-in-admin)
   * [Credit where it's due](#credit-where-its-due)
   * [Like this module?](#like-this-module)

<a id="passwords-vs-passkeys"></a>
## Passwords vs PassKeys
**Passwords**:

- Traditional security method using a combination of letters, numbers, and symbols.
- Vulnerable to hacking, phishing, and weak practices like reuse.
- Require users to remember and manage, which can lead to insecure behavior.
- Can be used to log in across multiple, disparate devices

**Passkeys**

- A more secure alternative based on public-key cryptography.
- Uses a private key on the user’s device and a public key on the service’s server.
- **Eliminates the need to remember passwords**, relying on biometric authentication or PINs.
- More resistant to attacks like phishing and keylogging.
- Tied to the user's device OR when used in conjunction with a federated passkey manager, eg Apple's Keychain, can be used to log into multiple devices using biometric fingerprint or face id

In short, passkeys offer improved security and convenience compared to traditional passwords.

<a id="features"></a>
## Features
- Can be enabled for Frontend users only, Admin users only, or both
- Simply install, then configure the module and it's good to go
- The module does not require TFA and will probably conflict if TFA is installed
- The module does not make any changes to the user template

<a id="how-it-works-short-version"></a>
## How it works (Short version)
The browser, server and device have a 3-way conversation answering the following two questions, then respond appropriately:

1. Who are you?
2. What do I do next?

The user must be **logged in with a password to register a passkey**.

The user must be **logged out to verify an existing passkey**.
<a id="how-it-works-long-version-tldr"></a>
## How it works (Long version TLDR;)
The user clicks a button, then:

1. The button click triggers an api call to the server identifying the user by the input value of the username field.
2. The server then runs through a series of scenarios regarding the submitted username:
   1. Username field is empty
   2. User is not logged in but device does support WebAuthn
   3. User is not logged in, device supports WebAuthn but username not found
   4. User is logged in but device does not support WebAuthn
   5. User is logged, device does support WebAuthn but user does not have the WebAuthn challenge set
   6. User roles do not match supported roles
   7. User roles match supported roles, user is not logged in and has WebAuthn challenge set
   8. User is logged in but their username does not match username input
   9. User logged in with passkey
   10. The user is an admin who wants to add/remove passkeys

The match to these scenarios triggers one of three possible actions:
1. Back out now
2. Go through the passkey registration process
3. Verify the passkey and log in the user

<a id="verification"></a>
## Verification
After a user enters their passkey (eg, fingerprint) to login, the following are checked:
- the *Challenge*
- the *Client data*
- the *Public key*
- the *Signature* returned by the device. This is a much tougher nut to crack than a salted password.
- the *User id*

Should any of the above tests fail, the user is denied access and to continue the login process, must enter their password.

<a id="installation"></a>
## Installation
During the installation process, the module creates:
- The Api template. The default name is `lkp-api` that includes attributes such as:
  - one page only, 
  - no children,
  - urlSegments `start`, `register`, `verify` and `end`,
  - content type of `application-json`, and
  - disables appending of `_main.php`.
- A publicly accessible page which is assigned the `lkp-api` template and is `hidden`.
- It is then up to you to create a login page. See `examples/loginpasskey-page-tpl.php` for inspiration.
- An admin page under `Access` to view a list of users who have passkeys and delete passkeys depending on user permissions.

<a id="configuration"></a>
## Configuration
The module configuration fields are:
<a id="activate-module"></a>
### Activate module
- **Enable Frontend Passkey login** - check to allow frontend users to login with a passkey.
- **Enable Admin Passkey login** - check to allow admins to login with a passkey.

<a id="authentication-options"></a>
### Authentication options
- **Application name** - the shortname that some passkey authenticators will display to enable users to differentiate between passkeys.
- **Host name** - The Replying Party host name. Default is the current `$config->host`.

<a id="processwire-user-info"></a>
### ProcessWire user info
- **User template** (required) - defaults to the system `user` template. After saving, any other user templates will display.
- **Identify user by username or email** (required) - defaults to the user template name field. After saving and the user template has email fields, those fields will become available. When choosing an email field, the user may login with their passkey with either their username OR email address in the input field.
- **User roles permitted to use WebAuthn** (required) - Select all roles for all users. Superuser role MUST be selected to enable Superusers to log in with a passkey. The permission is not set by default.
- **Path to your API ENDPOINT** (required) - The module will create a template and page for the api by default. Change this path should you prefer another endpoint.
- **Page to redirect to on login (Frontend only)** - enter the ID or path to the page the user should be directed on frontend login. When blank, the user will be shown the `Home` page.

<a id="customising-the-frontend"></a>
## Customising the frontend

**Frontend page template** - see `loginpasskey-page-tpl.php` in the `examples` folder. The script MUST be present but the layout can be whatever you choose. The id attribute of the button MUST match the `getElementById` selector.

**LoginPassKey with LoginRegisterPro** - Requires a hook in `site\ready.php` see `loginpasskey-for-loginregisterpro-hook` in the `examples` folder. The script MUST be present but the layout can be whatever you choose. The id attribute of the button MUST match the `getElementById` selector. The CSS and associated script to trigger the transitions, are in the second `Page::render` hook. You can remove this hook and add the styles and script to your own files.

**LoginPassKey with AppApi** - See `LoginPassKeyAppApi` in the `examples` folder. Copy this file to your AppApi `api` directory, update your `Routes.php` (instructions in example) and change the LoginPassKey API ENDPOINT in this module configuration.

**Api template** - see `loginpasskey-api-tpl.php` in the `examples` folder. **Changing this template will almost certainly break the application and is unsupported!**

<a id="loginpasskey-in-admin"></a>
## LoginPassKey in Admin
When logged into the admin area, a user who is a superuser or has the `passkeys` permission, the page `PassKeys` appears as a child page of `Access`.

Users with this permission can view and/or delete existing passkeys.  The list only shows `id`, `user id`, `username` and date `created`. It does not display any passkey authentication data.

<a id="important"></a>
## IMPORTANT
1. LoginPassKey uses technology available in all the big 4 browsers - Chrome, Edge, Firefox and Safari - in versions later than 2023. Older browsers may struggle.
2. Do NOT enable TracyDebugger DebugBar for the frontend when testing. The frontend javascripts will conflict. Instead, see https://processwire.com/talk/topic/31110-tracydebugger-session-challenge-conflict/#comment-248134 on how to set up TracyDebugger to monitor frontend guest https requests.
3. The test page will fail if you have LoginRegisterPro installed along with it's associated `site/ready.php` hook. Simply comment out the hook in `site/ready.php` and try the test page again. You won't be logged in, it's simply a proof of concept.

<a id="credit-where-its-due"></a>
## Credit where it's due
This module would not be possible without the help and support from:

- Ryan Cramer for ProcessWire and his comprehensive ProcessWire docs
- The ProcessWire Community Forum, with special mentions to:
   - Adrian for his TracyDebugger module and quick replies when I reached out for help
   - Bernhard for his knowledge and who is always willing to help
   - All forum members who posted solutions to problems I faced creating this module

<a id="like-this-module"></a>
## Like this module?
Please show your appreciation by sending Clip Magic some financial love via PayPal.

<a href="https://www.paypal.com/donate/?hosted_button_id=FELNM24L4NM5N">Click to contribute to Clip Magic</a>