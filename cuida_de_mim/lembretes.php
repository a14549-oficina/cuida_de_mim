<?php
$page_id    = 'lembretes';
$page_title = 'Lembretes';
require_once __DIR__ . '/config/config.php';
$uid = user_id();

//  Ações POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $titulo   = trim($_POST['titulo'] ?? '');
        $mensagem = trim($_POST['mensagem'] ?? '');
        $datahora = $_POST['datahora'] ?? '';
        if ($titulo && $mensagem && $datahora) {
            db_exec(
                'INSERT INTO lembretes (utilizador_id, titulo, mensagem, datahora, tipo, prioridade, repetir)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $uid, $titulo, $mensagem, $datahora,
                    $_POST['tipo']      ?? 'geral',
                    $_POST['prioridade']?? 'media',
                    $_POST['repetir']   ?? '',
                ]
            );
        }
    }

    if ($acao === 'editar') {
        $id       = (int)($_POST['id'] ?? 0);
        $titulo   = trim($_POST['titulo'] ?? '');
        $mensagem = trim($_POST['mensagem'] ?? '');
        $data     = trim($_POST['datahora_data'] ?? '');
        $hora     = trim($_POST['datahora_hora'] ?? '');
        $datahora = $data && $hora ? $data . ' ' . $hora . ':00' : null;
        if ($id > 0 && $titulo && $datahora) {
            db_exec(
                'UPDATE lembretes SET titulo=?, mensagem=?, datahora=?, tipo=?, prioridade=?, repetir=?
                 WHERE id=? AND utilizador_id=?',
                [
                    $titulo,
                    $mensagem ?: $titulo,
                    $datahora,
                    $_POST['tipo']       ?? 'geral',
                    $_POST['prioridade'] ?? 'media',
                    $_POST['repetir']    ?: null,
                    $id, $uid,
                ]
            );
        }
        header('Location: lembretes.php?ok=1' . (isset($_POST['filtro']) ? '&f='.$_POST['filtro'] : ''));
        exit;
    }

    if ($acao === 'lida' && isset($_POST['id'])) {
        db_exec('UPDATE lembretes SET lido = 1 WHERE id = ? AND utilizador_id = ?', [(int)$_POST['id'], $uid]);
    }

    if ($acao === 'todas_lidas') {
        db_exec('UPDATE lembretes SET lido = 1 WHERE utilizador_id = ?', [$uid]);
    }

    if ($acao === 'eliminar' && isset($_POST['id'])) {
        db_exec('DELETE FROM lembretes WHERE id = ? AND utilizador_id = ?', [(int)$_POST['id'], $uid]);
    }

    header('Location: lembretes.php' . (isset($_POST['filtro']) ? '?f='.$_POST['filtro'] : ''));
    exit;
}

// Filtro activo 
$filtro = $_GET['f'] ?? 'todos';

// Dados da BD 
$todos = db_query(
    'SELECT * FROM lembretes WHERE utilizador_id = ? AND (lido = 0 OR (lido = 1 AND whatsapp_enviado = 0 AND datahora > NOW())) ORDER BY lido ASC, datahora ASC',
    [$uid]
);

// Aplica filtro
$lista = array_filter($todos, function($l) use ($filtro) {
    if ($filtro === 'todos')    return true;
    if ($filtro === 'urgente')  return $l['prioridade'] === 'urgente';
    return $l['tipo'] === $filtro;
});

$total      = count($todos);
$ativos     = count(array_filter($todos, fn($l) => !$l['lido']));
$urgentes   = count(array_filter($todos, fn($l) => $l['prioridade'] === 'urgente' && !$l['lido']));
$concluidos = count(array_filter($todos, fn($l) => $l['lido']));

// Cores e ícones por tipo
$cores  = ['medicamento'=>'#3b82f6','consulta'=>'#f59e0b','exercicio'=>'#10b981','urgente'=>'#ef4444','geral'=>'#7c3aed'];
$icones = ['medicamento'=>'fa-pills','consulta'=>'fa-stethoscope','exercicio'=>'fa-running','urgente'=>'fa-exclamation-triangle','geral'=>'fa-bell'];

