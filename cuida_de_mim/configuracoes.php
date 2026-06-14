<?php
/*Cuida de Mim — Configurações */

$page_id    = 'configuracoes';
$page_title = 'Configurações';
require_once __DIR__ . '/config/config.php';
$uid = user_id();

// Garante que a tabela tem as colunas novas 
try {
    db()->exec("ALTER TABLE configuracoes
        ADD COLUMN IF NOT EXISTS whatsapp_ativo  TINYINT(1) NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS whatsapp_numero VARCHAR(20) DEFAULT NULL");
} catch (Exception $e) {  }

// Guardar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $notif_meds      = isset($_POST['notif_meds'])      ? 1 : 0;
    $notif_consultas = isset($_POST['notif_consultas']) ? 1 : 0;
    $notif_semanal   = isset($_POST['notif_semanal'])   ? 1 : 0;
    $whatsapp_ativo  = isset($_POST['whatsapp_ativo'])  ? 1 : 0;
    $whatsapp_numero = trim($_POST['whatsapp_numero'] ?? '');
    $antec_meds      = $_POST["antec_meds"]      ?? '1 hora';
    $antec_consultas = $_POST['antec_consultas'] ?? '1 dia';

    $permitidos_meds      = ['15 minutos','30 minutos','1 hora','2 horas'];
    $permitidos_consultas = ['1 hora','1 dia','2 dias','1 semana'];
    if (!in_array($antec_meds, $permitidos_meds))           $antec_meds = '1 hora';
    if (!in_array($antec_consultas, $permitidos_consultas)) $antec_consultas = '1 dia';

    // Limpa o número (remove espaços e traços)
    $whatsapp_numero = preg_replace('/[^0-9+]/', '', $whatsapp_numero);
    if ($whatsapp_numero && !str_starts_with($whatsapp_numero, '+')) {
        // Assume Portugal se não tiver prefixo
        $whatsapp_numero = '+351' . ltrim($whatsapp_numero, '0');
    }

    db_exec(
        'INSERT INTO configuracoes
            (utilizador_id, notif_meds, notif_consultas, notif_semanal, antec_meds, antec_consultas, whatsapp_ativo, whatsapp_numero)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           notif_meds=VALUES(notif_meds),
           notif_consultas=VALUES(notif_consultas),
           notif_semanal=VALUES(notif_semanal),
           antec_meds=VALUES(antec_meds),
           antec_consultas=VALUES(antec_consultas),
           whatsapp_ativo=VALUES(whatsapp_ativo),
           whatsapp_numero=VALUES(whatsapp_numero)',
        [$uid, $notif_meds, $notif_consultas, $notif_semanal,
         $antec_meds, $antec_consultas, $whatsapp_ativo, $whatsapp_numero ?: null]
    );

    // Se pediu teste de envio
    if (isset($_POST['teste_whatsapp']) && $whatsapp_ativo && $whatsapp_numero) {
        require_once __DIR__ . '/cron/whatsapp_helper.php';
        $utilizador = db_row('SELECT nome FROM utilizadores WHERE id = ?', [$uid]);
        $resultado  = enviar_whatsapp($whatsapp_numero,
            "*Cuida de Mim — Teste*\n\n"
            . "Olá {$utilizador['nome']}! \n\n"
            . "O teu WhatsApp está configurado com sucesso!\n"
            . "Vais receber os lembretes aqui. \n\n"
            . "_App Cuida de Mim_"
        );
        $teste_resultado = $resultado;
    }

    $guardado = true;
}

// Carregar configurações (ou defaults)
$cfg = db_row('SELECT * FROM configuracoes WHERE utilizador_id = ?', [$uid]) ?? [
    'notif_meds'      => 1,
    'notif_consultas' => 1,
    'notif_semanal'   => 1,
    'antec_meds'      => '1 hora',
    'antec_consultas' => '1 dia',
    'whatsapp_ativo'  => 0,
    'whatsapp_numero' => '',
];

// Número do utilizador como sugestão
$utilizador = db_row('SELECT telefone FROM utilizadores WHERE id = ?', [$uid]);

include 'includes/header.php';
?>

