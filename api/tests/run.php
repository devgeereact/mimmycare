<?php
/**
 * MimmyCare API — test suite. Run: php api/tests/run.php   (release.json "checks"; CI runs it on every deploy.)
 *
 * Zero-dependency harness (same as EGO LIMITED): no Composer, no PHPUnit. Needs no database.
 * Covers: Clerk session token verification (signature, iss, exp, nbf, sub, azp, alg, kid), claim parsing,
 * routing, bearer parsing, client IP trust, settings validation, migration file parsing, .htaccess guards.
 * NOT covered here (needs MariaDB): upsert_user, rate limits, migrate.php against a database.
 */
declare(strict_types=1);

require_once __DIR__ . "/../lib/http.php";
require_once __DIR__ . "/../lib/config.php";
require_once __DIR__ . "/../lib/db.php";
require_once __DIR__ . "/../lib/auth.php";
require_once __DIR__ . "/../lib/migrations.php";

// ---------------------------------------------------------------- tiny harness
$T = ["pass" => 0, "fail" => 0, "failures" => []];
function ok(bool $cond, string $what): void
{
    global $T;
    if ($cond) { $T["pass"]++; return; }
    $T["fail"]++;
    $T["failures"][] = $what;
}
function eq(mixed $expected, mixed $actual, string $what): void
{
    if ($expected !== $actual) $what .= sprintf("  (expected %s, got %s)", var_export($expected, true), var_export($actual, true));
    ok($expected === $actual, $what);
}
function throws(callable $fn, string $what): void
{
    try { $fn(); ok(false, "$what (expected a throw, got none)"); }
    catch (Throwable) { ok(true, $what); }
}

// ---------------------------------------------------------------- Clerk session tokens
$b64u = fn(string $x): string => rtrim(strtr(base64_encode($x), "+/", "-_"), "=");
$mkKey = function () use ($b64u): array {
    $k = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
    $d = openssl_pkey_get_details($k);
    return [$k, ["kty" => "RSA", "kid" => "k1", "n" => $b64u($d["rsa"]["n"]), "e" => $b64u($d["rsa"]["e"])]];
};
[$clerkKey, $clerkJwk] = $mkKey();
[$otherKey] = $mkKey();
$iss = "https://clerk.mimmycare.com";
$parties = ["https://mimmycare.com", "http://localhost:5092"];
$now = 1_800_000_000;
$sign = function ($key, array $claims, array $hdr = []) use ($b64u): string {
    $h = $b64u(json_encode($hdr + ["alg" => "RS256", "kid" => "k1", "typ" => "JWT"]));
    $p = $b64u(json_encode($claims));
    openssl_sign("$h.$p", $sig, $key, OPENSSL_ALGO_SHA256);
    return "$h.$p." . $b64u($sig);
};
$good = ["iss" => $iss, "sub" => "user_2abcDEF", "azp" => "https://mimmycare.com", "exp" => $now + 60, "nbf" => $now - 5, "email" => "Mimmy@Example.com"];
$cacheDir = sys_get_temp_dir() . "/mimmycare-clerk-test-" . getmypid();
@mkdir($cacheDir, 0700, true);
$clearCache = function () use ($cacheDir): void { foreach (glob("$cacheDir/*") ?: [] as $f) @unlink($f); };
$fetches = 0;
$verifier = function () use ($iss, $parties, $cacheDir, $clerkJwk, $now, &$fetches): MimmyClerkJwt {
    return new MimmyClerkJwt($iss, $parties, $cacheDir, function (string $url) use ($clerkJwk, &$fetches, $iss): string {
        $fetches++;
        if ($url !== "$iss/.well-known/jwks.json") throw new RuntimeException("unexpected url $url");
        return json_encode(["keys" => [$clerkJwk]]);
    }, $now);
};
$rejects = function (string $tok, string $what) use ($verifier, $clearCache): void {
    $clearCache();
    throws(fn() => $verifier()->verify($tok), $what);
};

