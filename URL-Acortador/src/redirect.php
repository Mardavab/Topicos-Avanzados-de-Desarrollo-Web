<?php
// src/redirect.php
// GET /{code}  →  Redirige a la URL original registrando la visita
// $code viene definido desde index.php

$db = getDB();

// ── 1. Buscar el código ───────────────────────────────────────────────────
$stmt = $db->prepare('SELECT * FROM short_urls WHERE code = ?');
$stmt->execute([$code]);
$row = $stmt->fetch();

if (!$row) {
    jsonResponse(['error' => 'Código no encontrado'], 404);
}

// ── 2. Verificar si está activo ───────────────────────────────────────────
if (!$row['is_active']) {
    jsonResponse(['error' => 'Esta URL ha sido desactivada'], 410);
}

// ── 3. Verificar expiración ───────────────────────────────────────────────
if ($row['expires_at'] !== null && strtotime($row['expires_at']) < time()) {
    jsonResponse([
        'error'      => 'Esta URL corta ha expirado',
        'expired_at' => $row['expires_at'],
    ], 410);
}

// ── 4. Verificar máximo de usos ───────────────────────────────────────────
if ($row['max_uses'] !== null && $row['visit_count'] >= $row['max_uses']) {
    jsonResponse([
        'error'    => 'Esta URL ha alcanzado su límite máximo de usos',
        'max_uses' => $row['max_uses'],
    ], 410);
}

// ── 5. Registrar la visita ────────────────────────────────────────────────
$visitorIp = getClientIP();
$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);
$referer   = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 2048);

try {
    // Insertar visita en la tabla de estadísticas
    $db->prepare('
        INSERT INTO visits (url_id, visitor_ip, user_agent, referer)
        VALUES (?, ?, ?, ?)
    ')->execute([$row['id'], $visitorIp, $userAgent ?: null, $referer ?: null]);

    // Incrementar contador total (más rápido que COUNT(*) en cada consulta)
    $db->prepare('UPDATE short_urls SET visit_count = visit_count + 1 WHERE id = ?')
       ->execute([$row['id']]);

} catch (PDOException $e) {
    // Loggeamos el error pero igual hacemos la redirección
    error_log('Error registrando visita: ' . $e->getMessage());
}

// ── 6. Redirigir ──────────────────────────────────────────────────────────
// 302 (temporal) para que los navegadores no cacheen la redirección,
// permitiendo que las estadísticas siempre se registren correctamente.
header('Location: ' . $row['original_url'], true, 302);
exit;
