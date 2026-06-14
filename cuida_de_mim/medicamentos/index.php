<?php
$page_id    = 'medicamentos';
$page_title = 'Medicamentos';
require_once __DIR__ . '/../config/config.php';
$uid = user_id();

// Lê medicamentos da BD
$medicamentos = db_query(
    'SELECT * FROM medicamentos WHERE utilizador_id = ? AND ativo = 1 ORDER BY horario',
    [$uid]
);
$total    = count($medicamentos);
$diarios  = count(array_filter($medicamentos, fn($m) => $m['frequencia'] === 'diário'));
$alertas  = count(array_filter($medicamentos, fn($m) => $m['lembrete']));

// Guardar novo medicamento (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'guardar') {
    csrf_verify();
    $nome      = trim($_POST['nome'] ?? '');
    $edit_id   = (int)($_POST['edit_id'] ?? 0);

    if ($nome) {
        $dados = [
            trim($_POST['dosagem']    ?? ''),
            $_POST['forma']           ?? 'comprimido',
            ($_POST['horario_hora'] ?? '08:00') . ':00',
            $_POST['frequencia']      ?? 'diário',
            (int)($_POST['quantidade'] ?? 30),
            trim($_POST['instrucoes'] ?? ''),
            isset($_POST['lembrete']) ? 1 : 0,
        ];

        if ($edit_id) {
            db_exec(
                'UPDATE medicamentos SET nome=?, dosagem=?, forma=?, horario=?, frequencia=?, quantidade=?, instrucoes=?, lembrete=?
                 WHERE id=? AND utilizador_id=?',
                array_merge([$nome], $dados, [$edit_id, $uid])
            );
            if (isset($_POST['lembrete'])) {
                $horario_str  = ($_POST['horario_hora'] ?? '08:00') . ':00';
                $datahora_lem = ($_POST['horario_data'] ?? date('Y-m-d')) . ' ' . $horario_str;
                $lem_exists = db_row('SELECT id FROM lembretes WHERE utilizador_id=? AND tipo="medicamento" AND titulo=?',
                    [$uid, 'Tomar '.$nome]);
                if ($lem_exists) {
                    db_exec('UPDATE lembretes SET datahora=?, whatsapp_enviado=0 WHERE id=?',
                        [$datahora_lem, $lem_exists['id']]);
                } else {
                    db_exec('INSERT INTO lembretes (utilizador_id, titulo, mensagem, datahora, tipo, prioridade, repetir) VALUES (?, ?, ?, ?, "medicamento", "media", "diario")',
                        [$uid, 'Tomar '.$nome, 'Hora de tomar '.$nome, $datahora_lem]);
                }
            } else {
                db_exec('DELETE FROM lembretes WHERE utilizador_id=? AND tipo="medicamento" AND titulo=? AND whatsapp_enviado=0',
                    [$uid, 'Tomar '.$nome]);
            }
        } else {
            $novo_id = db_insert(
                'INSERT INTO medicamentos (utilizador_id, nome, dosagem, forma, horario, frequencia, quantidade, instrucoes, lembrete)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array_merge([$uid, $nome], $dados)
            );
            db_exec(
                'INSERT IGNORE INTO tomas (medicamento_id, utilizador_id, data, tomado) VALUES (?, ?, CURDATE(), 0)',
                [$novo_id, $uid]
            );
            if (isset($_POST['lembrete'])) {
                $horario_str  = ($_POST['horario_hora'] ?? '08:00') . ':00';
                $datahora_lem = ($_POST['horario_data'] ?? date('Y-m-d')) . ' ' . $horario_str;
                db_exec(
                    'INSERT INTO lembretes (utilizador_id, titulo, mensagem, datahora, tipo, prioridade, repetir)
                     VALUES (?, ?, ?, ?, "medicamento", "media", "diario")',
                    [$uid, 'Tomar '.$nome, 'Hora de tomar '.$nome.($dados[0] ? ' ('.$dados[0].')' : ''), $datahora_lem]
                );
            }
        }
    }
    header('Location: index.php');
    exit;
}

