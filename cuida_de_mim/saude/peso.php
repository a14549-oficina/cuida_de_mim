<?php
$page_id    = 'peso';
$page_title = 'Peso & IMC';
require_once __DIR__ . '/../config/config.php';
$uid = user_id();

try { db()->exec("ALTER TABLE utilizadores ADD COLUMN IF NOT EXISTS altura SMALLINT UNSIGNED DEFAULT NULL"); } catch(Exception $e){}

// Registar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'registar') {
    csrf_verify();
    $peso  = (float)str_replace(',', '.', $_POST['peso'] ?? 0);
    $data  = $_POST['data'] ?? date('Y-m-d');
    $notas = trim($_POST['notas'] ?? '');
    $altura_nova = (int)($_POST['altura'] ?? 0);
    if ($peso > 0) {
        if ($altura_nova > 0) db_exec('UPDATE utilizadores SET altura=? WHERE id=?', [$altura_nova, $uid]);
        $alt = db_row('SELECT altura FROM utilizadores WHERE id=?', [$uid])['altura'] ?? 0;
        $imc = ($alt > 0) ? round($peso / (($alt/100)**2), 2) : null;
        db_exec('INSERT INTO peso_historico (utilizador_id,data,peso,imc,notas) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE peso=VALUES(peso),imc=VALUES(imc),notas=VALUES(notas)',
            [$uid,$data,$peso,$imc,$notas?:null]);
        header('Location: peso.php?ok=1'); exit;
    }
}

// Apagar
if (isset($_GET['apagar'])) {
    db_exec('DELETE FROM peso_historico WHERE id=? AND utilizador_id=?', [(int)$_GET['apagar'], $uid]);
    header('Location: peso.php'); exit;
}

// Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar') {
    csrf_verify();
    $id   = (int)($_POST['id'] ?? 0);
    $peso = (float)str_replace(',', '.', $_POST['peso'] ?? 0);
    $data = $_POST['data'] ?? '';
    $notas = trim($_POST['notas'] ?? '');
    $altura_nova = (int)($_POST['altura'] ?? 0);
    if ($id > 0 && $peso > 0) {
        if ($altura_nova > 0) db_exec('UPDATE utilizadores SET altura=? WHERE id=?', [$altura_nova, $uid]);
        $alt = db_row('SELECT altura FROM utilizadores WHERE id=?', [$uid])['altura'] ?? 0;
        $imc = ($alt > 0) ? round($peso / (($alt/100)**2), 2) : null;
        db_exec('UPDATE peso_historico SET peso=?,imc=?,data=?,notas=? WHERE id=? AND utilizador_id=?',
            [$peso,$imc,$data,$notas?:null,$id,$uid]);
    }
    header('Location: peso.php?ok=1'); exit;
}

$altura   = (int)(db_row('SELECT altura FROM utilizadores WHERE id=?', [$uid])['altura'] ?? 0);
$historico = db_query('SELECT * FROM peso_historico WHERE utilizador_id=? ORDER BY data DESC LIMIT 90', [$uid]);
$ultimo   = $historico[0] ?? null;
$imc_atual = $ultimo ? (float)($ultimo['imc'] ?? 0) : null;

function classificar_imc($imc) {
    if (!$imc) return ['label'=>'Sem dados','class'=>'','cor'=>'#94a3b8'];
    if ($imc < 18.5) return ['label'=>'Baixo peso','class'=>'imc-baixo','cor'=>'#3b82f6'];
    if ($imc < 25)   return ['label'=>'Normal','class'=>'imc-normal','cor'=>'#10b981'];
    if ($imc < 30)   return ['label'=>'Sobrepeso','class'=>'imc-sobrepeso','cor'=>'#f59e0b'];
    return ['label'=>'Obesidade','class'=>'imc-obesidade','cor'=>'#ef4444'];
}
$imc_info = classificar_imc($imc_atual ?: null);

$grafico = array_reverse(array_slice($historico, 0, 30));
$labels  = array_map(fn($r) => date('d/m', strtotime($r['data'])), $grafico);
$pesos   = array_map(fn($r) => (float)$r['peso'], $grafico);

