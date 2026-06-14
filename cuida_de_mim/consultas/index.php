<?php
$page_id    = 'consultas';
$page_title = 'Consultas';
require_once __DIR__ . '/../config/config.php';
$uid = user_id();

// Agendar nova consulta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'agendar') {
    $medico   = trim($_POST['medico'] ?? '');
    $datahora = $_POST['datahora'] ?? '';
    if ($medico && $datahora) {
        $cid = db_insert(
            'INSERT INTO consultas (utilizador_id, medico, especialidade, local, datahora, notas, lembrete_min)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$uid, $medico, $_POST['especialidade'] ?? 'Medicina Geral', trim($_POST['local'] ?? ''), $datahora, trim($_POST['notas'] ?? ''), ($_POST['lembrete'] !== '') ? (int)$_POST['lembrete'] : null]
        );
        $lem_min = (int)($_POST['lembrete'] ?? 0);
        if ($lem_min > 0) {
            $lem_dt = date('Y-m-d H:i:s', strtotime($datahora) - $lem_min * 60);
            db_exec('INSERT INTO lembretes (utilizador_id, titulo, mensagem, datahora, tipo, prioridade, repetir) VALUES (?, ?, ?, ?, "consulta", "alta", "")',
                [$uid, 'Consulta: '.$medico, 'Consulta de '.($_POST['especialidade'] ?? '').' com '.$medico.($_POST['local'] ? ' em '.$_POST['local'] : ''), $lem_dt]);
        }
    }
    header('Location: index.php'); exit;
}

// Editar consulta existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar') {
    $id       = (int)($_POST['id'] ?? 0);
    $medico   = trim($_POST['medico'] ?? '');
    $datahora = $_POST['datahora'] ?? '';
    if ($id && $medico && $datahora) {
        $novo_lembrete_min = ($_POST['lembrete'] !== '') ? (int)$_POST['lembrete'] : null;

        // Busca a consulta atual para comparar data/hora e lembrete_min
        $atual = db_row('SELECT datahora, lembrete_min FROM consultas WHERE id=? AND utilizador_id=?', [$id, $uid]);

        // Só repõe lembrete_enviado=0 se a data/hora ou o lembrete_min mudou
        // Assim o cron não volta a enviar um lembrete que já foi enviado
        $datahora_mudou = $atual && date('Y-m-d H:i', strtotime($atual['datahora'])) !== date('Y-m-d H:i', strtotime($datahora));
        $lembrete_mudou = $atual && $atual['lembrete_min'] != $novo_lembrete_min;

        if ($datahora_mudou || $lembrete_mudou) {
            db_exec(
                'UPDATE consultas SET medico=?, especialidade=?, local=?, datahora=?, notas=?, lembrete_min=?, lembrete_enviado=0 WHERE id=? AND utilizador_id=?',
                [$medico, $_POST['especialidade'] ?? 'Medicina Geral', trim($_POST['local'] ?? ''), $datahora, trim($_POST['notas'] ?? ''), $novo_lembrete_min, $id, $uid]
            );
            // Atualiza lembrete na tabela lembretes se existir
            if ($novo_lembrete_min > 0) {
                $nova_lem_dt = date('Y-m-d H:i:s', strtotime($datahora) - $novo_lembrete_min * 60);
                $lem = db_row("SELECT id FROM lembretes WHERE utilizador_id=? AND tipo='consulta' AND titulo=?",
                    [$uid, 'Consulta: '.$medico]);
                if ($lem) {
                    db_exec('UPDATE lembretes SET datahora=?, whatsapp_enviado=0 WHERE id=?', [$nova_lem_dt, $lem['id']]);
                }
            }
        } else {
            db_exec(
                'UPDATE consultas SET medico=?, especialidade=?, local=?, datahora=?, notas=?, lembrete_min=? WHERE id=? AND utilizador_id=?',
                [$medico, $_POST['especialidade'] ?? 'Medicina Geral', trim($_POST['local'] ?? ''), $datahora, trim($_POST['notas'] ?? ''), $novo_lembrete_min, $id, $uid]
            );
        }
    }
    header('Location: index.php'); exit;
}

// Remover consulta
if (isset($_GET['remover'])) {
    $cid = (int)$_GET['remover'];
    // Remove também o lembrete associado a esta consulta
    $c = db_row('SELECT medico, datahora FROM consultas WHERE id=? AND utilizador_id=?', [$cid, $uid]);
    if ($c) {
        db_exec("DELETE FROM lembretes WHERE utilizador_id=? AND tipo='consulta' AND datahora <= ? AND titulo LIKE ?",
            [$uid, $c['datahora'], 'Consulta: '.$c['medico'].'%']);
    }
    db_exec('DELETE FROM consultas WHERE id = ? AND utilizador_id = ?', [$cid, $uid]);
    header('Location: index.php'); exit;
}

// Carregar consulta para editar 
$editar = null;
if (isset($_GET['editar'])) {
    $editar = db_row('SELECT * FROM consultas WHERE id=? AND utilizador_id=?', [(int)$_GET['editar'], $uid]);
}

$consultas = db_query('SELECT * FROM consultas WHERE utilizador_id = ? AND datahora >= NOW() ORDER BY datahora ASC', [$uid]);
$especialidades = ['Medicina Geral','Cardiologia','Dermatologia','Oftalmologia','Ortopedia','Pediatria','Ginecologia','Urologia','Neurologia','Psiquiatria','Dentista','Fisioterapia'];

