<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = db();
    $stats = $pdo->query("SELECT COUNT(*) total, SUM(status='OPEN') open_count, SUM(status='INVESTIGATING') investigating_count, SUM(status='RESOLVED') resolved_count, SUM(severity='CRITICAL') critical_count, ROUND(AVG(CASE WHEN resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, resolved_at) END), 1) avg_minutes FROM incidents")->fetch();
    $search = trim((string)($_GET['q'] ?? ''));
    $severity = (string)($_GET['severity'] ?? '');
    $status = (string)($_GET['status'] ?? '');
    if (mb_strlen($search) > 100) { $search = mb_substr($search, 0, 100); }
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(title LIKE :title OR affected_service LIKE :service OR description LIKE :description)';
        $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
        $params += ['title' => $term, 'service' => $term, 'description' => $term];
    }
    if (in_array($severity, ['LOW','MEDIUM','HIGH','CRITICAL'], true)) { $where[] = 'severity = :severity'; $params['severity'] = $severity; }
    if (in_array($status, ['OPEN','INVESTIGATING','RESOLVED'], true)) { $where[] = 'status = :status'; $params['status'] = $status; }
    $sql = 'SELECT * FROM incidents' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC LIMIT 200';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $incidents = $stmt->fetchAll();
} catch (Throwable $exception) {
    error_log('Dashboard database error: ' . $exception->getMessage());
    http_response_code(503);
    exit('Application temporarily unavailable.');
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CloudOps Incident Tracker</title><link rel="stylesheet" href="/assets/css/style.css"></head><body>
<header class="topbar"><div class="wrap topbar-inner"><a class="brand" href="/"><span class="brand-mark">C<span>+</span></span><span class="brand-copy"><strong>CloudOps</strong><small>INCIDENT TRACKER</small></span></a><div class="topbar-right"><span class="live-indicator"><i></i> SYSTEM ONLINE</span><span class="top-note">OPERATIONS CONSOLE</span></div></div></header>
<main class="wrap">
<div class="page-head"><div><p class="eyebrow"><span class="eyebrow-line"></span> INCIDENT MANAGEMENT</p><h1>Operations overview<span class="heading-dot">.</span></h1><p class="muted">Monitor, investigate, and resolve service disruptions in one place.</p></div><a class="button" href="/create.php"><span class="button-plus">+</span> New incident</a></div>
<section class="stats" aria-label="Incident summary">
<?php foreach (['Total' => $stats['total'], 'Open' => $stats['open_count'], 'Investigating' => $stats['investigating_count'], 'Resolved' => $stats['resolved_count'], 'Critical' => $stats['critical_count']] as $label => $value): ?><div class="stat stat-<?= strtolower($label) ?>"><div class="stat-top"><span><?= escape($label) ?></span><i class="stat-indicator"></i></div><strong><?= (int)$value ?></strong><small><?= $label === 'Total' ? 'All recorded incidents' : ($label === 'Critical' ? 'High priority cases' : 'Current count') ?></small></div><?php endforeach; ?>
<div class="stat stat-resolution"><div class="stat-top"><span>Avg resolution</span><i class="stat-indicator"></i></div><strong><?= $stats['avg_minutes'] === null ? '—' : escape((string)$stats['avg_minutes']) . '<em> min</em>' ?></strong><small>Across resolved incidents</small></div></section>
<section class="panel"><div class="panel-title"><div><p class="eyebrow">INCIDENT LOG</p><h2>Recent incidents</h2></div><span class="record-count"><?= count($incidents) ?> shown</span></div>
<form method="get" class="filters"><label><span>Search incidents</span><input type="search" name="q" value="<?= escape($search) ?>" placeholder="Search title, service, or description"></label><label><span>Severity</span><select name="severity"><option value="">All severities</option><?php foreach (['LOW','MEDIUM','HIGH','CRITICAL'] as $option): ?><option value="<?= $option ?>" <?= $severity === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></label><label><span>Status</span><select name="status"><option value="">All statuses</option><?php foreach (['OPEN','INVESTIGATING','RESOLVED'] as $option): ?><option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></label><button class="button filter-button" type="submit">Apply filters</button><a class="text-link" href="/">Clear</a></form>
<?php if (!$incidents): ?><div class="empty"><div class="empty-icon">◎</div><h3><?= ($search !== '' || $severity !== '' || $status !== '') ? 'No matching incidents' : 'All clear for now' ?></h3><p><?= ($search !== '' || $severity !== '' || $status !== '') ? 'Try another search or clear the filters.' : 'When an incident is reported, it will appear here.' ?></p><?php if ($search === '' && $severity === '' && $status === ''): ?><a class="button" href="/create.php">Create first incident</a><?php endif; ?></div><?php else: ?><div class="table-scroll"><table><thead><tr><th>Incident</th><th>Service</th><th>Severity</th><th>Status</th><th>Created</th><th><span class="sr-only">Actions</span></th></tr></thead><tbody><?php foreach ($incidents as $incident): ?><tr><td class="incident-title"><small>INC-<?= str_pad((string)$incident['id'], 4, '0', STR_PAD_LEFT) ?></small><a href="/view.php?id=<?= (int)$incident['id'] ?>"><?= escape($incident['title']) ?></a></td><td><?= escape($incident['affected_service']) ?></td><td><span class="badge severity-<?= strtolower($incident['severity']) ?>"><?= escape($incident['severity']) ?></span></td><td><span class="badge status-<?= strtolower($incident['status']) ?>"><?= escape($incident['status']) ?></span></td><td class="date-cell"><?= escape($incident['created_at']) ?></td><td><a class="row-action" href="/view.php?id=<?= (int)$incident['id'] ?>" aria-label="View incident <?= (int)$incident['id'] ?>">↗</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
</main></body></html>
