<?php
/**
 * MimmyCare API — Clerk sign-in: from a verified session token to a users row.
 *
 * Clerk proves WHO is calling; everything else (roles, data ownership) lives in this database and is keyed
 * on users.clerk_user_id (the token's "sub"). Nothing about the user is ever taken from the request body.
 * Optional profile claims (email, name) are read from the verified token only. The Clerk instance adds
 * "email" to the session token; add a "name" session claim in Clerk to store names server-side too.
 */
declare(strict_types=1);

require_once __DIR__ . "/clerk_jwt.php";

function clerk_fetch_json(string $url): string
{
    $ctx = stream_context_create([
        "http" => ["timeout" => 5, "ignore_errors" => false, "header" => "Accept: application/json\r\n"],
        "ssl" => ["verify_peer" => true, "verify_peer_name" => true],
    ]);
    $r = @file_get_contents($url, false, $ctx);
    if ($r === false) throw new RuntimeException("jwks fetch failed");
    return $r;
}

function clerk_verifier(array $cfg): MimmyClerkJwt
{
    $dir = clerk_cache_dir($cfg);
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    return new MimmyClerkJwt((string) $cfg["clerk_issuer"], array_values((array) $cfg["clerk_authorized_parties"]), $dir, "clerk_fetch_json");
}

/**
 * The fields we keep from verified claims. Pure: pass only claims from MimmyClerkJwt::verify().
 * Returns null when the subject is unusable.
 * @return array{clerk_user_id: string, email: ?string, name: ?string}|null
 */
function profile_from_claims(array $claims): ?array
{
    $sub = $claims["sub"] ?? null;
    if (!is_string($sub) || preg_match('/^[A-Za-z0-9_]{1,64}$/', $sub) !== 1) return null;

    $email = $claims["email"] ?? null;
    $email = is_string($email) ? strtolower(trim($email)) : "";
    if ($email === "" || strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) $email = null;

    $name = null;
    foreach (["name", "full_name"] as $k) {
        if (is_string($claims[$k] ?? null) && trim($claims[$k]) !== "") { $name = $claims[$k]; break; }
    }
    if ($name === null) {
        $first = is_string($claims["first_name"] ?? null) ? trim($claims["first_name"]) : "";
        $last = is_string($claims["last_name"] ?? null) ? trim($claims["last_name"]) : "";
        $joined = trim("$first $last");
        if ($joined !== "") $name = $joined;
    }
    if ($name !== null) {
        $name = trim(preg_replace('/[\x00-\x1F\x7F]+/u', " ", $name) ?? "");
        $name = $name === "" ? null : mb_substr($name, 0, 190);
    }
    return ["clerk_user_id" => $sub, "email" => $email, "name" => $name];
}

/**
 * Insert the user on first sign-in, refresh email/name/last_seen_at afterwards. A claim that is absent
 * never wipes a stored value. Returns the stored row.
 */
function upsert_user(PDO $db, array $profile): array
{
    db_exec(
        $db,
        "INSERT INTO users (clerk_user_id, email, name, last_seen_at) VALUES (?, ?, ?, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE email = COALESCE(VALUES(email), email), name = COALESCE(VALUES(name), name), last_seen_at = UTC_TIMESTAMP()",
        [$profile["clerk_user_id"], $profile["email"], $profile["name"]],
    );
    $row = db_one($db, "SELECT id, clerk_user_id, email, name, created_at, last_seen_at FROM users WHERE clerk_user_id = ?", [$profile["clerk_user_id"]]);
    if ($row === null) throw new RuntimeException("user row missing after upsert");
    return $row;
}

/** The user as the API returns it. */
function public_user(array $row): array
{
    return [
        "id" => (int) $row["id"],
        "clerk_user_id" => (string) $row["clerk_user_id"],
        "email" => $row["email"] !== null ? (string) $row["email"] : null,
        "name" => $row["name"] !== null ? (string) $row["name"] : null,
        "created_at" => (string) $row["created_at"],
        "last_seen_at" => $row["last_seen_at"] !== null ? (string) $row["last_seen_at"] : null,
    ];
}
