<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; style-src \'self\'; form-action \'self\'; frame-ancestors \'none\'');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('cloudops_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function escape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function require_post_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
        !is_string($_POST['csrf_token'] ?? null) ||
        !hash_equals(csrf_token(), $_POST['csrf_token'])) {
        http_response_code(403);
        exit('Invalid request.');
    }
}

function redirect(string $path = '/'): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function incident_input(array $input): array
{
    foreach (['title', 'affected_service', 'description', 'resolution', 'severity', 'status'] as $field) {
        if (isset($input[$field]) && !is_string($input[$field])) {
            throw new InvalidArgumentException('Invalid incident field.');
        }
    }
    $title = trim((string)($input['title'] ?? ''));
    $service = trim((string)($input['affected_service'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $resolution = trim((string)($input['resolution'] ?? ''));
    $severity = (string)($input['severity'] ?? '');
    $status = (string)($input['status'] ?? 'OPEN');

    if ($title === '' || mb_strlen($title) > 180 || $service === '' || mb_strlen($service) > 120 ||
        $description === '' || mb_strlen($description) > 10000 || mb_strlen($resolution) > 10000 ||
        !in_array($severity, ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'], true) ||
        !in_array($status, ['OPEN', 'INVESTIGATING', 'RESOLVED'], true) ||
        ($status === 'RESOLVED' && $resolution === '')) {
        throw new InvalidArgumentException('Complete all required fields. Resolved incidents need resolution notes.');
    }
    return compact('title', 'service', 'description', 'resolution', 'severity', 'status');
}

function incident_id(mixed $value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        http_response_code(400);
        exit('Invalid incident ID.');
    }
    return $id;
}