// Remover medicamento (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'remover') {
    csrf_verify();
    $rid = (int)$_POST['remover_id'];
    $med = db_row('SELECT nome FROM medicamentos WHERE id=? AND utilizador_id=?', [$rid, $uid]);
    if ($med) {
        db_exec('DELETE FROM lembretes WHERE utilizador_id=? AND tipo="medicamento" AND titulo=?',
            [$uid, 'Tomar '.$med['nome']]);
    }
    db_exec('UPDATE medicamentos SET ativo = 0 WHERE id = ? AND utilizador_id = ?', [$rid, $uid]);
    header('Location: index.php');
    exit;
}

// Remover medicamento (GET legacy)
if (isset($_GET['remover'])) {
    $rid = (int)$_GET['remover'];
    $med = db_row('SELECT nome FROM medicamentos WHERE id=? AND utilizador_id=?', [$rid, $uid]);
    if ($med) {
        db_exec('DELETE FROM lembretes WHERE utilizador_id=? AND tipo="medicamento" AND titulo=?',
            [$uid, 'Tomar '.$med['nome']]);
    }
    db_exec('UPDATE medicamentos SET ativo = 0 WHERE id = ? AND utilizador_id = ?', [$rid, $uid]);
    header('Location: index.php');
    exit;
}

// Carregar dados para edição
$edit_med = null;
if (isset($_GET['editar'])) {
    $edit_med = db_row('SELECT * FROM medicamentos WHERE id = ? AND utilizador_id = ?', [(int)$_GET['editar'], $uid]);
}

include '../includes/header.php';
?>

<div style="display:flex;justify-content:flex-end;margin-bottom:16px">
  <button class="btn btn-primary" onclick="document.getElementById('modal-med-php').classList.add('open')">
    <i class="fas fa-plus"></i> Novo Medicamento
  </button>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr)">
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Total Ativos</div><div class="stat-card-icon icon-blue"><i class="fas fa-pills"></i></div></div><div class="stat-card-value"><?php echo $total ?></div><div class="stat-card-sub">medicamentos</div></div>
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Diários</div><div class="stat-card-icon icon-green"><i class="fas fa-clock"></i></div></div><div class="stat-card-value"><?php echo $diarios ?></div><div class="stat-card-sub">tomar todos os dias</div></div>
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Com Alertas</div><div class="stat-card-icon icon-yellow"><i class="fas fa-bell"></i></div></div><div class="stat-card-value"><?php echo $alertas ?></div><div class="stat-card-sub">lembretes ativos</div></div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-pills"></i> Os Meus Medicamentos</div></div>
  <?php if (!$medicamentos): ?>
    <div class="empty-state" style="padding:60px 20px">
      <div class="empty-icon"><i class="fas fa-pills"></i></div>
      <div class="empty-title">Sem medicamentos</div>
      <div class="empty-text">Adicione o seu primeiro medicamento para começar a acompanhar as tomas.</div>
      <button class="btn btn-primary" onclick="document.getElementById('modal-med-php').classList.add('open')"><i class="fas fa-plus"></i> Adicionar Medicamento</button>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Medicamento</th><th>Dosagem</th><th>Forma</th><th>Horário</th><th>Frequência</th><th>Stock</th><th>Lembrete</th><th>Ações</th></tr></thead>
      <tbody>
        <?php foreach ($medicamentos as $m): ?>
          <?php $sc = $m['quantidade'] <= 5 ? 'badge-danger' : ($m['quantidade'] <= 10 ? 'badge-warning' : 'badge-success') ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($m['nome']) ?></strong><?php if ($m['instrucoes']): ?><div style="font-size:11px;color:var(--text-muted)"><?php echo htmlspecialchars(substr($m['instrucoes'],0,50)) ?></div><?php endif ?></td>
            <td><?php echo htmlspecialchars($m['dosagem'] ?: '—') ?></td>
            <td><span class="badge badge-gray"><?php echo $m['forma'] ?></span></td>
            <td><span class="badge badge-info"><?php echo substr($m['horario'],0,5) ?></span></td>
            <td><span class="badge badge-gray"><?php echo $m['frequencia'] ?></span></td>
            <td><span class="badge <?php echo $sc ?>"><?php echo $m['quantidade'] ?> un.</span></td>
            <td><?php echo $m['lembrete'] ? '<span class="badge badge-success"><i class="fas fa-bell"></i> Ativo</span>' : '<span class="badge badge-gray">Sem alerta</span>' ?></td>
            <td style="display:flex;gap:6px">
              <a href="?editar=<?php echo $m['id'] ?>" class="btn btn-sm btn-ghost"><i class="fas fa-edit"></i></a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Remover este medicamento?')">
                <?php echo csrf_field() ?>
                <input type="hidden" name="acao" value="remover">
                <input type="hidden" name="remover_id" value="<?php echo $m['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?php endif ?>
