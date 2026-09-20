<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_post_csrf();
$id = incident_id($_POST['id'] ?? null);
try {
    $stmt = db()->prepare('DELETE FROM incidents WHERE id = :id');
    $stmt->execute(['id' => $id]);
    redirect('/');
} catch (Throwable $exception) {
    error_log('Delete incident failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('Unable to delete incident.');
}
