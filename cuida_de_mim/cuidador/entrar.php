<?php
require_once __DIR__ . '/../config/config.php';
$uid = user_id();
$utente_id = (int)($_GET['utente'] ?? 0);

// Verifica se é mesmo cuidador desta pessoa
$relacao = db_row(
    'SELECT c.* FROM cuidadores c
     INNER JOIN utilizadores u ON u.id = c.utente_id
     WHERE c.cuidador_id = ? AND c.utente_id = ? AND c.estado = "ativo"',
    [$uid, $utente_id]
);
// Também pode ser pelo email
if (!$relacao) {
    $relacao = db_row(
        'SELECT * FROM cuidadores WHERE email_cuidador = ? AND utente_id = ? AND estado = "ativo"',
        [user()['email'], $utente_id]
    );
}

if ($relacao) {
    $_SESSION['cuidador_utente_id'] = $utente_id;
    header('Location: ../index.php'); exit;
} else {
    header('Location: index.php'); exit;
}
