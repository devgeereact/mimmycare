<?php
/**
 * MimmyCare API — settings loader.
 *
 * Reads api/config.php (on the server: a stub that returns the private file in
 * /home/devgeereact/private-config/mimmycare/config.php). MIMMYCARE_API_CONFIG may point elsewhere
 * (local development and tests). Returns null when there is no usable file; never prints a value.
 */
declare(strict_types=1);

function config_path(): string
{
    $env = getenv("MIMMYCARE_API_CONFIG");
    return is_string($env) && $env !== "" ? $env : dirname(__DIR__) . "/config.php";
}

/** @return array<string,mixed>|null */
function load_config(?string $path = null): ?array
{
    $path ??= config_path();
    if (!is_file($path) || !is_readable($path)) return null;
    $cfg = (static fn(string $p) => require $p)($path);
    return is_array($cfg) ? $cfg : null;
}

/** Problems with a settings array (names only, never values). Empty list = usable. */
function config_problems(array $cfg): array
{
    $p = [];
    foreach (["db_host", "db_name", "db_user"] as $k) {
        if (!is_string($cfg[$k] ?? null) || $cfg[$k] === "" || str_starts_with($cfg[$k], "__SET_")) $p[] = "$k missing";
    }
    if (!is_string($cfg["db_pass"] ?? null) || str_starts_with((string) ($cfg["db_pass"] ?? ""), "__SET_")) $p[] = "db_pass missing";
    $iss = $cfg["clerk_issuer"] ?? null;
    if (!is_string($iss) || !str_starts_with($iss, "https://") || str_ends_with($iss, "/")) $p[] = "clerk_issuer must be an https URL without trailing slash";
    $azp = $cfg["clerk_authorized_parties"] ?? null;
    if (!is_array($azp) || $azp === [] || array_filter($azp, fn($o) => !is_string($o) || $o === "") !== []) $p[] = "clerk_authorized_parties must be a non-empty list of origins";
    return $p;
}

function clerk_cache_dir(array $cfg): string
{
    return (string) ($cfg["clerk_cache_dir"] ?? "/home/devgeereact/private-config/mimmycare/cache");
}
