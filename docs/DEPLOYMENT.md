# Deployment

Every push to `main` deploys: `.github/workflows/deploy.yml` installs (`npm ci`), then runs `node scripts/release.mjs --go` over SSH, which runs the checks, the build, the upload and the smoke checks (secrets `CPANEL_SSH_KEY`, `CPANEL_KNOWN_HOSTS`, set once with `~/Backups/scripts/setup-github-deploy.zsh --apply mimmycare`; the workflow's `HostName premium17.web-hosting.com`, `Port 21098`, `User devgeereact` match `ssh -G cpanel`). Docs-only pushes (`docs/**`, `**/*.md`, `.agent/**`) and commits with `[skip ci]` do not deploy. The same release runs by hand from the Mac with `npm run release`. No FTP accounts.

`scripts/release.mjs` is the kit's file (`~/Developer/build-kit/App-Kit/starter/scripts/release.mjs`), unchanged. Settings: `release.json` (no secrets).

## Targets

| Target | URL | Server folder (`release.json` `to`) | Built from | Status |
|---|---|---|---|---|
| landing | https://mimmycare.com/ | `~/mimmycare.com` | `landing/dist` | PLANNED (first SSH release not run) |
| app | https://mimmycare.com/app/ | `~/mimmycare.com/app` | `app/dist` (includes `app/public/.htaccess`) | PLANNED |
| api | https://mimmycare.com/api/ | `~/mimmycare.com/api` | `api/` without `tests/`, `config.sample.php`, `*.md` | PLANNED |

Smoke checks after every upload: `/` 200, `/app/` 200, `/api/health` 200 and contains `"ok":true` (so the API must reach the database). A failure restores the files the release changed.

## Server layout

| Path | What | Who writes it |
|---|---|---|
| `~/mimmycare.com/.htaccess` | Cloudflare origin lock + landing SPA fallback | server only, never uploaded (protected) |
| `~/mimmycare.com/app/.htaccess` | app SPA fallback (`RewriteRule . /app/index.html`), CSP, origin lock | release (app part allows `.htaccess`) |
| `~/mimmycare.com/api/.htaccess` | routes to `index.php`, denies `lib/`, `migrations/`, `tests/`, `migrate.php`, config files; origin lock | release (api part allows `.htaccess`) |
| `~/mimmycare.com/api/config.php` | stub, no secrets: `return require '/home/devgeereact/private-config/mimmycare/config.php';` | server setup script, never uploaded |
| `~/private-config/mimmycare/config.php` | real settings (0600, folder 0700), keys as in `api/config.sample.php` | server setup script |
| `~/private-config/mimmycare/cache/` | cached Clerk JWKS (0700) | the API |
| `~/backups/mimmycare/` | pre-change copies, retired files, DB dumps, `releases/` snapshots | setup script, release.mjs |

Subfolder `.htaccess` files with `RewriteEngine On` replace the root's rewrite rules, so `app/` and `api/` repeat the root's Cloudflare origin lock (requests without `CF-RAY` get 403).

## First release (once)

1. `~/Backups/scripts/mimmycare-server-setup.zsh` (preview: lists what it would do, changes nothing), then `--apply`: backs up and retires the old web-root `api.php` and `config.php`, writes the private config and the API stub, dumps the database and applies the migrations, checks `/api/health`.
2. `~/Backups/scripts/setup-github-deploy.zsh --apply mimmycare` (restricted deploy key + the two GitHub secrets).
3. `npm run release` (dry run; preflight needs `main` clean and pushed), read the list, then `npm run release -- --go`. Or push a commit without `[skip ci]` and watch Actions > Deploy.
4. Check https://mimmycare.com/api/health shows `{"ok":true,"service":"mimmycare-api","db":true}`, open https://mimmycare.com/app/ and sign in.

If step 3 runs before step 1, the API answers 503 `not_configured`, the smoke check fails and the changed files are restored; the files the release added (the new `api/` folder) stay in place, harmless until step 1 runs.

## How to release

1. `npm run release`: preflight (on `main`, clean, equal to `origin/main`), `checks`, `build`, then a checksum dry run listing every file that would change. Nothing changes.
2. `npm run release -- --go`: snapshot of every server file about to change into `~/backups/mimmycare/releases/<utc>/` (newest 3 kept), upload (entry files last, nothing deleted), smoke checks, automatic restore on failure.

Checks (`release.json`): app `tsc -b` + Vitest, landing `tsc -b`, `php -l` on every API file, `php api/tests/run.php`. Build: landing `vite build --base=/`, app `vite build --base=/app/` with the live Clerk publishable key, then a check that `app/dist/.htaccess` holds the SPA fallback.

## Database changes

Numbered files in `api/migrations/` (`001_users.sql`, `002_rate_hits.sql`, ...). Applied files are never edited. After a release that adds one: take a dump (`~/backups/mimmycare/`), then over SSH `cd ~/mimmycare.com/api && php migrate.php --list` and `php migrate.php`. `migrate.php` is CLI-only and denied over HTTP.

## How to roll back

`npm run release -- --rollback` (dry run), then `npm run release -- --rollback --go`: restores the files the last release changed. `npm run release -- --list` shows the snapshots. Files a release added stay in place.

## Clerk

| Instance | Publishable key (public) | Issuer / Frontend API | Used by |
|---|---|---|---|
| production | `pk_live_Y2xlcmsubWltbXljYXJlLmNvbSQ` | `https://clerk.mimmycare.com` | `release.json` build; private config `clerk_issuer` |
| development | `pk_test_dm9jYWwtZG9lLTk4OTEuY2xlcmsuYWNjb3VudHMuZGV2JA` | `https://vocal-doe-9891.clerk.accounts.dev` | `app/.env.local` (from `app/.env.example`); a local `api/config.php` |

Sign-in: email code (password off). Session token claim `email` is set; add a `name` claim (for example `{{user.full_name}}`) in Clerk to store names in `users.name` (the app falls back to Clerk's profile name meanwhile). Authorised parties (`azp`): `https://mimmycare.com`, `http://localhost:5092`. Publishable keys are public; `.gitleaks.toml` allows them. Clerk secret keys are not used.

## Local development

- `npm ci`, then `npm run dev:landing` (5091) and `npm run dev:app` (5092, `/app/`).
- API: copy `api/config.sample.php` to `api/config.php` (gitignored) pointing at a local MariaDB 11.4 and the development Clerk issuer, `php api/migrate.php`, then `npm run dev:api` (`php -S 127.0.0.1:8041 api/index.php`). Vite proxies `/api` to it. `MIMMYCARE_API_CONFIG` may point to a config elsewhere.
- Tests: `npm test` (Vitest + `php api/tests/run.php`). Throwaway databases live in `.local-test/` (ignored).

## Cloudflare / DNS

SSL/TLS mode: **Full (strict)** (kit rule; not re-checked in this pass). SSH goes to `premium17.web-hosting.com:21098`, not the proxied domain.

## GitHub secrets and variables (names only)

| Name | Kind | Used by |
|---|---|---|
| CPANEL_SSH_KEY | secret | deploy.yml (restricted key `github-deploy-mimmycare` on the server) |
| CPANEL_KNOWN_HOSTS | secret | deploy.yml |

The old `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` secrets and the cPanel FTP account belong to the removed FTPS workflow: delete them (`~/Backups/scripts/remove-ftp-deploys.zsh` covers this for other projects).
