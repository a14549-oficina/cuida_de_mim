<?php
$page_id    = 'tensao';
$page_title = 'Tensão Arterial';
require_once __DIR__ . '/../config/config.php';
$uid = user_id();

// Registar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'registar') {
    csrf_verify();
    $sis  = (int)($_POST['sistolica'] ?? 0);
    $dia  = (int)($_POST['diastolica'] ?? 0);
    $puls = ($_POST['pulsacao'] ?? '') !== '' ? (int)$_POST['pulsacao'] : null;
    $data = $_POST['data'] ?? date('Y-m-d');
    $hora = $_POST['hora'] ?? date('H:i');
    $ctx  = $_POST['contexto'] ?? 'repouso';
    $notas = trim($_POST['notas'] ?? '');
    if ($sis > 0 && $dia > 0) {
        db_exec('INSERT INTO tensao_arterial (utilizador_id,data,hora,sistolica,diastolica,pulsacao,contexto,notas) VALUES (?,?,?,?,?,?,?,?)',
            [$uid,$data,$hora,$sis,$dia,$puls,$ctx,$notas?:null]);
        header('Location: tensao.php?ok=1'); exit;
    }
}

// Apagar
if (isset($_GET['apagar'])) {
    db_exec('DELETE FROM tensao_arterial WHERE id=? AND utilizador_id=?', [(int)$_GET['apagar'], $uid]);
    header('Location: tensao.php'); exit;
}

// Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar') {
    csrf_verify();
    $id   = (int)($_POST['id'] ?? 0);
    $sis  = (int)($_POST['sistolica'] ?? 0);
    $dia  = (int)($_POST['diastolica'] ?? 0);
    $puls = ($_POST['pulsacao'] ?? '') !== '' ? (int)$_POST['pulsacao'] : null;
    $data = $_POST['data'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $ctx  = $_POST['contexto'] ?? 'repouso';
    $notas = trim($_POST['notas'] ?? '');
    if ($id > 0 && $sis > 0 && $dia > 0) {
        db_exec('UPDATE tensao_arterial SET sistolica=?,diastolica=?,pulsacao=?,data=?,hora=?,contexto=?,notas=? WHERE id=? AND utilizador_id=?',
            [$sis,$dia,$puls,$data,$hora,$ctx,$notas?:null,$id,$uid]);
    }
    header('Location: tensao.php?ok=1'); exit;
}

$historico = db_query('SELECT * FROM tensao_arterial WHERE utilizador_id=? ORDER BY data DESC, hora DESC LIMIT 90', [$uid]);
$ultimo    = $historico[0] ?? null;
$grafico   = array_reverse(array_slice($historico, 0, 30));

function classificar_tensao($s, $d) {
    if ($s < 90  || $d < 60)  return ['label'=>'Baixa',  'class'=>'tensao-baixa',  'cor'=>'#3b82f6'];
    if ($s < 120 && $d < 80)  return ['label'=>'Normal', 'class'=>'tensao-normal', 'cor'=>'#10b981'];
    if ($s < 130 && $d < 80)  return ['label'=>'Elevada','class'=>'tensao-elevada','cor'=>'#f59e0b'];
    return                           ['label'=>'Alta',   'class'=>'tensao-alta',   'cor'=>'#ef4444'];
}
$t_info = $ultimo ? classificar_tensao((int)$ultimo['sistolica'], (int)$ultimo['diastolica']) : null;

$labels_g = array_map(fn($r) => date('d/m', strtotime($r['data'])), $grafico);
$sis_g    = array_map(fn($r) => (int)$r['sistolica'],  $grafico);
$dia_g    = array_map(fn($r) => (int)$r['diastolica'], $grafico);

include '../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> Registo guardado!</div>
<?php endif ?>

<! PAINEL SUPERIOR: último registo + gráfico >
<?php if ($ultimo): ?>
<div class="saude-top-grid">

  <! Card último registo >
  <div class="card" style="display:flex;flex-direction:column;justify-content:center;align-items:center;padding:28px 16px;text-align:center">
    <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px">
      Última medição<br><?php echo date('d/m/Y', strtotime($ultimo['data'])) ?> · <?php echo substr($ultimo['hora'],0,5) ?>
    </div>
    <div style="font-size:40px;font-weight:800;letter-spacing:-.02em;color:var(--text);line-height:1">
      <span style="color:<?php echo $t_info['cor'] ?>"><?php echo $ultimo['sistolica'] ?></span>
      <span style="font-size:24px;color:var(--text-muted);margin:0 2px">/</span>
      <span style="color:var(--text)"><?php echo $ultimo['diastolica'] ?></span>
    </div>
    <div style="font-size:11px;color:var(--text-muted);margin-top:4px">mmHg</div>
    <div style="margin-top:12px;background:<?php echo $t_info['cor'] ?>22;color:<?php echo $t_info['cor'] ?>;font-size:11px;font-weight:700;padding:4px 14px;border-radius:99px;text-transform:uppercase;letter-spacing:.05em">
      <?php echo $t_info['label'] ?>
    </div>
    <?php if ($ultimo['pulsacao']): ?>
    <div style="margin-top:12px;font-size:12px;color:var(--text-muted)">
      <i class="fas fa-heartbeat" style="color:#ef4444"></i> <?php echo $ultimo['pulsacao'] ?> bpm
    </div>
    <?php endif ?>
  </div>

  <! Gráfico >
  <?php if (count($grafico) > 1): ?>
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-chart-line"></i> Evolução da Tensão</div></div>
    <div class="card-body" style="padding:12px 16px"><canvas id="chartTensao" height="90"></canvas></div>
  </div>
  <?php else: ?>
  <div class="card" style="display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:13px">
    <div style="text-align:center"><i class="fas fa-chart-line" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px"></i>Regista mais medições para ver a evolução</div>
  </div>
  <?php endif ?>

