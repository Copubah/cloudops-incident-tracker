<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
$heading = 'New incident';
$error = '';
$record = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $record = $_POST;
    try {
        $data = incident_input($_POST);
        $stmt = db()->prepare('INSERT INTO incidents (title, affected_service, severity, status, description, resolution, resolved_at) VALUES (:title, :service, :severity, :status, :description, :resolution, :resolved_at)');
        $stmt->execute([
            'title' => $data['title'], 'service' => $data['service'], 'severity' => $data['severity'], 'status' => $data['status'],
            'description' => $data['description'], 'resolution' => $data['resolution'] ?: null,
            'resolved_at' => $data['status'] === 'RESOLVED' ? date('Y-m-d H:i:s') : null,
        ]);
        redirect('/view.php?id=' . db()->lastInsertId());
    } catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
    catch (Throwable $exception) { error_log('Create incident failed: ' . $exception->getMessage()); $error = 'Unable to save incident.'; }
}
require __DIR__ . '/form.php';
