# MimmyCare API

PHP 8.4 (server: premium17, MariaDB 11.4), PDO, no Composer. Source: `api/`. Live at `https://mimmycare.com/api/`, deployed by `release.json` (see `docs/DEPLOYMENT.md`). Structure copied from EGO LIMITED's API.

## Endpoints

| Method and path | Auth | Answer |
|---|---|---|
| `GET /api/health` (also `GET /api/`) | public | `200 {"ok":true,"service":"mimmycare-api","db":true}`; `503` with `"ok":false,"db":false` when the settings or the database are unavailable |
| `GET /api/me` | Clerk session token, `Authorization: Bearer <token>` | `200 {"user":{"id","clerk_user_id","email","name","created_at","last_seen_at"}}`. Creates the user on first sign-in, refreshes email, name and `last_seen_at` afterwards |

Errors: `{"error":{"code":"...","message":"..."}}`; 500s add `"ref"`, which matches a `[mimmycare <ref>]` line in `~/mimmycare.com/api/error_log`. Codes: `unauthorized` 401, `rate_limited` 429, `not_found` 404, `method_not_allowed` 405 (with `Allow`), `not_configured` / `database_unavailable` 503, `server_error` 500.

Rate limits (`rate_hits` table): 20 failed token checks per IP per 10 minutes, then 429; 60 `/me` calls per user per minute. The client IP comes from `CF-Connecting-IP` only when the request arrives from a Cloudflare edge range.

## Sign-in (Clerk)

`lib/clerk_jwt.php` (`MimmyClerkJwt`, copied from EGO LIMITED): RS256 only; key from the issuer's JWKS (`<issuer>/.well-known/jwks.json`, cached an hour in the private cache folder, refreshed at most once a minute for an unknown `kid`); checks `iss`, `exp`, `nbf` (5 s leeway), `sub`, and `azp` against the exact list in the settings. Any failure is a 401. `lib/auth.php` keeps `sub`, `email` and `name` (or `first_name` + `last_name`) from the verified claims only; nothing about the user is read from the request body.

## Files

| File | Role |
|---|---|
| `index.php` | front controller: headers, CORS (listed origins only), routes, JSON errors |
| `lib/http.php` | JSON responses, error shape, route parsing, bearer parsing, client IP |
| `lib/settings.php` | loads `api/config.php` (or `MIMMYCARE_API_CONFIG`), validates keys without printing values |
| `lib/db.php` | PDO (utf8mb4, exceptions, real prepares, UTC session) |
| `lib/auth.php`, `lib/clerk_jwt.php` | Clerk verification, claims to profile, user upsert |
| `lib/rate_limit.php` | sliding-window counter |
| `lib/migrations.php`, `migrate.php` | migration runner (CLI only) |
| `migrations/NNN_*.sql` | schema: `001_users.sql`, `002_rate_hits.sql` |
| `tests/run.php` | 71 tests, no database needed (never deployed) |
| `config.sample.php` | settings keys, no values (never deployed) |
| `.htaccess` | routing and deny rules |

## Settings

Keys and comments: `api/config.sample.php`. On the server: `~/private-config/mimmycare/config.php` (0600) loaded by the stub `~/mimmycare.com/api/config.php`. Never commit `api/config.php` (gitignored), never upload it (release.mjs protects it).

## History

- Until 30 Oct 2026 the server had only `~/mimmycare.com/api.php`, an unauthenticated template (CORS `*`, inserts into a missing `items` table, HTTP 500) and a web-root `config.php` (0664) with the database settings as `$host/$dbport/$dbname/$dbuser/$dbpass`. Nothing called it. `~/Backups/scripts/mimmycare-server-setup.zsh` retires both into `~/backups/mimmycare/` and moves the settings to the private config.
- `ssh cpanel` works again on premium17 (read-only checks on 30 Oct 2026); the old note that only SFTP worked is out of date.
- `~/mimmycare.com/mimmycare.com/` is a stray nested copy from an old FTP deploy path, and `~/mimmycare.com/.ftp-deploy-sync-state.json` is the old FTPS action's state file (web-readable). Both are left for the owner to review.