include '../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> Registo guardado com sucesso!</div>
<?php endif ?>

<! PAINEL SUPERIOR: último registo + gráfico lado a lado >
<?php if ($ultimo): ?>
<div class="saude-top-grid">

  <! Card último registo >
  <div class="card" style="display:flex;flex-direction:column;justify-content:center;align-items:center;padding:28px 20px;text-align:center">
    <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">
      Último registo<br><?php echo date('d/m/Y', strtotime($ultimo['data'])) ?>
    </div>
    <div style="font-size:48px;font-weight:800;letter-spacing:-.03em;color:var(--text);line-height:1">
      <?php echo number_format($ultimo['peso'],1) ?>
      <span style="font-size:18px;font-weight:600;color:var(--text-muted)">kg</span>
    </div>
    <?php if ($imc_atual): ?>
    <div style="margin-top:14px;width:100%">
      <div style="font-size:28px;font-weight:800;color:<?php echo $imc_info['cor'] ?>;line-height:1"><?php echo $imc_atual ?></div>
      <div style="font-size:11px;font-weight:700;margin-top:4px;color:<?php echo $imc_info['cor'] ?>;text-transform:uppercase;letter-spacing:.05em"><?php echo $imc_info['label'] ?></div>
      <div style="margin-top:10px;height:6px;background:#f1f5f9;border-radius:99px;overflow:hidden">
        <?php $pct = min(100, max(0, ($imc_atual - 15) / (40 - 15) * 100)) ?>
        <div style="height:100%;width:<?php echo $pct ?>%;background:<?php echo $imc_info['cor'] ?>;border-radius:99px;transition:width .5s"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:9px;color:var(--text-muted);margin-top:3px"><span>15</span><span>25</span><span>30</span><span>40</span></div>
    </div>
    <?php endif ?>
  </div>

  <! Gráfico >
  <?php if (count($grafico) > 1): ?>
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-chart-line"></i> Evolução do Peso</div></div>
    <div class="card-body" style="padding:12px 16px"><canvas id="chartPeso" height="90"></canvas></div>
  </div>
  <?php else: ?>
  <div class="card" style="display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:13px">
    <div style="text-align:center"><i class="fas fa-chart-line" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px"></i>Regista mais pesos para ver a evolução</div>
  </div>
  <?php endif ?>

</div>
<?php endif ?>

<! LINHA INFERIOR: formulário + histórico >
<div class="two-col">

  <! Formulário >
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-weight"></i> Registar Peso</div></div>
    <div class="card-body">
      <form method="POST" action="peso.php">
        <?php echo csrf_field() ?>
        <input type="hidden" name="acao" value="registar">
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Peso (kg) *</label>
            <input type="number" name="peso" class="form-control" step="0.1" min="20" max="300" placeholder="ex: 72.5" required>
          </div>
          <div class="form-group">
            <label class="form-label">Data</label>
            <input type="date" name="data" class="form-control" value="<?php echo date('Y-m-d') ?>" max="<?php echo date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Altura (cm)</label>
            <input type="number" name="altura" class="form-control" min="100" max="250" placeholder="ex: 170" value="<?php echo $altura ?: '' ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Notas</label>
            <input type="text" name="notas" class="form-control" placeholder="Opcional...">
          </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;margin-top:4px"><i class="fas fa-save"></i> Guardar</button>
      </form>
    </div>
  </div>

  <! Histórico >
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-history"></i> Histórico</div></div>
    <div style="overflow-y:auto;max-height:340px;overflow-x:hidden">
      <?php if (!$historico): ?>
        <div class="empty-state" style="padding:40px">
          <div class="empty-icon"><i class="fas fa-weight"></i></div>
          <div class="empty-title">Sem registos</div>
          <div class="empty-text">Adicione o seu primeiro peso.</div>
        </div>
      <?php else: ?>
        <table class="table" style="table-layout:fixed;width:100%">
          <thead>
            <tr>
              <th style="width:90px">Data</th>
              <th style="width:80px">Peso</th>
              <th>IMC</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($historico as $r):
            $ri = classificar_imc($r['imc'] ? (float)$r['imc'] : null);
          ?>
            <tr>
              <td style="font-weight:600;font-size:12px;white-space:nowrap"><?php echo date('d/m/Y', strtotime($r['data'])) ?></td>
              <td style="font-weight:700;white-space:nowrap"><?php echo number_format($r['peso'],1) ?> kg</td>
              <td>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px">
                  <div>
                    <?php if ($r['imc']): ?>
                      <span style="font-size:11px;font-weight:700;color:<?php echo $ri['cor'] ?>"><?php echo $r['imc'] ?></span>
                      <span style="font-size:10px;color:var(--text-muted);display:block"><?php echo $ri['label'] ?></span>
                    <?php else: ?>—<?php endif ?>
                  </div>
                  <div style="display:flex;gap:4px;flex-shrink:0">
                    <button type="button" onclick="abrirEditarPeso(<?php echo htmlspecialchars(json_encode($r)) ?>)" class="btn btn-xs" style="background:#dbeafe;color:#1d4ed8;border:none"><i class="fas fa-pen"></i></button>
                    <a href="?apagar=<?php echo $r['id'] ?>" class="btn btn-xs btn-danger" onclick="return confirm('Apagar registo?')"><i class="fas fa-times"></i></a>
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

