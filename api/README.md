# MimmyCare API (PHP/MySQL)

**Port (local dev):** 8041
**Deploy target:** not confirmed (cPanel, premium152). See "Where the API lives" below.

## Where the API lives (owner to confirm)

- The API source is **not in this repository**. Only this README and
  `config.example.php` are tracked under `api/`.
- `.github/workflows/deploy.yml` states that the docroot
  (`/home/devgeereact/mimmycare.com`) already holds `api.php`, `config.php`
  and `htaccess` at its root, and excludes them from the landing sync.
- This README previously named `/home/devgeereact/mimmycare.com/api/` as the
  deploy target. The workflow now excludes both the root files and `api/**`.
- Which location production actually serves, and where the API source is
  maintained, must be confirmed by the owner. Update this section once known.

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