</div>
<?php endif ?>

<! LINHA INFERIOR: formulário + histórico >
<div class="two-col">

  <! Formulário >
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-plus-circle"></i> Nova Medição</div></div>
    <div class="card-body">
      <form method="POST" action="tensao.php">
        <?php echo csrf_field() ?>
        <input type="hidden" name="acao" value="registar">
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Sistólica (mmHg) *</label>
            <input type="number" name="sistolica" class="form-control" min="50" max="300" placeholder="ex: 120" required>
          </div>
          <div class="form-group">
            <label class="form-label">Diastólica (mmHg) *</label>
            <input type="number" name="diastolica" class="form-control" min="30" max="200" placeholder="ex: 80" required>
          </div>
          <div class="form-group">
            <label class="form-label">Pulsação (bpm)</label>
            <input type="number" name="pulsacao" class="form-control" min="30" max="250" placeholder="ex: 72">
          </div>
          <div class="form-group">
            <label class="form-label">Contexto</label>
            <select name="contexto" class="form-control">
              <option value="repouso">Repouso</option>
              <option value="manha">Manhã</option>
              <option value="noite">Noite</option>
              <option value="apos_exercicio">Após exercício</option>
              <option value="outro">Outro</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Data</label>
            <input type="date" name="data" class="form-control" value="<?php echo date('Y-m-d') ?>" max="<?php echo date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Hora</label>
            <input type="time" name="hora" class="form-control" value="<?php echo date('H:i') ?>">
          </div>
          <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Notas</label>
            <input type="text" name="notas" class="form-control" placeholder="Opcional...">
          </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;margin-top:4px"><i class="fas fa-save"></i> Guardar</button>
      </form>
    </div>
  </div>

  <! Histórico + tabela referência >
  <div style="display:flex;flex-direction:column;gap:20px">

    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-history"></i> Histórico</div></div>
      <div style="overflow-y:auto;max-height:280px;overflow-x:hidden">
        <?php if (!$historico): ?>
          <div class="empty-state" style="padding:40px">
            <div class="empty-icon"><i class="fas fa-heartbeat"></i></div>
            <div class="empty-title">Sem registos</div>
          </div>
        <?php else: ?>
          <table class="table" style="table-layout:fixed;width:100%">
            <thead>
              <tr>
                <th style="width:88px">Data/Hora</th>
                <th style="width:72px">mmHg</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($historico as $r):
              $ti = classificar_tensao((int)$r['sistolica'], (int)$r['diastolica']);
            ?>
              <tr>
                <td style="font-size:11px">
                  <div style="font-weight:600"><?php echo date('d/m/Y', strtotime($r['data'])) ?></div>
                  <div style="color:var(--text-muted)"><?php echo substr($r['hora'],0,5) ?></div>
                </td>
                <td style="font-weight:700;font-size:13px;white-space:nowrap"><?php echo $r['sistolica'] ?>/<?php echo $r['diastolica'] ?></td>
                <td>
                  <div style="display:flex;align-items:center;justify-content:space-between;gap:6px">
                    <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:<?php echo $ti['cor'] ?>22;color:<?php echo $ti['cor'] ?>;white-space:nowrap">
                      <?php echo $ti['label'] ?>
                    </span>
                    <div style="display:flex;gap:4px;flex-shrink:0">
                      <button type="button" onclick="abrirEditarTensao(<?php echo htmlspecialchars(json_encode($r)) ?>)" class="btn btn-xs" style="background:#dbeafe;color:#1d4ed8;border:none"><i class="fas fa-pen"></i></button>
                      <a href="?apagar=<?php echo $r['id'] ?>" class="btn btn-xs btn-danger" onclick="return confirm('Apagar?')"><i class="fas fa-times"></i></a>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endforeach ?>
            </tbody>
          </table>
        <?php endif ?>
      </div>
    </div>

    <! Tabela de referência >
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-info-circle"></i> Referência</div></div>
      <div class="card-body" style="padding:10px 14px">
        <table class="table" style="font-size:12px">
          <thead><tr><th>Categoria</th><th>Sistólica</th><th>Diastólica</th></tr></thead>
          <tbody>
            <tr><td><span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:#3b82f622;color:#3b82f6">Baixa</span></td><td>&lt; 90</td><td>&lt; 60</td></tr>
            <tr><td><span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:#10b98122;color:#10b981">Normal</span></td><td>&lt; 120</td><td>&lt; 80</td></tr>
            <tr><td><span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:#f59e0b22;color:#f59e0b">Elevada</span></td><td>120–129</td><td>&lt; 80</td></tr>
            <tr><td><span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:#ef444422;color:#ef4444">Alta</span></td><td>≥ 130</td><td>≥ 80</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php if (count($grafico) > 1): ?>