$clearCache();
eq("user_2abcDEF", $verifier()->verify($sign($clerkKey, $good))["sub"], "Clerk: a correctly signed token verifies");
eq("user_2abcDEF", $verifier()->verify($sign($clerkKey, ["azp" => "http://localhost:5092"] + $good))["sub"], "Clerk: the local dev origin is accepted");
$before = $fetches;
$verifier()->verify($sign($clerkKey, $good));
eq($before, $fetches, "Clerk: the JWKS is cached (no second fetch)");
$rejects($sign($otherKey, $good), "Clerk: a token signed by another key is rejected");
$rejects($sign($clerkKey, ["iss" => "https://clerk.evil.example"] + $good), "Clerk: a wrong issuer is rejected");
$rejects($sign($clerkKey, ["iss" => "https://vocal-doe-9891.clerk.accounts.dev"] + $good), "Clerk: a development-instance token is rejected by the production issuer");
$rejects($sign($clerkKey, ["exp" => $now - 60] + $good), "Clerk: an expired token is rejected");
$noExp = $good; unset($noExp["exp"]);
$rejects($sign($clerkKey, $noExp), "Clerk: a token without exp is rejected");
$rejects($sign($clerkKey, ["nbf" => $now + 600] + $good), "Clerk: a not-yet-valid token is rejected");
$rejects($sign($clerkKey, ["azp" => "https://evil.example"] + $good), "Clerk: an unlisted azp is rejected");
$rejects($sign($clerkKey, ["azp" => "https://mimmycare.com/"] + $good), "Clerk: azp must match exactly (trailing slash)");
$noAzp = $good; unset($noAzp["azp"]);
$rejects($sign($clerkKey, $noAzp), "Clerk: a missing azp is rejected");
$noSub = $good; unset($noSub["sub"]);
$rejects($sign($clerkKey, $noSub), "Clerk: a missing sub is rejected");
$rejects($sign($clerkKey, $good, ["alg" => "HS256"]), "Clerk: a non-RS256 alg is rejected");
$none = $b64u(json_encode(["alg" => "none", "kid" => "k1"])) . "." . $b64u(json_encode($good)) . ".";
$rejects($none, "Clerk: alg=none is rejected");
$rejects($sign($clerkKey, $good, ["kid" => "unknown"]), "Clerk: an unknown kid is rejected");
$rejects("a.b", "Clerk: a malformed token is rejected");
$tok = $sign($clerkKey, $good);
$rejects(substr($tok, 0, -4) . "AAAA", "Clerk: a tampered signature is rejected");
throws(fn() => new MimmyClerkJwt("http://clerk.mimmycare.com", $parties, $cacheDir, fn($u) => ""), "Clerk: an http issuer is refused");
throws(fn() => new MimmyClerkJwt("https://clerk.mimmycare.com/", $parties, $cacheDir, fn($u) => ""), "Clerk: an issuer with a trailing slash is refused");
$clearCache();
$down = new MimmyClerkJwt($iss, $parties, $cacheDir, function (string $u): string { throw new RuntimeException("offline"); }, $now);
throws(fn() => $down->verify($tok), "Clerk: JWKS unreachable and nothing cached fails closed");
$clearCache();
@rmdir($cacheDir);

