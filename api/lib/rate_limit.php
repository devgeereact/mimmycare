<?php
/**
 * MimmyCare API — persistent sliding-window rate limit (same approach as EGO LIMITED).
 *
 * Table rate_hits comes from migrations/002_rate_hits.sql: the runtime user never creates tables.
 * rate_allowed() reads; rate_hit() records one strike. Failed sign-ins are recorded per IP so normal
 * use is never throttled; /me also has a per-user cap because every call writes.
 */
declare(strict_types=1);

function rate_allowed(PDO $db, string $key, int $max, int $windowSec): bool
{
    $row = db_one($db, "SELECT COUNT(*) AS c FROM rate_hits WHERE k = ? AND created_at > (UTC_TIMESTAMP() - INTERVAL ? SECOND)", [rate_key($key), $windowSec]);
    return (int) ($row["c"] ?? 0) < $max;
}

function rate_hit(PDO $db, string $key): void
{
    db_exec($db, "INSERT INTO rate_hits (k, created_at) VALUES (?, UTC_TIMESTAMP())", [rate_key($key)]);
    if (random_int(1, 50) === 1) $db->exec("DELETE FROM rate_hits WHERE created_at < (UTC_TIMESTAMP() - INTERVAL 1 DAY)");
}

/** Keys are hashed so no IP or user id is stored in clear and the column length is fixed. */
function rate_key(string $key): string
{
    return hash("sha256", $key);
}
