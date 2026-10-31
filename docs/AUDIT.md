# Audit record

One audit file for this project. Every audit, check or review adds a dated section at the top; nothing is rewritten. Open work lives in `.agent/CURRENT-TASK.md`. Older audit reports: `../reports/mimmycare/`.

## 30 October 2026: SSH release, PHP API, Clerk sign-in (local commits, not pushed, not deployed)

Scope: set the project up like EGO LIMITED: push-to-deploy over SSH (kit `scripts/release.mjs`), a PHP 8.4 API on MariaDB with Clerk session-token verification, Clerk sign-in in the app, settings outside the web root. Infrastructure only; there is no product spec for app features yet.

### Server state before (read-only `ssh cpanel` and HTTPS, 30 Oct 2026)

- `~/mimmycare.com/api.php`: unauthenticated template (CORS `*`, reads/inserts a missing `items` table). `https://mimmycare.com/api.php` answers **500**.
- `~/mimmycare.com/config.php` (0664) in the web root: database settings as `$host`, `$dbport`, `$dbname`, `$dbuser`, `$dbpass`, opens a mysqli connection. `https://mimmycare.com/config.php` answers 200 (runs, prints nothing). Values not read.
- `https://mimmycare.com/.ftp-deploy-sync-state.json` (old FTPS action state, lists deployed files) answers **200**: publicly readable. `~/mimmycare.com/mimmycare.com/` is a stray nested copy. Both left for the owner.
- No `~/mimmycare.com/api/` folder; `/api/health` answers 200 with the landing page (root SPA fallback). Database `devgeereact_mimmycare` exists, empty (per owner).
- Server: PHP 8.4.27 CLI with `pdo_mysql`, `mbstring`, `openssl`; MariaDB client 11.4.13. Root `.htaccess`: Cloudflare origin lock (403 without `CF-RAY`) + SPA fallback.
- EGO LIMITED for comparison: `/api/health` → `{"ok":true,"service":"ego-api","db":true}`. No Content-Security-Policy is set by EGO LIMITED's repo, `.htaccess` files or live headers; MimmyCare's app `.htaccess` adds one (Clerk FAPI, img.clerk.com, challenges.cloudflare.com).

### Evidence (this Mac, PHP 8.5.12, Node 24.22.1)

| Check | Result |
|---|---|
| `cd app && npx --no-install tsc -b && npm test` | PASS: 11 Vitest tests (`app/src/lib/api.test.ts`) |
| `cd landing && npx --no-install tsc -b` | PASS |
| `php -l` on every `api/**/*.php` | PASS |
| `php api/tests/run.php` | PASS: 71 passed, 0 failed (JWT signature/iss/exp/nbf/sub/azp/alg/kid, JWKS cache, fail-closed when JWKS unreachable, claims parsing, routes, bearer, client IP, settings, migrations, `.htaccess` guards, `release.json` excludes) |
| `release.json` build (landing `--base=/`, app `--base=/app/` with `pk_live_…`, `.htaccess` grep) | PASS: `app/dist/.htaccess` holds `RewriteRule . /app/index.html`; bundle carries the live publishable key; no development key in `dist/` |
| Throwaway MariaDB 11.4.13 in `.local-test/` (runtime user with SELECT/INSERT/UPDATE/DELETE only, separate migration user) | PASS: `migrate.php --list` / apply / re-run idempotent; tables `users`, `rate_hits`, `schema_migrations` |
| `php -S` + that database, test key seeded into the JWKS cache | PASS: `/api/health` 200 `db:true`; `/api/` 200; `POST /api/health` 405; `/api/nope` 404; `/api/me` without token 401, garbage token 401, wrong `azp` 401; valid token 200 creates the user (email lower-cased), second token with `name` claim updates it, `last_seen_at` set; 18 failed tokens then **429** |
| Missing settings | PASS: `/api/health` 503 `{"ok":false,...,"db":false}`, `/api/me` 503 `not_configured` |
| Server script `~/Backups/scripts/mimmycare-server-setup.zsh` | `zsh -n` and `bash -n` (remote part) PASS; its old-config parser run locally on a synthetic file (quotes and `$` in the password): PASS. **Script not run** (preview or apply). |

### NOT TESTED

- Anything on the live server: the server script, the first SSH release, smoke checks, `.htaccess` behaviour on LiteSpeed (subfolder rules replacing the root's, the repeated origin lock, `<Files>` deny/grant, the CSP header), the PDO connection with the real credentials, JWKS fetch from `https://clerk.mimmycare.com`.
- Real Clerk sign-in in a browser (development or production instance), the CSP with Clerk's real scripts and Turnstile, Google sign-in (not enabled on the instance per the coordinator), the `name` session claim (not configured; the app falls back to Clerk's profile name).
- The GitHub Actions deploy run (secrets not set yet; all commits are `[skip ci]`).
- `node scripts/release.mjs` preflight (fails by design: `main` ahead of `origin/main`); its checks and build were run by hand instead (table above).
- Accessibility: keyboard and focus styles written for WCAG 2.2 AA; not tested with a screen reader.

### Decisions and deviations

- Error shape follows the kit (`{"error":{"code","message"}}`), not EGO LIMITED's `{"error":"..."}`.
- PDO (kit rule) instead of EGO LIMITED's mysqli; `pdo_mysql` is on the server.
- The JWT verifier is EGO LIMITED's own zero-dependency code (owner's instruction), not a maintained library as the kit rule says. Covered by the 20+ token tests.
- `release.json` api part also allows `.htaccess` (beyond the brief), otherwise `api/.htaccess` would never reach the server and the `.sql` files it allows would be web-readable.
- The rate-limit table is a migration (`002_rate_hits.sql`) instead of EGO LIMITED's lazy `CREATE TABLE`, so the runtime user needs no schema rights.

## 31 Oct 2026: deploy on push

- Live 31 Oct 2026: /api/health 200 db:true, /api/me 401 JSON without a token, /app/ serves the Clerk sign-in with the CSP header, old api.php and config.php retired to ~/backups/mimmycare/, migrations 001 and 002 applied (backup db-before-migrations-20261031T000253Z.sql). FTP login, its 3 GitHub secrets and the FTP leftovers removed (leftovers kept in ~/backups/mimmycare/ftp-leftovers-*). Clerk production verified (DNS, SSL, mail), Google with the owner's keys, session claims email and name.
- Incident: the first release left out api/lib/config.php (the release script never uploads a file named config.php), health answered 500 and the release restored the old files. Fixed in ce6800b (renamed to lib/settings.php) plus a release check that refuses any file named config.php; migrations had not run because the setup script ran before the API existed, applied by the owner afterwards.
- Repo made public 31 Oct 2026 after a full-history gitleaks scan (0 findings); Actions on; GitHub deploy run 41149017652 passed (nothing to upload).
- Real sign-in in a browser: NOT TESTED yet (owner to try email code and Google at /app/).
