<?php
/*Registar / desmarcar toma de medicamento*/
require_once __DIR__ . '/../config/config.php';
$uid    = user_id();
$med_id = (int)($_POST['med_id'] ?? 0);
$tomado = (int)($_POST['tomado'] ?? 0);
$hoje   = date('Y-m-d');

if ($med_id) {
    $med = db_row('SELECT id FROM medicamentos WHERE id = ? AND utilizador_id = ? AND ativo = 1', [$med_id, $uid]);
    if ($med) {
        db_exec(
            'INSERT INTO tomas (medicamento_id, utilizador_id, data, tomado, tomado_em)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE tomado = VALUES(tomado), tomado_em = VALUES(tomado_em)',
            [$med_id, $uid, $hoje, $tomado, $tomado ? date('Y-m-d H:i:s') : null]
        );
    }
}

// Redireciona de volta para onde veio
$ref = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php';
// Garante que o redirect fica dentro do próprio projeto
if (!str_contains($ref, $_SERVER['HTTP_HOST'])) {
    $ref = BASE_URL . '/index.php';
}
header('Location: ' . $ref);
exit;