<script>
new Chart(document.getElementById('chartTensao').getContext('2d'), {
  type: 'line',
  data: {
    labels: <?php echo json_encode($labels_g) ?>,
    datasets: [
      { label: 'Sistólica',  data: <?php echo json_encode($sis_g) ?>, borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,.06)', tension:.4, fill:false, pointRadius:3, borderWidth:2 },
      { label: 'Diastólica', data: <?php echo json_encode($dia_g) ?>, borderColor:'#10b981', backgroundColor:'rgba(16,185,129,.06)', tension:.4, fill:false, pointRadius:3, borderWidth:2 }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    animation: false,
    plugins: { legend: { position:'top', labels: { font: { size:11 }, boxWidth:20, padding:10 } } },
    scales: {
      y: { beginAtZero:false, grid:{ color:'rgba(0,0,0,.05)' }, ticks:{ font:{ size:10 } } },
      x: { grid:{ display:false }, ticks:{ font:{ size:10 } } }
    }
  }
});
</script>
<?php endif ?>

<?php include '../includes/footer.php'; ?>

<! Modal Editar Tensão >
<div id="modalEditarTensao" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.25);margin:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h3 style="font-size:16px;font-weight:700;margin:0"><i class="fas fa-pen" style="color:var(--primary);margin-right:8px"></i> Editar Medição</h3>
      <button onclick="fecharEditarTensao()" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted)">&times;</button>
    </div>
    <form method="POST" action="tensao.php">
      <?php echo csrf_field() ?>
      <input type="hidden" name="acao" value="editar">
      <input type="hidden" name="id" id="editTensaoId">
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Sistólica (mmHg) *</label>
          <input type="number" name="sistolica" id="editTensaoSis" class="form-control" min="50" max="300" required>
        </div>
        <div class="form-group">
          <label class="form-label">Diastólica (mmHg) *</label>
          <input type="number" name="diastolica" id="editTensaoDia" class="form-control" min="30" max="200" required>
        </div>
        <div class="form-group">
          <label class="form-label">Pulsação (bpm)</label>
          <input type="number" name="pulsacao" id="editTensaoPuls" class="form-control" min="30" max="250">
        </div>
        <div class="form-group">
          <label class="form-label">Contexto</label>
          <select name="contexto" id="editTensaoCtx" class="form-control">
            <option value="repouso">Repouso</option>
            <option value="manha">Manhã</option>
            <option value="noite">Noite</option>
            <option value="apos_exercicio">Após exercício</option>
            <option value="outro">Outro</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Data</label>
          <input type="date" name="data" id="editTensaoData" class="form-control" max="<?php echo date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Hora</label>
          <input type="time" name="hora" id="editTensaoHora" class="form-control">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Notas</label>
          <input type="text" name="notas" id="editTensaoNotas" class="form-control" placeholder="Opcional...">
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="button" onclick="fecharEditarTensao()" class="btn btn-ghost" style="flex:1">Cancelar</button>
        <button type="submit" class="btn btn-primary" style="flex:1"><i class="fas fa-save"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>
<script>
function abrirEditarTensao(r) {
  document.getElementById('editTensaoId').value    = r.id;
  document.getElementById('editTensaoSis').value   = r.sistolica;
  document.getElementById('editTensaoDia').value   = r.diastolica;
  document.getElementById('editTensaoPuls').value  = r.pulsacao || '';
  document.getElementById('editTensaoData').value  = r.data;
  document.getElementById('editTensaoHora').value  = r.hora ? r.hora.substring(0,5) : '';
  document.getElementById('editTensaoNotas').value = r.notas || '';
  document.getElementById('editTensaoCtx').value   = r.contexto || 'repouso';
  document.getElementById('modalEditarTensao').style.display = 'flex';
}
function fecharEditarTensao() {
  document.getElementById('modalEditarTensao').style.display = 'none';
}
document.getElementById('modalEditarTensao').addEventListener('click', function(e) {
  if (e.target === this) fecharEditarTensao();
});
</script>