</div>

<?php if (count($grafico) > 1): ?>
<script>
new Chart(document.getElementById('chartPeso').getContext('2d'), {
  type: 'line',
  data: {
    labels: <?php echo json_encode($labels) ?>,
    datasets: [{
      data: <?php echo json_encode($pesos) ?>,
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59,130,246,.07)',
      tension: .4, fill: true, pointRadius: 3, pointBackgroundColor: '#3b82f6', borderWidth: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    animation: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ctx.parsed.y + ' kg' } } },
    scales: {
      y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 10 }, callback: v => v + ' kg' } },
      x: { grid: { display: false }, ticks: { font: { size: 10 } } }
    }
  }
});
</script>
<?php endif ?>

<?php include '../includes/footer.php'; ?>

<!-- Modal Editar Peso -->
<div id="modalEditarPeso" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.25);margin:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h3 style="font-size:16px;font-weight:700;margin:0"><i class="fas fa-pen" style="color:var(--primary);margin-right:8px"></i> Editar Registo</h3>
      <button onclick="fecharEditarPeso()" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted)">&times;</button>
    </div>
    <form method="POST" action="peso.php">
      <?php echo csrf_field() ?>
      <input type="hidden" name="acao" value="editar">
      <input type="hidden" name="id" id="editPesoId">
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label">Peso (kg) *</label>
          <input type="number" name="peso" id="editPesoVal" class="form-control" step="0.1" min="20" max="300" required>
        </div>
        <div class="form-group">
          <label class="form-label">Data</label>
          <input type="date" name="data" id="editPesoData" class="form-control" max="<?php echo date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Altura (cm)</label>
          <input type="number" name="altura" id="editPesoAltura" class="form-control" min="100" max="250" value="<?php echo $altura ?: '' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Notas</label>
          <input type="text" name="notas" id="editPesoNotas" class="form-control" placeholder="Opcional...">
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="button" onclick="fecharEditarPeso()" class="btn btn-ghost" style="flex:1">Cancelar</button>
        <button type="submit" class="btn btn-primary" style="flex:1"><i class="fas fa-save"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>
<script>
function abrirEditarPeso(r) {
  document.getElementById('editPesoId').value    = r.id;
  document.getElementById('editPesoVal').value   = parseFloat(r.peso);
  document.getElementById('editPesoData').value  = r.data;
  document.getElementById('editPesoNotas').value = r.notas || '';
  document.getElementById('modalEditarPeso').style.display = 'flex';
}
function fecharEditarPeso() {
  document.getElementById('modalEditarPeso').style.display = 'none';
}
document.getElementById('modalEditarPeso').addEventListener('click', function(e) {
  if (e.target === this) fecharEditarPeso();
});
</script>
