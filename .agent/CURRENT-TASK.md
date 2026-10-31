# Current Task Contract

## Outcome

MimmyCare set up like EGO LIMITED: push-to-deploy over SSH (`release.json`, `scripts/release.mjs`, `.github/workflows/deploy.yml`), a PHP 8.4 API on MariaDB (`GET /api/health`, `GET /api/me` with Clerk session-token verification, migrations, tests), Clerk sign-in in the app, settings outside the web root. Infrastructure only: no product features until there is a spec.

## Mode

Existing Application, Change Safety. Owner-authorised (30 Oct 2026): local commits on `main`, no push; read-only server checks; no changes to the server, Clerk or GitHub settings.

## Status (30 Oct 2026)

- DONE (local, evidence in `docs/AUDIT.md`): API, app sign-in, landing "Sign in" link, release settings, SSH workflow (FTPS workflow removed), docs, server script `~/Backups/scripts/mimmycare-server-setup.zsh` (written, not run).
- NOT TESTED: everything live (see `docs/AUDIT.md`).

## Open steps (in order)

1. Owner: review and push the local commits (all `[skip ci]`, so the push does not deploy).
2. Owner: `~/Backups/scripts/mimmycare-server-setup.zsh` (preview), then `--apply` (retires the old `api.php`/`config.php`, writes `~/private-config/mimmycare/config.php` and the API stub, dumps the DB, applies migrations 001–002).
3. Owner: `~/Backups/scripts/setup-github-deploy.zsh --apply mimmycare` (deploy key + `CPANEL_SSH_KEY`, `CPANEL_KNOWN_HOSTS`); delete the old `FTP_*` secrets and the FTP account.
4. First release: `npm run release`, then `npm run release -- --go` (or a push without `[skip ci]`). Check `/api/health` shows `"db":true`; sign in at `/app/`.
5. Optional: Clerk session claim `name` (`{{user.full_name}}`) so names are stored server-side; enable Google in Clerk if wanted.
6. Owner review: `~/mimmycare.com/.ftp-deploy-sync-state.json` (publicly readable) and the stray `~/mimmycare.com/mimmycare.com/` copy.

## Evidence required to close

Live smoke checks pass after the first release; a real sign-in creates a `users` row; `docs/AUDIT.md` updated with the live results.

## Previous task

8 October 2026: GEE OS 0.9.0 audit and launcher fix (records in `../reports/mimmycare/` and git history). GEE OS adoption record: `docs/GEE-OS.md`.