// ---------------------------------------------------------------- claims -> profile
$p = profile_from_claims($good);
eq(["clerk_user_id" => "user_2abcDEF", "email" => "mimmy@example.com", "name" => null], $p, "Claims: sub and lower-cased email are kept, no name claim => null");
eq("Ada Lovelace", profile_from_claims(["name" => "  Ada Lovelace "] + $good)["name"] ?? null, "Claims: name claim is trimmed");
eq("Ada Lovelace", profile_from_claims(["first_name" => "Ada", "last_name" => "Lovelace"] + $good)["name"] ?? null, "Claims: first + last name are joined");
$emailOf = fn(?array $p): mixed => is_array($p) && array_key_exists("email", $p) ? $p["email"] : "missing";
eq(null, $emailOf(profile_from_claims(["email" => "not-an-email"] + $good)), "Claims: an invalid email is dropped, not stored");
eq(null, $emailOf(profile_from_claims(["email" => ["a@b.co"]] + $good)), "Claims: a non-string email is dropped");
eq("A B", profile_from_claims(["name" => "A\x00\nB"] + $good)["name"] ?? null, "Claims: control characters in a name are removed");
eq(190, mb_strlen(profile_from_claims(["name" => str_repeat("é", 300)] + $good)["name"] ?? ""), "Claims: names are capped at 190 characters");
eq(null, profile_from_claims(["sub" => ""] + $good), "Claims: an empty sub is refused");
eq(null, profile_from_claims(["sub" => "user_1; DROP TABLE users"] + $good), "Claims: a sub with odd characters is refused");
eq(null, profile_from_claims(["sub" => str_repeat("a", 65)] + $good), "Claims: a sub over 64 characters is refused");
$row = ["id" => "7", "clerk_user_id" => "user_x", "email" => null, "name" => "Mimmy", "created_at" => "2026-10-30 12:00:00", "last_seen_at" => null];
eq(["id" => 7, "clerk_user_id" => "user_x", "email" => null, "name" => "Mimmy", "created_at" => "2026-10-30 12:00:00", "last_seen_at" => null], public_user($row), "Users: public_user shapes the row");

// ---------------------------------------------------------------- routing and request parsing
eq("/health", api_route("/api/health"), "Route: /api/health");
eq("/health", api_route("/api/health/?release=123"), "Route: trailing slash and query are ignored");
eq("/me", api_route("/api/index.php/me"), "Route: /api/index.php/me");
eq("/", api_route("/api"), "Route: /api is the root");
eq("/", api_route("/api/"), "Route: /api/ is the root");
eq("/apiary", api_route("/apiary"), "Route: /apiary is not mistaken for /api");
eq("abc.def.ghi", bearer_token(["HTTP_AUTHORIZATION" => "Bearer abc.def.ghi"]), "Bearer: header is parsed");
eq("abc.def.ghi", bearer_token(["REDIRECT_HTTP_AUTHORIZATION" => "bearer abc.def.ghi"]), "Bearer: redirected header, any case");
eq(null, bearer_token(["HTTP_AUTHORIZATION" => "Basic dXNlcjpwYXNz"]), "Bearer: Basic auth is ignored");
eq(null, bearer_token([]), "Bearer: no header => null");
eq(null, bearer_token(["HTTP_AUTHORIZATION" => "Bearer a b"]), "Bearer: a value with spaces is refused");
eq(["error" => ["code" => "not_found", "message" => "Not found."]], error_body("not_found", "Not found."), "Errors: kit error shape");
eq("abc", error_body("x", "y", "abc")["error"]["ref"] ?? null, "Errors: a ref is included when given");

// ---------------------------------------------------------------- client IP trust
ok(ip_in_cidr("104.16.1.1", "104.16.0.0/13") && !ip_in_cidr("104.24.0.1", "104.16.0.0/13"), "IP: IPv4 CIDR match");
ok(ip_in_cidr("2606:4700::1", "2606:4700::/32") && !ip_in_cidr("2607::1", "2606:4700::/32"), "IP: IPv6 CIDR match");
eq("203.0.113.9", client_ip(["REMOTE_ADDR" => "104.16.1.1", "HTTP_CF_CONNECTING_IP" => "203.0.113.9"]), "IP: CF-Connecting-IP trusted from a Cloudflare edge");
eq("198.51.100.1", client_ip(["REMOTE_ADDR" => "198.51.100.1", "HTTP_CF_CONNECTING_IP" => "203.0.113.9"]), "IP: CF-Connecting-IP ignored from anywhere else");
eq("104.16.1.1", client_ip(["REMOTE_ADDR" => "104.16.1.1", "HTTP_CF_CONNECTING_IP" => "nonsense"]), "IP: an invalid CF-Connecting-IP is ignored");

