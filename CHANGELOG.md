# Changelog

All notable changes to LoginPassKey will be documented in this file.

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
