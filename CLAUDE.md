@~/.claude/CLAUDE.md

# MimmyCare — Project CLAUDE.md

**Client NN:** 04
**Repo:** devgeereact/mimmycare (private) — standalone
**Host:** cPanel **premium17** (UK, `185.61.152.45`) / user `devgeereact` → https://mimmycare.com
**DB:** `devgeereact_mimmycare`

> Migrated US → UK datacenter 2026-07-21 (was premium152). `ssh cpanel` is
> **disabled** on the new server — key auth works, login shell does not. Use
> `lftp sftp://cpanel` or cPanel File Manager; `rsync` won't work.

## Surfaces & ports (pinned, strictPort)

| Surface | Stack | Local port | cPanel dir |
|---------|-------|-----------|-----------|
| landing | Vite/React/TS/Tailwind v4 | 5091 | `mimmycare.com/` |
| app     | Vite/React/TS/Tailwind v4 | 5092 | `mimmycare.com/app/` |
| api     | PHP/MySQL | 8041 | `mimmycare.com/` (root — `api.php`, **no** `api/` dir) |

## Deploy

- Push `main` → GitHub Actions FTPS deploy (`.github/workflows/deploy.yml`).
- CI deploys **landing + app only**. API = manual (option A).
- Secrets: `FTP_SERVER` / `FTP_USERNAME` / `FTP_PASSWORD`.

## Rules

- Never commit `.env`, `api/config.php`, live `.htaccess`.
- API endpoints already uploaded to cPanel by hand — do not let CI clobber.
- `.htaccess` files are **server-side only** and easy to lose: the site root and
  `app/` each need their own (SPA fallback, `RewriteBase /` and `/app/`). A
  deploy that wipes them silently breaks every deep link. See `api/README.md`.
- Check `FTP_SERVER` in GitHub Actions secrets still points at the UK host — a
  stale `premium152` value fails deploys silently.
