<?php
/* Cuida de Mim — API: Badges e dados para o JS * Retorna JSON com contagens reais da BD */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

// Tem de estar logado
if (!logged_in()) {
    echo json_encode(['lembretes_nao_lidos' => 0]);
    exit;
}

$uid = user_id();
$resp = [];

// Contagem de lembretes não lidos 
$resp['lembretes_nao_lidos'] = (int) db_row(
    'SELECT COUNT(*) as c FROM lembretes WHERE utilizador_id = ? AND lido = 0',
    [$uid]
)['c'];

// Lista de lembretes para o painel 
if (isset($_GET['lembretes'])) {
    $resp['lembretes'] = db_query(
        'SELECT id, titulo, mensagem, datahora, tipo, prioridade
         FROM lembretes
         WHERE utilizador_id = ? AND lido = 0
         ORDER BY datahora ASC
         LIMIT 6',
        [$uid]
    );
}

// Stock baixo 
if (isset($_GET['stock'])) {
    $resp['stock_baixo'] = db_query(
        'SELECT nome, quantidade FROM medicamentos
         WHERE utilizador_id = ? AND ativo = 1 AND quantidade <= 10
         ORDER BY quantidade ASC',
        [$uid]
    );
}

echo json_encode($resp);
