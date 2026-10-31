@~/.claude/CLAUDE.md

# MimmyCare — Project CLAUDE.md

**Client NN:** 04
**Repo:** devgeereact/mimmycare (private) — standalone
**Host:** cPanel **premium17** (UK) / user `devgeereact` → https://mimmycare.com (behind Cloudflare)
**DB:** `devgeereact_mimmycare` (MariaDB 11.4)

> Migrated US → UK datacenter 2026-07-21 (was premium152). `ssh cpanel` works (key auth, checked 30 Oct 2026).
> Server access from the Mac is read-only (logs, checks, backups); changes go through Git and the release.

## Surfaces & ports (pinned, strictPort)

| Surface | Stack | Local port | Server folder |
|---------|-------|-----------|---------------|
| landing | Vite/React/TS/Tailwind v4 | 5091 | `~/mimmycare.com/` |
| app     | Vite/React/TS/Tailwind v4, Clerk | 5092 | `~/mimmycare.com/app/` |
| api     | PHP 8.4, PDO MariaDB, Clerk JWT | 8041 | `~/mimmycare.com/api/` |

## Deploy

- Push `main` → `.github/workflows/deploy.yml` → `node scripts/release.mjs --go` over SSH (same as EGO LIMITED, kit release). Settings `release.json`. By hand: `npm run release`.
- Secrets: `CPANEL_SSH_KEY`, `CPANEL_KNOWN_HOSTS`. No FTP (the FTPS workflow was removed 30 Oct 2026).
- Full procedure: `docs/DEPLOYMENT.md`. API: `docs/API.md`.

## Rules

- Never commit `.env*` (except `.env.example`), `api/config.php`, or secrets. Clerk publishable keys are public.
- Real API settings: `~/private-config/mimmycare/config.php` (0600). `~/mimmycare.com/api/config.php` is a stub. Releases never upload `config.php`.
- The site root `.htaccess` (Cloudflare origin lock + landing SPA fallback) is server-managed and never uploaded. `app/` and `api/` ship their own `.htaccess` from the repo (`app/public/.htaccess`, `api/.htaccess`); each repeats the origin lock because a subfolder's rewrite rules replace the root's.
- Keep `/api` out of the SPA fallback. Schema changes: new numbered file in `api/migrations/`, never edit an applied one.
- Docs live in `docs/` (only README, AGENTS, CLAUDE, CODEX at the root).
