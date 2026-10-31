<?php
/**
 * MimmyCare API — apply numbered SQL migrations from api/migrations/ (CLI only, idempotent).
 *
 *   php api/migrate.php           apply pending files in name order
 *   php api/migrate.php --list    show applied / pending, change nothing
 *
 * Uses migrate_db_user when the settings have one (schema rights), else the runtime user.
 * Take a database backup first (~/backups/mimmycare/). Applied files are never edited: add a new number.
 * Never reachable over HTTP: api/.htaccess denies it and this guard answers 404 outside the CLI.
 */
declare(strict_types=1);

if (PHP_SAPI !== "cli") { http_response_code(404); exit; }

require __DIR__ . "/lib/config.php";
require __DIR__ . "/lib/db.php";
require __DIR__ . "/lib/migrations.php";

$cfg = load_config();
if ($cfg === null) { fwrite(STDERR, "No settings file (" . config_path() . ").\n"); exit(1); }
$db = db_connect($cfg, true);
$listOnly = in_array("--list", $argv, true);
$code = run_migrations($db, __DIR__ . "/migrations", $listOnly, function (string $line): void { echo $line, "\n"; });
exit($code);