include '../includes/header.php';
?>

<div class="two-col">

  <!-- FORMULÁRIO -->
  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <?php if ($editar): ?>
          <i class="fas fa-edit"></i> Editar Consulta
        <?php else: ?>
          <i class="fas fa-calendar-plus"></i> Nova Consulta
        <?php endif ?>
      </div>
    </div>
    <div class="card-body">
      <form method="POST" action="index.php">
      <?php echo csrf_field() ?>
        <input type="hidden" name="acao" value="<?php echo $editar ? 'editar' : 'agendar' ?>">
        <?php if ($editar): ?>
          <input type="hidden" name="id" value="<?php echo $editar['id'] ?>">
        <?php endif ?>

        <div class="form-group">
          <label class="form-label">Médico / Clínica *</label>
          <input type="text" name="medico" class="form-control" placeholder="Nome do médico" required
                 value="<?php echo htmlspecialchars($editar['medico'] ?? '') ?>">
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Especialidade *</label>
            <select name="especialidade" class="form-control">
              <?php foreach ($especialidades as $e): ?>
                <option <?php echo (($editar['especialidade'] ?? '') === $e) ? 'selected' : '' ?>><?php echo $e ?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Local</label>
            <input type="text" name="local" class="form-control" placeholder="Hospital / Clínica"
                   value="<?php echo htmlspecialchars($editar['local'] ?? '') ?>">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Data e Hora *</label>
            <input type="datetime-local" name="datahora" class="form-control" required
                   value="<?php echo $editar ? date('Y-m-d\TH:i', strtotime($editar['datahora'])) : date('Y-m-d\TH:i', strtotime('+1 day')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Criar Lembrete</label>
            <select name="lembrete" class="form-control">
              <option value="">Sem lembrete</option>
              <?php foreach ([60=>'1 hora antes', 1440=>'1 dia antes', 2880=>'2 dias antes', 10080=>'1 semana antes'] as $v => $l): ?>
                <option value="<?php echo $v ?>" <?php echo (($editar['lembrete_min'] ?? '') == $v) ? 'selected' : '' ?>><?php echo $l ?></option>
              <?php endforeach ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Notas</label>
          <textarea name="notas" class="form-control" placeholder="Sintomas, motivo..."><?php echo htmlspecialchars($editar['notas'] ?? '') ?></textarea>
        </div>
        <div style="display:flex;gap:12px">
          <button type="submit" class="btn btn-primary" style="flex:1;padding:12px">
            <i class="fas fa-<?php echo $editar ? 'save' : 'calendar-check' ?>"></i>
            <?php echo $editar ? ' Guardar Alterações' : ' Agendar' ?>
          </button>
          <a href="index.php" class="btn btn-ghost" style="flex:1;padding:12px;text-align:center">Cancelar</a>
        </div>
      </form>
    </div>
  </div>

  <!-- LISTA DE CONSULTAS -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-calendar"></i> Próximas Consultas</div></div>
    <div style="padding:12px 16px;font-size:13px">
      <?php if (!$consultas): ?>
        <div class="empty-state" style="padding:40px">
          <div class="empty-icon"><i class="fas fa-calendar-times"></i></div>
          <div class="empty-title">Sem consultas agendadas</div>
          <div class="empty-text">Adicione a sua próxima consulta.</div>
        </div>
      <?php else: ?>
        <?php foreach ($consultas as $c): ?>
          <div class="activity-item" style="<?php echo ($editar && $editar['id'] == $c['id']) ? 'background:#eff6ff;border-radius:8px;' : '' ?>">
            <div class="activity-avatar" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
              <i class="fas fa-stethoscope" style="font-size:12px"></i>
            </div>
            <div class="activity-text">
              <strong><?php echo htmlspecialchars($c['medico']) ?></strong><br>
              <span style="font-size:12px;color:var(--text-muted)">
                <?php echo htmlspecialchars($c['especialidade']) ?>
                <?php echo $c['local'] ? ' · '.htmlspecialchars($c['local']) : '' ?>
              </span>
            </div>
            <div class="activity-time" style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
              <div style="font-weight:600;font-size:12px"><?php echo date('d/m', strtotime($c['datahora'])) ?></div>
              <div style="font-size:11px;color:var(--text-muted)"><?php echo date('H:i', strtotime($c['datahora'])) ?></div>
              <div style="display:flex;gap:4px;margin-top:2px">
                <a href="partilhar.php?id=<?php echo $c['id'] ?>" class="btn btn-xs" style="background:#d1fae5;color:#065f46;border:none;padding:3px 7px;border-radius:5px;font-size:11px" title="Partilhar"><i class="fas fa-share-alt"></i></a>
                <a href="?editar=<?php echo $c['id'] ?>" class="btn btn-xs" style="background:#dbeafe;color:#1d4ed8;border:none;padding:3px 7px;border-radius:5px;font-size:11px">
                  <i class="fas fa-edit"></i>
                </a>
                <a href="?remover=<?php echo $c['id'] ?>" class="btn btn-xs btn-danger" style="padding:3px 7px;font-size:11px" onclick="return confirm('Remover consulta?')">
                  <i class="fas fa-times"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach ?>
      <?php endif ?>
    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
