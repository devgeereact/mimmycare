<?php
/**
 * MimmyCare API — front controller. Every request under /api reaches this file (api/.htaccess).
 *
 *   GET /api/health   public: {"ok":true,"service":"mimmycare-api","db":true}; 503 with ok:false when the
 *                     database (or the settings) are unavailable.
 *   GET /api/me       Clerk session token required (Authorization: Bearer <token>): creates the user on
 *                     first sign-in, refreshes it afterwards, returns {"user":{...}}.
 *
 * Errors: {"error":{"code":"...","message":"..."}} (+ "ref" on 500, matching a line in error_log).
 * Stack: PHP 8.4, PDO MariaDB, no Composer. Settings: lib/config.php.
 */
declare(strict_types=1);

require __DIR__ . "/lib/http.php";
require __DIR__ . "/lib/config.php";
require __DIR__ . "/lib/db.php";
require __DIR__ . "/lib/rate_limit.php";
require __DIR__ . "/lib/auth.php";

define("MC_RID", bin2hex(random_bytes(6)));

header("Content-Type: application/json; charset=utf-8");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("X-Frame-Options: DENY");
header("Cache-Control: no-store");

/* Everything unhandled is logged with a ref and answered as a JSON 500; nothing internal is echoed. */
set_exception_handler(function (Throwable $e): void {
    if ($e instanceof ApiError) {
        http_response_code($e->status);
        echo json_encode(error_body($e->errorCode, $e->getMessage()));
        return;
    }
    $route = ($_SERVER["REQUEST_METHOD"] ?? "?") . " " . api_route((string) ($_SERVER["REQUEST_URI"] ?? "/"));
    error_log(sprintf("[mimmycare %s] %s: %s @ %s:%d | %s", MC_RID, get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $route));
    http_response_code(500);
    echo json_encode(error_body("server_error", "Something went wrong on our side.", MC_RID));
});

$cfg = load_config();
$cfgOk = $cfg !== null && config_problems($cfg) === [];
if ($cfg !== null && !$cfgOk) error_log(sprintf("[mimmycare %s] config: %s", MC_RID, implode("; ", config_problems($cfg))));

/* CORS: only listed origins (local development). Live is same-origin. */
$origin = (string) ($_SERVER["HTTP_ORIGIN"] ?? "");
if ($origin !== "" && $cfg !== null && in_array($origin, (array) ($cfg["cors_origins"] ?? []), true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Max-Age: 600");
}
$method = strtoupper((string) ($_SERVER["REQUEST_METHOD"] ?? "GET"));
if ($method === "OPTIONS") { http_response_code(204); exit; }

/** The database, connected on first use. Throws ApiError 503 when it cannot be reached. */
$db = function () use ($cfg, $cfgOk): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    if (!$cfgOk) throw new ApiError(503, "not_configured", "The service is not configured yet.");
    try {
        return $pdo = db_connect($cfg);
    } catch (PDOException $e) {
        error_log(sprintf("[mimmycare %s] db connect: %s", MC_RID, $e->getMessage()));
        throw new ApiError(503, "database_unavailable", "The service is temporarily unavailable.");
    }
};

/** Verified Clerk claims for this request, or a 401 (429 after repeated failures from one IP). */
$requireClaims = function () use ($cfg, $cfgOk, $db): array {
    if (!$cfgOk) throw new ApiError(503, "not_configured", "The service is not configured yet.");
    $ipKey = "auth-fail:" . client_ip($_SERVER);
    if (!rate_allowed($db(), $ipKey, 20, 600)) throw new ApiError(429, "rate_limited", "Too many attempts. Wait a few minutes and try again.");
    $token = bearer_token($_SERVER);
    try {
        if ($token === null) throw new RuntimeException("no token");
        return clerk_verifier($cfg)->verify($token);
    } catch (RuntimeException) {
        if ($token !== null) rate_hit($db(), $ipKey);   // a missing token is not a strike: signed-out pages poll
        throw new ApiError(401, "unauthorized", "Sign in to continue.");
    }
};

$routes = [
    "GET /" => "health",
    "GET /health" => "health",
    "GET /me" => "me",
];

$route = api_route((string) ($_SERVER["REQUEST_URI"] ?? "/"));
$handler = $routes["$method $route"] ?? null;
if ($handler === null) {
    $allowed = [];
    foreach (array_keys($routes) as $k) {
        [$m, $r] = explode(" ", $k, 2);
        if ($r === $route) $allowed[] = $m;
    }
    if ($allowed !== []) {
        header("Allow: " . implode(", ", $allowed));
        send_error(405, "method_not_allowed", "Method not allowed.");
    }
    send_error(404, "not_found", "Not found.");
}

switch ($handler) {
    case "health": {
        $ok = false;
        try {
            $ok = db_ping($db());
        } catch (ApiError) {
            $ok = false;
        }
        send_json(["ok" => $ok, "service" => "mimmycare-api", "db" => $ok], $ok ? 200 : 503);
    }

    case "me": {
        $claims = $requireClaims();
        $profile = profile_from_claims($claims);
        if ($profile === null) throw new ApiError(401, "unauthorized", "Sign in to continue.");
        $userKey = "me:" . $profile["clerk_user_id"];
        if (!rate_allowed($db(), $userKey, 60, 60)) throw new ApiError(429, "rate_limited", "Too many requests. Give it a moment.");
        rate_hit($db(), $userKey);
        send_json(["user" => public_user(upsert_user($db(), $profile))]);
    }
}
