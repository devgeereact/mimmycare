<?php
/**
 * MimmyCare API settings — SAMPLE ONLY. No real values here, ever. Never deployed (release.json excludes it).
 *
 * On the server the real file is /home/devgeereact/private-config/mimmycare/config.php (0600, folder 0700),
 * outside every web root. ~/mimmycare.com/api/config.php is a stub without secrets that loads it:
 *
 *     <?php
 *     return require '/home/devgeereact/private-config/mimmycare/config.php';
 *
 * Releases never upload config.php (release.mjs PROTECT list). The server setup script
 * (~/Backups/scripts/mimmycare-server-setup.zsh) creates both files from the old web-root config.php.
 *
 * Local development: copy this file to api/config.php (gitignored) and fill in a local database, or set
 * MIMMYCARE_API_CONFIG to the path of a config file kept somewhere else.
 */
return [
    // MariaDB (cPanel). Runtime user: data rights only (SELECT, INSERT, UPDATE, DELETE).
    "db_host"    => "localhost",
    "db_port"    => 3306,
    "db_name"    => "devgeereact_mimmycare",
    "db_user"    => "__SET_DB_USER__",
    "db_pass"    => "__SET_DB_PASS__",

    // Optional: a separate user with CREATE/ALTER/INDEX rights, used only by migrate.php (CLI).
    // Leave out to run migrations with the runtime user.
    // "migrate_db_user" => "",
    // "migrate_db_pass" => "",

    // Clerk. Issuer = the Frontend API URL of the instance, https, no trailing slash.
    // Production: https://clerk.mimmycare.com. Local development with the dev instance (pk_test_...):
    // https://vocal-doe-9891.clerk.accounts.dev
    "clerk_issuer" => "https://clerk.mimmycare.com",
    // Exact origins allowed in the token's "azp" claim: the live site and the local app dev server.
    "clerk_authorized_parties" => ["https://mimmycare.com", "http://localhost:5092"],
    // Optional: private writable folder (outside the web root) for the cached JWKS signing keys.
    // Default: /home/devgeereact/private-config/mimmycare/cache
    // "clerk_cache_dir" => "/home/devgeereact/private-config/mimmycare/cache",

    // Origins allowed to call the API cross-origin (local development only). Live is same-origin: leave empty.
    "cors_origins" => [],
];
