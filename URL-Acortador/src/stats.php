<?php
// src/stats.php
// GET /stats/{code}  →  Devuelve estadísticas de una URL corta
// $code viene definido desde index.php

$db = getDB();

// ── 1. Buscar el código ───────────────────────────────────────────────────
$stmt = $db->prepare('SELECT * FROM short_urls WHERE code = ?');
$stmt->execute([$code]);
$row = $stmt->fetch();

if (!$row) {
    jsonResponse(['error' => 'Código no encontrado'], 404);
}

// ── 2. Visitas por día (últimos 30 días) ──────────────────────────────────
$stmtDaily = $db->prepare('
    SELECT DATE(visited_at) AS day, COUNT(*) AS visits
    FROM visits
    WHERE url_id = ? AND visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(visited_at)
    ORDER BY day ASC
');
$stmtDaily->execute([$row['id']]);
$visitsByDay = $stmtDaily->fetchAll();

// ── 3. Últimos 10 accesos ─────────────────────────────────────────────────
$stmtRecent = $db->prepare('
    SELECT visitor_ip, user_agent, referer, visited_at
    FROM visits
    WHERE url_id = ?
    ORDER BY visited_at DESC
    LIMIT 10
');
$stmtRecent->execute([$row['id']]);
$recentAccesses = $stmtRecent->fetchAll();

// ── 4. Estado actual de la URL ────────────────────────────────────────────
$status = 'active';
if (!$row['is_active']) {
    $status = 'disabled';
} elseif ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
    $status = 'expired';
} elseif ($row['max_uses'] !== null && $row['visit_count'] >= $row['max_uses']) {
    $status = 'limit_reached';
}

// ── 5. Construir respuesta ────────────────────────────────────────────────
jsonResponse([
    'code'         => $row['code'],
    'short_url'    => BASE_URL . '/' . $row['code'],
    'original_url' => $row['original_url'],
    'status'       => $status,
    'created_at'   => $row['created_at'],
    'expires_at'   => $row['expires_at'],
    'max_uses'     => $row['max_uses'] ? (int) $row['max_uses'] : null,
    'statistics'   => [
        'total_visits'   => (int) $row['visit_count'],
        'visits_by_day'  => array_map(fn($v) => [
            'date'   => $v['day'],
            'visits' => (int) $v['visits'],
        ], $visitsByDay),
        'recent_accesses' => array_map(fn($v) => [
            'ip'         => $v['visitor_ip'],
            'user_agent' => $v['user_agent'],
            'referer'    => $v['referer'],
            'visited_at' => $v['visited_at'],
        ], $recentAccesses),
    ],
]);
