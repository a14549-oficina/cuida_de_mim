<?php
/*Cuida de Mim — Cron: Atualiza streaks de todos os utilizadores
 *Executar diariamente às 00:05:
 *5 0 * * * php /caminho/para/cuida_de_mim/cron/atualizar_streaks.php*/
 
define('CRON_MODE', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/api/streak.php';

$utilizadores = db_query('SELECT id FROM utilizadores');
$ok = 0;
foreach ($utilizadores as $u) {
    calcular_streak((int)$u['id']);
    $ok++;
}
echo date('Y-m-d H:i:s') . " — Streaks atualizados para {$ok} utilizador(es).\n";