function badge_prioridade($p) {
    return match($p) {
        'baixa'   => '<span class="badge badge-gray"    style="font-size:10px">↓ Baixa</span>',
        'media'   => '<span class="badge badge-info"    style="font-size:10px">→ Média</span>',
        'alta'    => '<span class="badge badge-warning" style="font-size:10px">↑ Alta</span>',
        'urgente' => '<span class="badge badge-danger"  style="font-size:10px">Urgente</span>',
        default   => ''
    };
}
function badge_tipo($t) {
    return match($t) {
        'medicamento' => '<span class="badge badge-info"    style="font-size:10px">Med.</span>',
        'consulta'    => '<span class="badge badge-warning" style="font-size:10px">Consulta</span>',
        'agua'        => '<span class="badge badge-info"    style="font-size:10px">Água</span>',
        'exercicio'   => '<span class="badge badge-success" style="font-size:10px">Exerc.</span>',
        'urgente'     => '<span class="badge badge-danger"  style="font-size:10px">Urgente</span>',
        default       => '<span class="badge badge-gray"    style="font-size:10px">Geral</span>',
    };
}
function fmt_datahora($dt) {
    $tz   = new DateTimeZone('Europe/Lisbon');
    $d    = new DateTime($dt, $tz);
    $now  = new DateTime('now', $tz);
    $mins = (int)(($d->getTimestamp() - $now->getTimestamp()) / 60);
    if (abs($mins) < 60)   return $mins < 0 ? abs($mins).'min atrás' : 'em '.$mins.'min';
    if (abs($mins) < 1440) return $mins < 0 ? round(abs($mins)/60).'h atrás' : 'em '.round($mins/60).'h';
    return $d->format('d/m/Y H:i');
}

include 'includes/header.php';
?>

<! NOVO LEMBRETE >
<div style="display:flex;justify-content:flex-end;margin-bottom:16px">
  <button class="btn btn-primary" onclick="abrirModalNovo()">
    <i class="fas fa-plus"></i> Novo Lembrete
  </button>
</div>

<! STATS >
<div class="stats-grid four-cols">
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Total</div><div class="stat-card-icon icon-purple"><i class="fas fa-bell"></i></div></div><div class="stat-card-value"><?php echo $total ?></div><div class="stat-card-sub">lembretes</div></div>
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Por ler</div><div class="stat-card-icon icon-blue"><i class="fas fa-bell"></i></div></div><div class="stat-card-value"><?php echo $ativos ?></div><div class="stat-card-sub">pendentes</div></div>
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Urgentes</div><div class="stat-card-icon icon-red"><i class="fas fa-exclamation"></i></div></div><div class="stat-card-value"><?php echo $urgentes ?></div><div class="stat-card-sub">não lidos</div></div>
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Concluídos</div><div class="stat-card-icon icon-green"><i class="fas fa-check"></i></div></div><div class="stat-card-value"><?php echo $concluidos ?></div><div class="stat-card-sub">lidos</div></div>
</div>

<! FILTROS >
<div class="pill-tabs">
  <?php
  $filtros = ['todos'=>'Todos','medicamento'=>'Medicamentos','consulta'=>'Consultas','geral'=>'Geral','urgente'=>'Urgentes'];
  foreach ($filtros as $key => $label):
  ?>
    <a href="?f=<?php echo $key ?>" class="pill-tab <?php echo $filtro===$key?'active':'' ?>" style="text-decoration:none"><?php echo $label ?></a>
  <?php endforeach ?>
</div>

<! LISTA >
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-bell"></i> Os Meus Lembretes</div>
    <?php if ($ativos > 0): ?>
    <form method="POST" style="display:inline">
      <?php echo csrf_field() ?>
      <input type="hidden" name="acao" value="todas_lidas">
      <input type="hidden" name="filtro" value="<?php echo $filtro ?>">
      <button type="submit" class="btn btn-ghost btn-sm"><i class="fas fa-check-double"></i> Todas lidas</button>
    </form>
    <?php endif ?>
  </div>

  <?php if (!$lista): ?>
    <div class="empty-state" style="padding:60px 20px">
      <div class="empty-icon"><i class="fas fa-bell-slash"></i></div>
      <div class="empty-title">Nenhum lembrete</div>
      <div class="empty-text">Clique em "+ Novo Lembrete" para criar o primeiro.</div>
    </div>
  <?php else: ?>
    <?php foreach ($lista as $l):
      $cor   = $cores[$l['tipo']]  ?? '#7c3aed';
      $icone = $icones[$l['tipo']] ?? 'fa-bell';
      $cls   = $l['lido'] ? 'lido' : ($l['prioridade']==='urgente' ? 'urgente' : 'nao-lido');
    ?>
    <div class="lembrete-item <?php echo $cls ?>" style="overflow:hidden">
      <div style="display:flex;align-items:flex-start;gap:10px">
        <div class="activity-avatar" style="background:<?php echo $cor ?>;flex-shrink:0;width:36px;height:36px;min-width:36px">
          <i class="fas <?php echo $icone ?>" style="font-size:13px"></i>
        </div>
        <div style="flex:1;min-width:0;overflow:hidden">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
            <span style="font-weight:700;font-size:14px"><?php echo htmlspecialchars($l['titulo']) ?></span>
            <?php echo badge_prioridade($l['prioridade']) ?>
            <?php echo badge_tipo($l['tipo']) ?>
            <?php if ($l['lido']): ?><span class="badge badge-success" style="font-size:10px">✓ Lido</span><?php endif ?>
            <?php if ($l['repetir']): ?><span class="badge badge-gray" style="font-size:10px"><i class="fas fa-redo"></i> <?php echo $l['repetir'] ?></span><?php endif ?>
          </div>
          <div style="font-size:13px;color:var(--text-muted);margin-bottom:10px"><?php echo htmlspecialchars($l['mensagem']) ?></div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:8px">
            <span style="font-size:11px;color:var(--text-light)"><i class="fas fa-clock"></i> <?php echo fmt_datahora($l['datahora']) ?></span>
            <?php if (!$l['lido']): ?>
            <form method="POST" style="display:inline">
      <?php echo csrf_field() ?>
              <input type="hidden" name="acao" value="lida">
              <input type="hidden" name="id" value="<?php echo $l['id'] ?>">
              <input type="hidden" name="filtro" value="<?php echo $filtro ?>">
              <button type="submit" class="btn btn-sm btn-success" style="padding:5px 10px;font-size:12px"><i class="fas fa-check"></i> Marcar lida</button>
            </form>
            <?php endif ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Eliminar este lembrete?')">
      <?php echo csrf_field() ?>
              <input type="hidden" name="acao" value="eliminar">
              <input type="hidden" name="id" value="<?php echo $l['id'] ?>">
              <input type="hidden" name="filtro" value="<?php echo $filtro ?>">
              <button type="submit" class="btn btn-sm btn-danger" style="padding:5px 10px;font-size:12px"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach ?>
  <?php endif ?>
