<?php

/*Cuida de Mim — Cron Job de Lembretes WhatsApp
 * Lê APENAS a tabela `lembretes`.
 * Consultas e medicamentos criam registos nessa tabela automaticamente,
 * por isso não é necessário ler as tabelas separadas.
 * Corre de 30 em 30 minutos via Agendador de Tarefas do Windows.
 * Execução manual: php cron_lembretes.php
 * Com detalhe:    php cron_lembretes.php --debug */

set_time_limit(120);
ini_set('display_errors', 1);
error_reporting(E_ALL);

$debug = in_array('--debug', $argv ?? []);

define('ROOT_PATH', __DIR__);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/cron/whatsapp_helper.php';

// PHP usa Europe/Lisbon (hora de verão = UTC+2... mas XAMPP usa UTC+0 no MySQL)
// Forçamos o MySQL a usar o mesmo offset que o PHP
date_default_timezone_set('Europe/Lisbon');
db()->exec("SET time_zone = '+01:00'");

$log = [];

function log_msg(string $msg): void {
    global $log, $debug;
    $linha = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    $log[] = $linha;
    if ($debug || php_sapi_name() === 'cli') {
        echo $linha . PHP_EOL;
    }
    file_put_contents(__DIR__ . '/cron/cron_log.txt', $linha . PHP_EOL, FILE_APPEND);
}

log_msg('=== Cron iniciado ===');
echo '<br>=== Cron iniciado ===';

//  LEMBRETES (tabela lembretes) 
// Procura lembretes cuja datahora cai nos próximos 30 minutos e ainda não foram enviados por WhatsApp.
// Inclui consultas e medicamentos — são inseridos nesta tabela automaticamente quando o utilizador cria uma consulta ou medicamento com lembrete.

$lembretes = db_query("
    SELECT
        l.id,
        l.utilizador_id,
        l.titulo,
        l.mensagem,
        l.datahora,
        l.tipo,
        l.prioridade,
        u.nome       AS nome_utilizador,
        u.telefone   AS telefone_utilizador,
        cfg.whatsapp_ativo,
        cfg.whatsapp_numero
    FROM lembretes l
    JOIN utilizadores u   ON u.id  = l.utilizador_id
    LEFT JOIN configuracoes cfg ON cfg.utilizador_id = l.utilizador_id
    WHERE l.whatsapp_enviado = 0
      AND l.datahora
          BETWEEN DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND DATE_ADD(NOW(), INTERVAL " . CRON_JANELA_MIN . " MINUTE)
");

log_msg('Lembretes a notificar: ' . count($lembretes));
echo '<br>Lembretes a notificar: ' . count($lembretes);

foreach ($lembretes as $l) {
    $numero = $l['whatsapp_numero'] ?: $l['telefone_utilizador'];

    if (!$numero) {
        log_msg("  [SKIP] Lembrete #{$l['id']} — utilizador sem número");
        echo "<br>  [SKIP] Lembrete #{$l['id']} — utilizador sem número";
        db_exec('UPDATE lembretes SET whatsapp_enviado = 1 WHERE id = ?', [$l['id']]);
        continue;
    }

    if (!$l['whatsapp_ativo']) {
        log_msg("  [SKIP] Lembrete #{$l['id']} — WhatsApp desativado");
        echo "<br>  [SKIP] Lembrete #{$l['id']} — WhatsApp desativado";
        db_exec('UPDATE lembretes SET whatsapp_enviado = 1 WHERE id = ?', [$l['id']]);
        continue;
    }

    $data_formatada = date('d/m/Y \à\s H:i', strtotime($l['datahora']));

    $mensagem = "Cuida de Mim\n\n"
          . "{$l['titulo']}\n\n"
          . "Olá {$l['nome_utilizador']}!\n\n"
          . "{$l['mensagem']}\n\n"
          . "Hora: {$data_formatada}\n\n"
          . "Gerado automaticamente pela\n"
          . "app Cuida de Mim";
    $resultado = enviar_whatsapp($numero, $mensagem);

    if ($resultado['sucesso']) {
        log_msg("  [OK] Lembrete #{$l['id']} ({$l['tipo']}) enviado para {$numero} (SID: {$resultado['sid']})");
        echo "<br>  [OK] Lembrete #{$l['id']} ({$l['tipo']}) enviado para {$numero} (SID: {$resultado['sid']})";

        // Se for lembrete repetível, agenda para o próximo período em vez de marcar como enviado definitivo
        $repetir = db_row('SELECT repetir FROM lembretes WHERE id = ?', [$l['id']])['repetir'] ?? '';
        if ($repetir === 'diario') {
            $proxima = (new DateTime($l['datahora'], new DateTimeZone('Europe/Lisbon')))->modify('+1 day')->format('Y-m-d H:i:s');
            // Marca como lido (some da lista) e reagenda para amanhã
            db_exec('UPDATE lembretes SET whatsapp_enviado = 0, lido = 1, datahora = ? WHERE id = ?', [$proxima, $l['id']]);
            log_msg("    → Reagendado para {$proxima} (diário) — marcado como lido");
            echo "<br>    → Reagendado para {$proxima} (diário) — marcado como lido";
        } elseif ($repetir === 'semanal') {
            $proxima = (new DateTime($l['datahora'], new DateTimeZone('Europe/Lisbon')))->modify('+7 days')->format('Y-m-d H:i:s');
            db_exec('UPDATE lembretes SET whatsapp_enviado = 0, lido = 1, datahora = ? WHERE id = ?', [$proxima, $l['id']]);
            log_msg("    → Reagendado para {$proxima} (semanal) — marcado como lido");
            echo "<br>    → Reagendado para {$proxima} (semanal) — marcado como lido";
        } elseif ($repetir === 'mensal') {
            $proxima = (new DateTime($l['datahora'], new DateTimeZone('Europe/Lisbon')))->modify('+1 month')->format('Y-m-d H:i:s');
            db_exec('UPDATE lembretes SET whatsapp_enviado = 0, lido = 1, datahora = ? WHERE id = ?', [$proxima, $l['id']]);
            log_msg("    → Reagendado para {$proxima} (mensal) — marcado como lido");
            echo "<br>    → Reagendado para {$proxima} (mensal) — marcado como lido";
        } else {
            // Lembrete único (consulta, etc.) — marca como lido e enviado
            db_exec('UPDATE lembretes SET whatsapp_enviado = 1, lido = 1 WHERE id = ?', [$l['id']]);
            log_msg("    → Marcado como lido (lembrete único)");
            echo "<br>    → Marcado como lido (lembrete único)";
        }
    } else {
        log_msg("  [ERRO] Lembrete #{$l['id']}: {$resultado['erro']}");
        echo "<br>  [ERRO] Lembrete #{$l['id']}: {$resultado['erro']}";
    }
}
echo '<br>=== Cron terminado ===';
log_msg('=== Cron terminado ===');
