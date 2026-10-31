# MimmyCare

Client **NN 04**. Standalone repo. Three surfaces: landing, app, api.

## Stack

| Surface | Stack | Local port | Live | Server folder (cPanel) |
|---------|-------|-----------|------|------------------------|
| landing | Vite / React / TS / Tailwind v4 | 5091 | https://mimmycare.com/ | `~/mimmycare.com/` |
| app     | Vite / React / TS / Tailwind v4, Clerk sign-in | 5092 | https://mimmycare.com/app/ | `~/mimmycare.com/app/` |
| api     | PHP 8.4, PDO MariaDB, Clerk JWT | 8041 | https://mimmycare.com/api/ | `~/mimmycare.com/api/` |

- Host: cPanel **premium17** (`devgeereact`, `ssh cpanel`) behind Cloudflare → https://mimmycare.com
- DB: `devgeereact_mimmycare` (MariaDB 11.4). Schema: `api/migrations/`.
- Sign-in: Clerk application "MimmyCare" (email code). Details: `docs/DEPLOYMENT.md`.
- Repo: `devgeereact/mimmycare` (private)
- API reference: `docs/API.md`

## Develop

```bash
npm ci               # installs landing + app (npm workspaces)
npm run dev:landing  # http://localhost:5091
npm run dev:app      # http://localhost:5092/app/  (needs app/.env.local, copy app/.env.example)
npm run dev:api      # http://127.0.0.1:8041/api/health  (needs api/config.php, copy api/config.sample.php)
npm test             # Vitest (app) + php api/tests/run.php
npm run build        # builds landing + app
```

To bind the development sites to this computer only: `npm run dev:landing -- --host 127.0.0.1` (and the same for `dev:app`). Ports are pinned (`strictPort`). The app's dev server proxies `/api` to the local API on 8041.

## Deploy

Push to `main` → GitHub Actions (`.github/workflows/deploy.yml`) runs `node scripts/release.mjs --go` over **SSH**: checks, build, a snapshot of every server file it will change (`~/backups/mimmycare/releases/`), upload (nothing deleted on the server, `config.php` and the root `.htaccess` never sent), smoke checks on `/`, `/app/` and `/api/health`, automatic restore on failure. Settings: `release.json`.

- By hand from the Mac: `npm run release` (dry run), then `npm run release -- --go`. Roll back: `npm run release -- --rollback --go`.
- Docs-only pushes and `[skip ci]` commits do not deploy.
- GitHub secrets: `CPANEL_SSH_KEY`, `CPANEL_KNOWN_HOSTS` (`~/Backups/scripts/setup-github-deploy.zsh --apply mimmycare`). No FTP.
- One-time server preparation before the first release: `~/Backups/scripts/mimmycare-server-setup.zsh` (preview), then `--apply`.

Full procedure, server layout and Clerk settings: `docs/DEPLOYMENT.md`.

## Secrets

- Server settings live in `~/private-config/mimmycare/config.php` (0600), outside the web root; `~/mimmycare.com/api/config.php` is a stub that loads it. `api/config.sample.php` documents the keys (no values).
- `api/config.php`, `.env`, `.env.*` (except `.env.example`) are gitignored. Everything `VITE_*` is public; the Clerk publishable keys are public by design (`.gitleaks.toml` allows them). No Clerk secret key is used.
