<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
try {
    db()->query('SELECT 1');
    echo json_encode(['status' => 'healthy', 'database' => 'connected']);
} catch (Throwable $exception) {
    error_log('Health check database connection failed');
    http_response_code(503);
    echo json_encode(['status' => 'unhealthy', 'database' => 'unavailable']);
}