</div>

<! MODAL CRIAR LEMBRETE>
<div class="modal-overlay" id="modal-novo-lem">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
      <h2 style="font-size:17px;font-weight:700">Novo Lembrete</h2>
      <button onclick="fecharModais()" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted)">×</button>
    </div>
    <form method="POST" action="lembretes.php">
      <?php echo csrf_field() ?>
      <input type="hidden" name="acao" value="criar">
      <input type="hidden" name="filtro" value="<?php echo $filtro ?>">
      <div class="form-group">
        <label class="form-label">Título *</label>
        <input type="text" name="titulo" class="form-control" placeholder="ex: Tomar Paracetamol" required>
      </div>
      <div class="form-group">
        <label class="form-label">Mensagem *</label>
        <textarea name="mensagem" class="form-control" rows="3" placeholder="Descrição..." required></textarea>
      </div>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Data e Hora *</label>
          <input type="datetime-local" name="datahora" class="form-control" required
                 value="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Tipo</label>
          <select name="tipo" class="form-control">
            <option value="medicamento">Medicamento</option>
            <option value="consulta">Consulta</option>
            <option value="agua">Hidratação</option>
            <option value="exercicio">Exercício</option>
            <option value="urgente">Urgente</option>
            <option value="geral" selected>Geral</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Prioridade</label>
          <select name="prioridade" class="form-control">
            <option value="baixa">Baixa</option>
            <option value="media" selected>Média</option>
            <option value="alta">Alta</option>
            <option value="urgente">Urgente</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Repetir</label>
          <select name="repetir" class="form-control">
            <option value="">Não repetir</option>
            <option value="diario">Diariamente</option>
            <option value="semanal">Semanalmente</option>
            <option value="mensal">Mensalmente</option>
          </select>
        </div>
      </div>
      <div style="display:flex;gap:12px;margin-top:8px">
        <button type="submit" class="btn btn-primary" style="flex:1;padding:12px"><i class="fas fa-save"></i> Guardar</button>
        <button type="button" class="btn btn-ghost" style="flex:1;padding:12px" onclick="fecharModais()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
function fecharModais() {
  document.getElementById('modal-novo-lem').classList.remove('open');
  document.getElementById('modal-editar-lem').classList.remove('open');
}

function abrirModalNovo() {
  fecharModais();
  document.getElementById('modal-novo-lem').classList.add('open');
}

function abrirModalEditar(l) {
  fecharModais();
  document.getElementById('edit-lem-id').value        = l.id;
  document.getElementById('edit-lem-titulo').value    = l.titulo;
  document.getElementById('edit-lem-mensagem').value  = l.mensagem;
  const dt = l.datahora ? l.datahora.replace(' ', 'T').slice(0, 16) : '';
  document.getElementById('edit-lem-data').value = dt.slice(0, 10);
  document.getElementById('edit-lem-hora').value = dt.slice(11, 16);
  document.getElementById('edit-lem-tipo').value      = l.tipo;
  document.getElementById('edit-lem-prioridade').value= l.prioridade;
  document.getElementById('edit-lem-repetir').value   = l.repetir ?? '';
  document.getElementById('modal-editar-lem').classList.add('open');
}
</script>

<?php include 'includes/footer.php'; ?>
