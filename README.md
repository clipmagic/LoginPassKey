

<a id="loginpasskey-for-processwire"></a>
# LoginPassKey for ProcessWire
This module enables users to log in to ProcessWire with a WebAuthn passkey as an alternative to a password.

## Table of Contents

   * [Passwords vs PassKeys](#passwords-vs-passkeys)
   * [Features](#features)
   * [How it works (Short version)](#how-it-works-short-version)
   * [How it works (Long version TLDR;)](#how-it-works-long-version-tldr)
   * [Login entry points](#login-entry-points)
   * [Installation](#installation)
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
- Can be used to log in across multiple, disparate devices.

**Passkeys**

- A more secure alternative based on public-key cryptography.
- Uses a private key on the user’s device and a public key on the service’s server.
- **Eliminates the need to remember passwords**, relying on biometric authentication or PINs.
- More resistant to attacks like phishing and keylogging.
- Tied to the user's device, or when used with a passkey manager such as Apple Keychain, can be used on multiple devices using fingerprint, face ID or device PIN.

In short, passkeys offer improved security and convenience compared to traditional passwords.

<a id="features"></a>
## Features
- Can be enabled for Frontend users only, Admin users only, or both
- Simply install, then configure the module and it is good to go
- The module does not integrate with ProcessWire TFA; if you enforce TFA, test carefully because passkey login uses WebAuthn verification as its own login path
- Supports username/email + passkey and optional passkey-only login with discoverable credentials
- The module does not make any changes to the user template

<a id="how-it-works-short-version"></a>
## How it works (Short version)
LoginPassKey supports three login methods on the same site:

1. username/email + password
2. username/email + passkey
3. passkey only, when enabled and using discoverable credentials

The browser, server and device have a 3-way conversation answering the following questions, then respond appropriately:

1. Who are you, or which discoverable credential did you present?
2. What do I do next?

The user must be **logged in with a password to register a passkey**.

The user must be **logged out to verify an existing passkey**.
<a id="how-it-works-long-version-tldr"></a>
## How it works (Long version TLDR;)
For username/email + passkey login, the user clicks a button, then:

1. The button click triggers an API call to the server identifying the user by the input value of the username field.
2. The server then runs through a series of scenarios regarding the submitted username:
   1. Username field is empty
   2. User is not logged in but device does support WebAuthn
   3. User is not logged in, device supports WebAuthn but username not found
   4. User is logged in but device does not support WebAuthn
   5. User is logged in, device supports WebAuthn but user does not yet have a stored passkey credential
   6. User roles do not match supported roles
   7. User roles match supported roles, user is not logged in and has a stored passkey credential
   8. User is logged in but their username does not match username input
   9. User logged in with passkey
   10. The user is an admin who wants to add/remove passkeys

The match to these scenarios triggers one of three possible actions:
1. Back out now
2. Go through the passkey registration process
3. Verify the passkey and log in the user

For passkey-only login, the button click starts the `discover-start` step. The server creates a challenge without `allowCredentials`, the browser prompts for a discoverable credential, and `discover-verify` verifies the returned credential, user handle, challenge and signature before logging in the matching ProcessWire user.

<a id="verification"></a>
## Verification
After a user enters their passkey (eg, fingerprint) to login, the following are checked:
- the *Challenge*
- the *Client data*
- the *Public key*
- the *Signature* returned by the device. This is a much tougher nut to crack than a salted password.
- the *User id* or discoverable credential user handle

Should any of the above tests fail, the user is denied access and to continue the login process, must enter their password.

<a id="login-entry-points"></a>
## Login entry points
LoginPassKey separates the browser flow from the endpoint that handles each step. The browser-side JavaScript starts at the configured API endpoint and moves through the same `start`, `finduser`, `register`, `verify` and `end` steps regardless of where the username/email + passkey login button appears.

When passkey-only login is enabled, the JavaScript uses the additional `discover-start` and `discover-verify` steps for WebAuthn discoverable credentials. Existing username/email + passkey login continues to use the original steps.

Supported entry points:

1. **ProcessWire admin login** - enabled with **Enable Admin Passkey login**. The module adds the passkey button to the default admin login form and uses the configured API endpoint.
2. **Default `lpk-api` page/template** - installed by the module. The template file is copied from `site/modules/LoginPassKey/lpk-api.php` to `site/templates/lpk-api.php`. If you customise the installed template, keep the module copy in sync before distributing or reinstalling.
3. **AppApi endpoint** - optional alternate endpoint. See `examples/LoginPassKeyAppApi.php`. It should expose the same step names and call the same LoginPassKey methods as the default `lpk-api` endpoint.
4. **LoginRegisterPro** - optional frontend integration. It requires a `site/ready.php` hook to add passkey buttons and supporting JavaScript to the LoginRegisterPro form. The hook still starts the same LoginPassKey JavaScript flow and uses the configured API endpoint.

All endpoint implementations must preserve the same server-side security checks. In particular, the verification challenge is created by LoginPassKey, stored in the user's session, and consumed during the `verify` or `discover-verify` step.

<a id="installation"></a>
## Installation
During the installation process, the module creates:
- The API template. The default name is `lpk-api` that includes attributes such as:
  - one page only, 
  - no children,
  - URL segments enabled for the API steps `start`, `finduser`, `register`, `verify`, `discover-start`, `discover-verify` and `end`,
  - JSON response content type, and
  - disables appending of `_main.php`.
- A publicly accessible page which is assigned the `lpk-api` template and is `hidden`.
- It is then up to you to create a login page. See `examples/loginpasskey-page-tpl.php` for inspiration.
- An admin page under `Access` to view a list of users who have passkeys and delete passkeys depending on user permissions.

<a id="configuration"></a>
## Configuration
The module configuration fields are:
<a id="activate-module"></a>
### Activate module
- **Enable Frontend Passkey login** - check to allow frontend users to login with a passkey.
- **Enable Admin Passkey login** - check to allow admins to login with a passkey.
- **Enable passkey-only login** - check to allow discoverable credential login without entering a username/email. When enabled, new passkey registrations request discoverable credentials. Existing username/email + passkey logins continue to work.

<a id="authentication-options"></a>
### Authentication options
- **Application name** - the shortname that some passkey authenticators will display to enable users to differentiate between passkeys.
- **Host name** - The Replying Party host name. Default is the current `$config->host`.

<a id="processwire-user-info"></a>
### ProcessWire user info
- **User template** (required) - defaults to the system `user` template. After saving, any other user templates will display.
- **Identify user by username or email** (required) - defaults to the user template name field. After saving and the user template has email fields, those fields will become available. When choosing an email field, the user may login with their passkey with either their username OR email address in the input field.
- **User roles permitted to use WebAuthn** (required) - Select all roles for all users. Superuser role MUST be selected to enable Superusers to log in with a passkey. The permission is not set by default.
- **Path to your API ENDPOINT** (required) - The module will create a template and page for the API by default. Change this path should you prefer another endpoint.
- **Page to redirect to on login (Frontend only)** - enter the ID or path to the page the user should be directed on frontend login. When blank, the user will be shown the `Home` page.

<a id="customising-the-frontend"></a>
## Customising the frontend

**Frontend page template** - see `loginpasskey-page-tpl.php` in the `examples` folder. The LoginPassKey JavaScript must be present but the layout can be whatever you choose. Button IDs must match the JavaScript selectors.

**LoginPassKey with LoginRegisterPro** - Requires a hook in `site/ready.php`; see `examples/loginpasskey-for-loginregisterpro-hook.php`. The hook adds username/email + passkey and passkey-only buttons to the LoginRegisterPro login form, defines the configured API endpoint for the JavaScript, and can optionally auto-trigger passkey registration for an already logged-in frontend user. Button IDs must match the JavaScript selectors. The included markup and script are only an example and may be replaced with your own frontend code.

**LoginPassKey with AppApi** - See `LoginPassKeyAppApi` in the `examples` folder. Copy this file to your AppApi `api` directory, update your `Routes.php` (instructions in example) and change the LoginPassKey API ENDPOINT in this module configuration.

**API template** - see `loginpasskey-api-tpl.php` in the `examples` folder. Keep the endpoint step names and JSON response contract intact if you customise it.

<a id="loginpasskey-in-admin"></a>
## LoginPassKey in Admin
When logged into the admin area, a user who is a superuser or has the `passkeys` permission, the page `PassKeys` appears as a child page of `Access`.

Users with this permission can view and/or delete existing passkeys.  The list only shows `id`, `user id`, `username` and date `created`. It does not display any passkey authentication data.

<a id="important"></a>
## IMPORTANT
1. LoginPassKey uses technology available in all the big 4 browsers - Chrome, Edge, Firefox and Safari - in versions later than 2023. Older browsers may struggle.
2. Do NOT enable TracyDebugger DebugBar for the frontend when testing. The frontend javascripts will conflict. Instead, see https://processwire.com/talk/topic/31110-tracydebugger-session-challenge-conflict/#comment-248134 on how to set up TracyDebugger to monitor frontend guest https requests.

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
