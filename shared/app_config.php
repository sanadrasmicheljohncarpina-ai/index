<?php
declare(strict_types=1);

/**
 * Production/runtime configuration for the Evaluation System.
 *
 * Configuration precedence:
 *   1) Environment variables supplied by the hosting platform
 *   2) Root .env.php (ignored by Git; suitable for shared-hosting config)
 *   3) Local development defaults
 *
 * Never commit real passwords, SMTP credentials, or API keys.
 */

function app_local_config(): array
{
    static $loaded = null;
    if ($loaded !== null) {
        return $loaded;
    }

    $path = dirname(__DIR__) . '/.env.php';
    if (!is_file($path)) {
        $loaded = [];
        return $loaded;
    }

    $cfg = include $path;
    $loaded = is_array($cfg) ? $cfg : [];
    return $loaded;
}

function app_setting(string $key, mixed $default = null): mixed
{
    $env = getenv($key);
    if ($env !== false && $env !== '') {
        return $env;
    }

    $local = app_local_config();
    if (array_key_exists($key, $local) && $local[$key] !== '') {
        return $local[$key];
    }

    return $default;
}

function app_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $config = [
        'APP_ENV'      => (string)app_setting('APP_ENV', 'local'),
        'APP_URL'      => rtrim((string)app_setting('APP_URL', 'https://school-evaluation.com'), '/'),
        'APP_DOMAIN'   => (string)app_setting('APP_DOMAIN', 'school-evaluation.com'),
        'APP_TIMEZONE' => (string)app_setting('APP_TIMEZONE', 'Asia/Manila'),

        'DB_HOST' => (string)app_setting('DB_HOST', 'localhost'),
        'DB_PORT' => (int)app_setting('DB_PORT', 3306),
        'DB_NAME' => (string)app_setting('DB_NAME', 'evaluation'),
        'DB_USER' => (string)app_setting('DB_USER', 'root'),
        'DB_PASS' => (string)app_setting('DB_PASS', ''),
    ];

    date_default_timezone_set($config['APP_TIMEZONE']);
    return $config;
}

function app_url(string $path = ''): string
{
    $base = rtrim((string)app_config()['APP_URL'], '/');
    $path = '/' . ltrim($path, '/');
    return $base . ($path === '/' ? '' : $path);
}

function app_is_production(): bool
{
    return strtolower((string)app_config()['APP_ENV']) === 'production';
}