// ---------------------------------------------------------------- settings
$sample = load_config(__DIR__ . "/../config.sample.php");
ok(is_array($sample), "Config: the sample loads");
ok(in_array("db_user missing", config_problems($sample ?? []), true), "Config: the sample's placeholders are reported as missing");
$real = ["db_host" => "localhost", "db_name" => "d", "db_user" => "u", "db_pass" => "p", "clerk_issuer" => $iss, "clerk_authorized_parties" => $parties];
eq([], config_problems($real), "Config: a complete settings array has no problems");
ok(config_problems(["clerk_issuer" => "https://clerk.mimmycare.com/"] + $real) !== [], "Config: an issuer with a trailing slash is a problem");
ok(config_problems(["clerk_authorized_parties" => []] + $real) !== [], "Config: no authorised parties is a problem");
eq(null, load_config(__DIR__ . "/does-not-exist.php"), "Config: a missing file => null");
eq("mysql:host=localhost;port=3306;dbname=d;charset=utf8mb4", db_dsn($real), "DB: DSN uses utf8mb4 and the default port");
eq("mysql:host=127.0.0.1;port=3307;dbname=d;charset=utf8mb4", db_dsn(["db_host" => "127.0.0.1", "db_port" => "3307"] + $real), "DB: a port from settings is used");
$sampleSrc = (string) file_get_contents(__DIR__ . "/../config.sample.php");
ok(!preg_match('/(sk|pk)_(live|test)_[A-Za-z0-9]{8,}/', $sampleSrc), "Config: the sample holds no Clerk keys");

// ---------------------------------------------------------------- migrations
$files = array_map("basename", migration_files(__DIR__ . "/../migrations"));
eq(["001_users.sql", "002_rate_hits.sql"], $files, "Migrations: numbered files in order");
eq(["CREATE TABLE a (x INT)", "CREATE TABLE b (y INT)"], split_sql_statements("-- note; with semicolon\nCREATE TABLE a (x INT);\n\n-- more\nCREATE TABLE b (y INT);\n"), "Migrations: statements split, comments dropped");
$users = split_sql_statements((string) file_get_contents(__DIR__ . "/../migrations/001_users.sql"));
ok(count($users) === 1 && str_contains($users[0], "UNIQUE KEY uq_users_clerk_user_id (clerk_user_id)"), "Migrations: 001 creates users with a unique Clerk id");
foreach ($files as $f) ok(str_contains((string) file_get_contents(__DIR__ . "/../migrations/$f"), "IF NOT EXISTS"), "Migrations: $f is safe to re-run");

// ---------------------------------------------------------------- web guards
$ht = (string) file_get_contents(__DIR__ . "/../.htaccess");
ok(str_contains($ht, "RewriteRule ^(lib|migrations|tests)(/|$) - [F,L]"), ".htaccess: lib/, migrations/, tests/ are denied");
ok(str_contains($ht, "RewriteRule ^(migrate|config|config\\.sample)\\.php$ - [F,L]"), ".htaccess: migrate.php and config files are denied");
ok(str_contains($ht, "RewriteRule ^ index.php [QSA,L]"), ".htaccess: requests go to index.php");
ok(str_contains($ht, "E=HTTP_AUTHORIZATION"), ".htaccess: the Authorization header is kept");
ok(str_contains((string) file_get_contents(__DIR__ . "/../migrate.php"), 'if (PHP_SAPI !== "cli")'), "migrate.php: refuses to run outside the CLI");
$release = json_decode((string) file_get_contents(__DIR__ . "/../../release.json"), true);
$apiPart = array_values(array_filter($release["parts"] ?? [], fn($p) => ($p["from"] ?? "") === "api"))[0] ?? [];
ok(in_array("/tests/", $apiPart["exclude"] ?? [], true) && in_array("/config.sample.php", $apiPart["exclude"] ?? [], true), "release.json: tests and the sample config are never uploaded");
ok(!in_array("config.php", $apiPart["allow"] ?? [], true), "release.json: config.php is never allowed");

// ---------------------------------------------------------------- report
printf("\n  %d passed, %d failed\n", $T["pass"], $T["fail"]);
foreach ($T["failures"] as $f) printf("  FAIL: %s\n", $f);
exit($T["fail"] === 0 ? 0 : 1);
