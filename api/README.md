# MimmyCare API (PHP/MySQL)

**Port (local dev):** 8041
**Deploy target:** `/home/devgeereact/mimmycare.com/api/` (cPanel, premium152)

## Deployment — MANUAL (option A)

This API is **NOT** part of the GitHub Actions FTP auto-deploy. The workflow
(`.github/workflows/deploy.yml`) syncs `landing/` and `app/` only, so CI can
never clobber server-side config or DB credentials.

Deploy API changes by hand (cPanel File Manager or `ssh cpanel` / SFTP).

## Secrets

- Real DB creds live in `config.php` **on the server only** — never committed.
- `config.example.php` is the tracked template (no values).
- `.htaccess` lives on the server; keep a copy locally if needed but do not
  commit live rewrite/security rules that reveal structure.

## Files on server (already uploaded)

- API endpoints
- `.htaccess`
- `config.php` (live DB credentials)
