<?php
/**
 * MimmyCare API — database (PDO, MariaDB). Prepared statements only; never build SQL from request strings.
 */
declare(strict_types=1);

function db_dsn(array $cfg): string
{
    $port = (int) ($cfg["db_port"] ?? 3306) ?: 3306;
    return sprintf("mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4", (string) $cfg["db_host"], $port, (string) $cfg["db_name"]);
}

/** Connect with the runtime user, or with the migration user when $migrate is true (falls back to runtime). */
function db_connect(array $cfg, bool $migrate = false): PDO
{
    $user = (string) ($migrate && !empty($cfg["migrate_db_user"]) ? $cfg["migrate_db_user"] : $cfg["db_user"]);
    $pass = (string) ($migrate && !empty($cfg["migrate_db_user"]) ? ($cfg["migrate_db_pass"] ?? "") : ($cfg["db_pass"] ?? ""));
    $pdo = new PDO(db_dsn($cfg), $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    // Store and compare times in UTC regardless of the server's own zone.
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}

/** One row or null. @param list<mixed> $args */
function db_one(PDO $db, string $sql, array $args = []): ?array
{
    $st = $db->prepare($sql);
    $st->execute($args);
    $row = $st->fetch();
    return is_array($row) ? $row : null;
}

/** Execute a write; returns the affected row count. @param list<mixed> $args */
function db_exec(PDO $db, string $sql, array $args = []): int
{
    $st = $db->prepare($sql);
    $st->execute($args);
    return $st->rowCount();
}

/** Health probe: true when SELECT 1 answers. Never throws. */
function db_ping(?PDO $db): bool
{
    if ($db === null) return false;
    try {
        return (int) $db->query("SELECT 1")->fetchColumn() === 1;
    } catch (Throwable) {
        return false;
    }
}
