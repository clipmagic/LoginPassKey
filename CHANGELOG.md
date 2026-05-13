# Changelog

All notable changes to LoginPassKey will be documented in this file.

## Unreleased

## 0.3.0

- Hardened passkey assertion verification by reconstructing `authenticatorData || SHA-256(clientDataJSON)` server-side instead of accepting client-supplied signed data.
- Removed browser-side `signedData` generation from login verification requests.
- Added stricter assertion origin checks, including `crossOrigin` rejection and parsed host validation.
- Added registration attestation verification with `lbuchs/webauthn`; LoginPassKey now stores the verified PEM public key returned by the WebAuthn library.
- Registration challenges are now tracked as a short-lived pending list per session, avoiding challenge overwrites when multiple registration UIs are present.
- Registration banners now skip POST renders so AppApi/404-backed API requests do not mint a fresh challenge while an API step is in progress.
- Documented the bundled `lbuchs/webauthn` dependency support boundary for ProcessWire GitHub installs.
- Added `sign_count` storage and assertion counter rollback detection, with zero-counter authenticators allowed while they continue reporting zero.
- Changed default user verification policy to `required`, exposed it in module config, and enforce the WebAuthn UV flag on login when required.
- Enforced `POST` for centralized API steps and added CSRF validation to logged-in passkey registration requests.
- Fixed username/email + passkey login so browser `allowCredentials` restrictions are preserved and credential IDs are converted to `ArrayBuffer` before `navigator.credentials.get()`.
- Updated the ProcessLoginPassKey admin list to use server-side pagination/search and avoid per-row user lookups.
- Fixed uninstall cleanup so the installed `site/templates/lpk-api.php` copy is removed only when it exists.
- Fixed ProcessLoginPassKey package metadata dependency syntax.
- Hardened database error handling in passkey lookup/list helpers so callers receive expected empty result values and errors are logged.

## 0.2.0

- Replaced automatic post-login passkey registration with an explicit registration banner for eligible users without a passkey.
- Added helper methods/hooks for checking whether a user already has a passkey and whether the current user can self-manage passkeys.
- Added admin profile and LoginRegisterPro profile self-management for users with `profile-edit`, limited to adding and deleting their own passkeys.
- Fixed chained JavaScript API steps so they derive the endpoint from the current action URL instead of requiring a global `apiUrl`.

## 0.1.0

- Added optional passkey-only login using WebAuthn discoverable credentials.
- Added `discover-start` and `discover-verify` API steps.
- Added `lpk.discover(apiUrl)` JavaScript flow.
- Added **Enable passkey-only login** module configuration.
- New passkey registrations request discoverable credentials when passkey-only login is enabled.
- Added passkey-only button to the standalone page example and LoginRegisterPro hook example.
- Updated AppApi example routes for passkey-only login.
- Added upgrade handling to ensure the installed `lpk-api` template has URL segments enabled.
- Updated README for the three supported login methods and TFA compatibility note.

## 0.0.4Beta

- Centralized API endpoint step handling in `LoginPassKey::handleApiStep()`.
- Updated default `lpk-api`, API template example, and AppApi example to use the centralized handler.
- Fixed admin login regression where existing passkey users could be sent to registration.
- Fixed frontend fetch login success handling to return a `goto` target instead of server-side redirecting during JSON requests.
- Updated standalone frontend page example.
- Updated LoginRegisterPro hook example.
- Removed obsolete typo-named LoginRegisterPro example file.
- Bumped bundled module versions to `0.0.4Beta`.

## 0.0.3Beta

- Previous beta baseline.
