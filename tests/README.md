# Test instructions for EAL Training app

This folder contains two lightweight tests:

- `auth_csrf_test.php` — CLI unit-style test for CSRF helpers (`getCSRFToken`, `verifyCSRFToken`, `regenerateCSRFToken`).
- `integration/integration_test.php` — simple integration test that logs in and exercises `personnel_edit` → `personnel_save`.

Prerequisites

- PHP CLI with `curl` extension available.
- The application must be reachable from the machine running the tests.

Running the unit test (CSRF helpers)

From the repository root run:

``` bash
php tests/auth_csrf_test.php
```

Expected output: `ALL CSRF HELPER TESTS PASSED` and exit code 0.

Running the integration test

You can provide credentials and target via environment variables or arguments.

Option A — `tests/config.php` (local, not checked in):

- Copy `tests/config.php.example` → `tests/config.php` and fill these variables:
  - `$TEST_BASE_URL` (e.g. `https://inpp.ohio.edu/~leblanc/eal_2024`)
  - `$TEST_LOGIN_USER`
  - `$TEST_LOGIN_PASS`
  - `$TEST_OPERATOR_ID`

Then run:

``` bash
php tests/integration/integration_test.php
```

Option B — environment variables:

``` bash
BASE_URL=https://inpp.ohio.edu/~leblanc/eal_2024 \
LOGIN_USER=youruser LOGIN_PASS=yourpass OPERATOR_ID=123 \
php tests/integration/integration_test.php
```

Option C — positional args:

``` bash
php tests/integration/integration_test.php https://inpp.ohio.edu/~leblanc/eal_2024 user pass 123
```

Notes & security

- Prefer creating a dedicated test/training account with limited privileges for automation.
- `tests/config.php` is intended to be local and gitignored; never commit real credentials.
- The integration test performs an actual POST to `personnel_save.php` which will modify data. Use an operator ID meant for testing.

Troubleshooting

- If the integration test fails to parse CSRF tokens, confirm the app pages include the hidden `csrf_token` input and are reachable.
- For more verbose debugging, run the script under `strace`/`tcpdump` or inspect the temporary cookie file created in your system temp dir.

Cleanup

The integration test removes its temporary cookie file after completion; if it crashes, delete `eal_integration_cookies_*` in your system temp dir.

Contact

If you want me to add more endpoint checks (certification add/remove, trainer add/remove, password change), tell me which flows to include and I will add them.
