<?php
/*Ações de lembretes — suporta GET e POST*/

require_once __DIR__ . '/config/config.php';
$uid    = user_id();
$action = $_GET['action'] ?? $_POST['action'] ?? $_POST['acao'] ?? '';

// CSRF: verify on state-changing POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_ajax();
}
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

switch ($action) {
    case 'lida':
        if ($id) db_exec('UPDATE lembretes SET lido = 1 WHERE id = ? AND utilizador_id = ?', [$id, $uid]);
        break;
    case 'todas_lidas':
        db_exec('UPDATE lembretes SET lido = 1 WHERE utilizador_id = ?', [$uid]);
        break;
    case 'eliminar':
        if ($id) db_exec('DELETE FROM lembretes WHERE id = ? AND utilizador_id = ?', [$id, $uid]);
        break;
}

// Se for AJAX (chamado pelo JS), não redireciona
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

$ref = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/lembretes.php';
header('Location: ' . $ref);
exit;