</div>

<! MODAL PHP (formulário real, sem JS state) >
<div class="modal-overlay <?php echo ($edit_med || isset($_GET['novo'])) ? 'open' : '' ?>" id="modal-med-php">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
      <h2 style="font-size:17px;font-weight:700"><?php echo $edit_med ? 'Editar Medicamento' : 'Novo Medicamento' ?></h2>
      <a href="index.php" style="font-size:22px;color:var(--text-muted);text-decoration:none">×</a>
    </div>
    <form method="POST" action="index.php">
      <?php echo csrf_field() ?>
      <input type="hidden" name="acao" value="guardar">
      <input type="hidden" name="edit_id" value="<?php echo $edit_med['id'] ?? '' ?>">
      <div class="form-group" style="grid-column:1/-1">
        <label class="form-label">Nome *</label>
        <input type="text" name="nome" class="form-control" placeholder="ex: Paracetamol" value="<?php echo htmlspecialchars($edit_med['nome'] ?? '') ?>" required>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Dosagem</label>
          <input type="text" name="dosagem" class="form-control" placeholder="500mg" value="<?php echo htmlspecialchars($edit_med['dosagem'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Forma</label>
          <select name="forma" class="form-control">
            <?php foreach (['comprimido','xarope','injetavel','pomada','gotas','outro'] as $f): ?>
              <option <?php echo ($edit_med['forma'] ?? 'comprimido') === $f ? 'selected' : '' ?>><?php echo $f ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Data *</label>
            <input type="date" name="horario_data" class="form-control"
              value="<?php echo isset($edit_med['horario']) ? date('Y-m-d') : date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Hora *</label>
            <input type="time" name="horario_hora" class="form-control"
              value="<?php echo isset($edit_med['horario']) ? substr($edit_med['horario'], 0, 5) : '08:00' ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Frequência</label>
          <select name="frequencia" class="form-control">
            <?php foreach (['diário','dias alternados','semanal','mensal'] as $f): ?>
              <option <?php echo ($edit_med['frequencia'] ?? 'diário') === $f ? 'selected' : '' ?>><?php echo $f ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Stock (unidades)</label>
          <input type="number" name="quantidade" class="form-control" min="1" value="<?php echo $edit_med['quantidade'] ?? 30 ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Instruções</label>
        <textarea name="instrucoes" class="form-control" placeholder="ex: Tomar com água às refeições..."><?php echo htmlspecialchars($edit_med['instrucoes'] ?? '') ?></textarea>
      </div>
      <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:16px">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
          <input type="checkbox" name="lembrete" <?php echo ($edit_med['lembrete'] ?? 1) ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--primary)">
          <span style="font-size:13.5px;font-weight:600"><i class="fas fa-bell" style="color:var(--primary);margin-right:4px"></i> Criar lembrete automático</span>
        </label>
      </div>
      <div style="display:flex;gap:12px">
        <button type="submit" class="btn btn-primary" style="flex:1;padding:12px"><i class="fas fa-save"></i> Guardar</button>
        <a href="index.php" class="btn btn-ghost" style="flex:1;padding:12px;text-align:center">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
