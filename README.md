# MimmyCare

Client **NN 04**. Standalone repo. Three surfaces: landing, app, api.

## Stack

| Surface | Stack | Local port | Deploy dir (cPanel) |
|---------|-------|-----------|---------------------|
| landing | Vite / React / TS / Tailwind v4 | 5091 | `mimmycare.com/` (root) |
| app     | Vite / React / TS / Tailwind v4 | 5092 | `mimmycare.com/app/` |
| api     | PHP / MySQL | 8041 | `mimmycare.com/api/` (manual) |

- Host: cPanel `premium152` (`devgeereact`) → https://mimmycare.com
- DB: `devgeereact_mimmycare`
- Repo: `devgeereact/mimmycare` (private)

## Develop

```bash
npm install          # installs landing + app (npm workspaces)
npm run dev:landing  # http://localhost:5091
npm run dev:app      # http://localhost:5092
npm run build        # builds both
```

Ports are pinned (`strictPort`) — a clash fails loud instead of drifting.

## Deploy

Push to `main` → GitHub Actions builds landing + app and syncs via **FTPS**
to cPanel (`.github/workflows/deploy.yml`).

- Secrets: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` (repo settings).
- **API is deployed manually** — never touched by CI. See `api/README.md`.
- Landing deploy excludes `app/` and `api/` so the root mirror can't delete them.

## Secrets

- `.env` (gitignored) holds local secrets — never commit.
- `.env.example` is the tracked template.
- `api/config.php` lives on the server only; `api/config.example.php` is the template.
