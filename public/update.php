<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
$id = incident_id($_GET['id'] ?? null);
$heading = 'Edit incident #' . $id;
$error = '';
try {
    $stmt = db()->prepare('SELECT * FROM incidents WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $original = $stmt->fetch();
    if (!$original) { http_response_code(404); exit('Incident not found.'); }
    $record = $original;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_post_csrf();
        $record = $_POST;
        try {
            $data = incident_input($_POST);
            $resolvedAt = $data['status'] === 'RESOLVED' ? ($original['resolved_at'] ?? date('Y-m-d H:i:s')) : null;
            $update = db()->prepare('UPDATE incidents SET title=:title, affected_service=:service, severity=:severity, status=:status, description=:description, resolution=:resolution, resolved_at=:resolved_at WHERE id=:id');
            $update->execute(['title'=>$data['title'], 'service'=>$data['service'], 'severity'=>$data['severity'], 'status'=>$data['status'], 'description'=>$data['description'], 'resolution'=>$data['resolution'] ?: null, 'resolved_at'=>$resolvedAt, 'id'=>$id]);
            redirect('/view.php?id=' . $id);
        } catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
    }
} catch (Throwable $exception) { error_log('Update incident failed: ' . $exception->getMessage()); http_response_code(503); exit('Application temporarily unavailable.'); }
require __DIR__ . '/form.php';
