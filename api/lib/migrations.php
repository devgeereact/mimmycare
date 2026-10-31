<?php
/**
 * MimmyCare API — migration runner core (used by migrate.php; the pure parts are unit-tested).
 * Applied names are recorded in schema_migrations. MariaDB commits DDL implicitly, so each file should be
 * safe to re-run (CREATE TABLE IF NOT EXISTS ...) in case it stopped half way.
 */
declare(strict_types=1);

/** Migration files in apply order: NNN_name.sql only. @return list<string> full paths */
function migration_files(string $dir): array
{
    $files = array_values(array_filter(glob($dir . "/*.sql") ?: [], fn($f) => preg_match('/^\d{3}_[a-z0-9_]+\.sql$/', basename($f)) === 1));
    sort($files, SORT_STRING);
    return $files;
}

/**
 * Split a migration file into statements: drops "-- " comment lines and blank statements. Statements are
 * separated by ";" at the end of a line. Migrations must not contain procedures or ";" inside strings.
 * @return list<string>
 */
function split_sql_statements(string $sql): array
{
    $sql = preg_replace('/^\s*--.*$/m', "", $sql) ?? "";
    $parts = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
    return array_values(array_filter(array_map("trim", $parts), fn($s) => $s !== ""));
}

/** @param callable(string):void $out */
function run_migrations(PDO $db, string $dir, bool $listOnly, callable $out): int
{
    $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        name VARCHAR(120) NOT NULL PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $done = $db->query("SELECT name FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);
    $pending = 0;
    foreach (migration_files($dir) as $f) {
        $name = basename($f);
        if (in_array($name, $done, true)) { $out("applied  $name"); continue; }
        $pending++;
        if ($listOnly) { $out("pending  $name"); continue; }
        foreach (split_sql_statements((string) file_get_contents($f)) as $stmt) $db->exec($stmt);
        $st = $db->prepare("INSERT INTO schema_migrations (name) VALUES (?)");
        $st->execute([$name]);
        $out("ran      $name");
    }
    if ($pending === 0) $out("nothing pending");
    return 0;
}
