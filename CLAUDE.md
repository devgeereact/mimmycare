@~/.claude/CLAUDE.md

# MimmyCare — Project CLAUDE.md

**Client NN:** 04
**Repo:** devgeereact/mimmycare (private) — standalone
**Host:** cPanel premium152 / user `devgeereact` → https://mimmycare.com
**DB:** `devgeereact_mimmycare`

## Surfaces & ports (pinned, strictPort)

| Surface | Stack | Local port | cPanel dir |
|---------|-------|-----------|-----------|
| landing | Vite/React/TS/Tailwind v4 | 5041 | `mimmycare.com/` |
| app     | Vite/React/TS/Tailwind v4 | 5042 | `mimmycare.com/app/` |
| api     | PHP/MySQL | 8041 | `mimmycare.com/api/` |

## Deploy

- Push `main` → GitHub Actions FTPS deploy (`.github/workflows/deploy.yml`).
- CI deploys **landing + app only**. API = manual (option A).
- Secrets: `FTP_SERVER` / `FTP_USERNAME` / `FTP_PASSWORD`.

## Rules

- Never commit `.env`, `api/config.php`, live `.htaccess`.
- API endpoints already uploaded to cPanel by hand — do not let CI clobber.
