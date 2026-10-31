<?php
/**
 * MimmyCare API — HTTP helpers: JSON responses, errors, routing, client IP.
 *
 * Error shape (kit rule): {"error":{"code":"...","message":"..."}} with the right status. No stack traces
 * or database messages ever reach the client; they go to error_log with a request ref.
 */
declare(strict_types=1);

/** Thrown by handlers for a deliberate, client-safe error. */
final class ApiError extends RuntimeException
{
    public function __construct(public readonly int $status, public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}

/** @param array<string,mixed> $data */
function send_json(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/** @return array{error: array{code: string, message: string, ref?: string}} */
function error_body(string $code, string $message, ?string $ref = null): array
{
    $e = ["code" => $code, "message" => $message];
    if ($ref !== null) $e["ref"] = $ref;
    return ["error" => $e];
}

function send_error(int $status, string $code, string $message, ?string $ref = null): never
{
    send_json(error_body($code, $message, $ref), $status);
}

/**
 * The route inside the API, from the request path: "/api/health" -> "/health", "/api" -> "/".
 * Works behind Apache/LiteSpeed (rewrite to index.php) and with `php -S` (router script).
 */
function api_route(string $requestUri): string
{
    $path = parse_url($requestUri, PHP_URL_PATH);
    if (!is_string($path) || $path === "") $path = "/";
    $path = "/" . trim($path, "/");
    if ($path === "/api" || str_starts_with($path, "/api/")) $path = substr($path, 4);
    if (str_starts_with($path, "/index.php")) $path = substr($path, 10);
    $path = "/" . trim($path, "/");
    return $path;
}

/** Bearer token from the Authorization header (some FastCGI setups move it to REDIRECT_HTTP_AUTHORIZATION). */
function bearer_token(array $server): ?string
{
    $h = (string) ($server["HTTP_AUTHORIZATION"] ?? $server["REDIRECT_HTTP_AUTHORIZATION"] ?? "");
    return preg_match('/^Bearer\s+([A-Za-z0-9._~+\/=-]+)\s*$/i', $h, $m) === 1 ? $m[1] : null;
}

/** True if $ip is inside the CIDR (IPv4 or IPv6). */
function ip_in_cidr(string $ip, string $cidr): bool
{
    [$net, $bits] = array_pad(explode("/", $cidr, 2), 2, null);
    $ipB = @inet_pton($ip);
    $netB = @inet_pton((string) $net);
    if ($ipB === false || $netB === false || strlen($ipB) !== strlen($netB)) return false;
    $bits = (int) $bits;
    $bytes = intdiv($bits, 8);
    $rem = $bits % 8;
    if ($bytes && strncmp($ipB, $netB, $bytes) !== 0) return false;
    if ($rem) {
        $mask = (0xff << (8 - $rem)) & 0xff;
        if ((ord($ipB[$bytes]) & $mask) !== (ord($netB[$bytes]) & $mask)) return false;
    }
    return true;
}

/**
 * The visitor's IP. CF-Connecting-IP is trusted only when the request really came from a Cloudflare edge
 * (REMOTE_ADDR inside Cloudflare's published ranges); otherwise the header could be forged.
 */
function client_ip(array $server): string
{
    $remote = (string) ($server["REMOTE_ADDR"] ?? "0.0.0.0");
    $cf = (string) ($server["HTTP_CF_CONNECTING_IP"] ?? "");
    if ($cf === "" || @inet_pton($cf) === false) return $remote;
    static $ranges = [
        "173.245.48.0/20", "103.21.244.0/22", "103.22.200.0/22", "103.31.4.0/22", "141.101.64.0/18",
        "108.162.192.0/18", "190.93.240.0/20", "188.114.96.0/20", "197.234.240.0/22", "198.41.128.0/17",
        "162.158.0.0/15", "104.16.0.0/13", "104.24.0.0/14", "172.64.0.0/13", "131.0.72.0/22",
        "2400:cb00::/32", "2606:4700::/32", "2803:f800::/32", "2405:b500::/32", "2405:8100::/32", "2a06:98c0::/29", "2c0f:f248::/32",
    ];
    foreach ($ranges as $cidr) if (ip_in_cidr($remote, $cidr)) return $cf;
    return $remote;
}
