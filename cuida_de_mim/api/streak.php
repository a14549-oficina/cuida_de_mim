<?php
/* Cálculo de Streak incluído pelo dashboard.php e outros. Pode também ser chamado via AJAX (GET /api/streak.php)*/
if (!defined('BASE')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

function calcular_streak(int $uid): array {
    $dias = db_query(
        'SELECT t.data,
                SUM(t.tomado) AS tomados,
                COUNT(*) AS total
         FROM tomas t
         INNER JOIN medicamentos m ON m.id = t.medicamento_id AND m.ativo = 1
         WHERE t.utilizador_id = ?
         GROUP BY t.data
         HAVING total > 0
         ORDER BY t.data DESC',
        [$uid]
    );

    $contagem  = 0;
    $hoje      = new DateTime('today');
    $esperado  = clone $hoje;

    foreach ($dias as $dia) {
        $data_dia = new DateTime($dia['data']);
        $completo = (int)$dia['tomados'] >= (int)$dia['total'];

        if ($data_dia == $esperado) {
            if ($completo) {
                $contagem++;
                $esperado->modify('-1 day');
            } else {
                // Hoje ainda incompleto, avança mas não conta
                $esperado->modify('-1 day');
            }
        } elseif ($data_dia < $esperado) {
            // Salto de dia — streak interrompido
            break;
        }
    }

    // Busca máximo guardado
    $saved = db_row('SELECT streak_max FROM streaks WHERE utilizador_id = ?', [$uid]);
    $max   = max((int)($saved['streak_max'] ?? 0), $contagem);

    // Persiste (silencioso — ignora erro se tabela ainda não existir)
    try {
        db_exec(
            'INSERT INTO streaks (utilizador_id, streak_atual, streak_max, ultima_data)
             VALUES (?, ?, ?, CURDATE())
             ON DUPLICATE KEY UPDATE
               streak_atual = VALUES(streak_atual),
               streak_max   = GREATEST(streak_max, VALUES(streak_max)),
               ultima_data  = VALUES(ultima_data)',
            [$uid, $contagem, $max]
        );
    } catch (Exception $e) {
        // Tabela ainda não existe — ignora, dashboard não quebra
    }

    return ['streak_atual' => $contagem, 'streak_max' => $max];
}

// Apenas responde JSON se chamado directamente via HTTP (AJAX)
// Usa CRON_MODE para distinguir de include normal
if (!defined('CRON_MODE') && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'streak.php') {
    header('Content-Type: application/json');
    if (!logged_in()) { http_response_code(401); echo json_encode(['ok' => false]); exit; }
    echo json_encode(calcular_streak(user_id()));
    exit;
}