<div>

  <?php if (!empty($guardado)): ?>
    <div class="alert alert-success" style="margin-bottom:16px">
      <i class="fas fa-check-circle"></i> Configurações guardadas com sucesso!
    </div>
  <?php endif ?>

  <?php if (!empty($teste_resultado)): ?>
    <?php if ($teste_resultado['sucesso']): ?>
      <div class="alert alert-success" style="margin-bottom:16px">
        <i class="fab fa-whatsapp"></i> <strong>Mensagem de teste enviada!</strong>
      </div>
    <?php else: ?>
      <div class="alert alert-danger" style="margin-bottom:16px">
        <i class="fas fa-exclamation-circle"></i> <strong>Erro no envio:</strong>
        <?= htmlspecialchars($teste_resultado['erro']) ?>
        <br><small>Verifica se o número está autorizado no Sandbox da Twilio.</small>
      </div>
    <?php endif ?>
  <?php endif ?>

  <form method="POST">
    <?php echo csrf_field() ?>


    <! LINHA 1: [Notificações + Antecedência] à esq | WhatsApp à dir >
    <div class="two-col" style="align-items:start">

      <! COLUNA ESQUERDA: Notificações + Antecedência >
      <div style="display:flex;flex-direction:column;gap:20px">

        <! NOTIFICAÇÕES >
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-bell"></i> Notificações</div></div>
          <div class="card-body">

            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border)">
              <div><div style="font-weight:600;font-size:14px">Lembretes de medicamentos</div><div style="font-size:12px;color:var(--text-muted)">Alertas antes da hora da toma</div></div>
              <label class="toggle"><input type="checkbox" name="notif_meds" <?= $cfg['notif_meds'] ? 'checked' : '' ?>><span class="toggle-track"></span></label>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border)">
              <div><div style="font-weight:600;font-size:14px">Lembretes de consultas</div><div style="font-size:12px;color:var(--text-muted)">Alertas antes das consultas</div></div>
              <label class="toggle"><input type="checkbox" name="notif_consultas" <?= $cfg['notif_consultas'] ? 'checked' : '' ?>><span class="toggle-track"></span></label>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0">
              <div><div style="font-weight:600;font-size:14px">Resumo semanal</div><div style="font-size:12px;color:var(--text-muted)">Relatório automático às segundas</div></div>
              <label class="toggle"><input type="checkbox" name="notif_semanal" <?= $cfg['notif_semanal'] ? 'checked' : '' ?>><span class="toggle-track"></span></label>
            </div>

          </div>
        </div>

        <! ANTECEDÊNCIA >
        <div class="card">
          <div class="card-header"><div class="card-title"><i class="fas fa-clock"></i> Antecedência dos Lembretes</div></div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Medicamentos — avisar com antecedência</label>
              <select name="antec_meds" class="form-control">
                <?php foreach (['15 minutos','30 minutos','1 hora','2 horas'] as $op): ?>
                  <option <?= $cfg['antec_meds'] === $op ? 'selected' : '' ?>><?= $op ?></option>
                <?php endforeach ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">Consultas — avisar com antecedência</label>
              <select name="antec_consultas" class="form-control">
                <?php foreach (['1 hora','1 dia','2 dias','1 semana'] as $op): ?>
                  <option <?= $cfg['antec_consultas'] === $op ? 'selected' : '' ?>><?= $op ?></option>
                <?php endforeach ?>
              </select>
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;padding:10px">
            <i class="fas fa-save"></i> Guardar Configurações
          </button>

      </div><!fim coluna esq >

      <! WHATSAPP >
      <div class="card" style="border:2px solid #25D366">
        <div class="card-header" style="background:linear-gradient(135deg,#25D366 0%,#128C7E 100%);color:white">
          <div class="card-title" style="color:white"><i class="fab fa-whatsapp"></i> Lembretes por WhatsApp</div>
        </div>
        <div class="card-body">

          <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#166534">
            <i class="fab fa-whatsapp" style="color:#25D366"></i>
            <strong>Como funciona:</strong> A app envia mensagens WhatsApp para o teu número antes das consultas e na hora dos medicamentos.
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);margin-bottom:14px">
            <div>
              <div style="font-weight:600;font-size:14px">Ativar lembretes WhatsApp</div>
              <div style="font-size:12px;color:var(--text-muted)">Recebe alertas no WhatsApp</div>
            </div>
            <label class="toggle">
              <input type="checkbox" name="whatsapp_ativo" id="whatsapp_ativo"
                <?= $cfg['whatsapp_ativo'] ? 'checked' : '' ?>
                onchange="document.getElementById('whatsapp_campos').style.display = this.checked ? 'block' : 'none'">
              <span class="toggle-track"></span>
            </label>
          </div>

          <div id="whatsapp_campos" style="display:<?= $cfg['whatsapp_ativo'] ? 'block' : 'none' ?>">
            <div class="form-group">
              <label class="form-label"><i class="fab fa-whatsapp" style="color:#25D366"></i> Número WhatsApp</label>
              <input type="tel" name="whatsapp_numero" class="form-control" placeholder="+351912345678"
                value="<?= htmlspecialchars($cfg['whatsapp_numero'] ?? $utilizador['telefone'] ?? '') ?>"
                style="font-size:15px;font-weight:600">
              <small style="color:var(--text-muted)">Formato: +351912345678 <?= htmlspecialchars($utilizador['telefone'] ?? '') ?></small>
            </div>

            <button type="submit" name="teste_whatsapp" value="1" class="btn" style="background:#25D366;color:white;width:100%">
              <i class="fab fa-whatsapp"></i> Enviar mensagem de teste
            </button>
          </div>

        </div>
      </div>

    </div><! fim grid principal >

  </form>
</div>


<script>

</script>

<?php include 'includes/footer.php'; ?>
