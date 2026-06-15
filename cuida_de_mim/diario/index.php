<?php
$page_id = 'diario';
$page_title = 'Diário de Saúde';
require_once __DIR__ . '/../config/config.php';
$uid = user_id();

// Guardar registo do diário (POST - criar ou editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'guardar') {
    csrf_verify();
    // edit_data vem do formulário de edição; sem ele usa hoje
    $data = $_POST['edit_data'] ?? date('Y-m-d');
    db_exec(
        'INSERT INTO diario (utilizador_id, data, humor, energia, dor, peso, pressao, sintomas, notas)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE humor=VALUES(humor), energia=VALUES(energia), dor=VALUES(dor),
           peso=VALUES(peso), pressao=VALUES(pressao), sintomas=VALUES(sintomas), notas=VALUES(notas)',
        [
            $uid, $data,
            $_POST['humor'] ?? 'bom',
            (int)($_POST['energia'] ?? 5),
            (int)($_POST['dor'] ?? 0),
            ($_POST['peso'] !== '' ? (float)$_POST['peso'] : null),
            trim($_POST['pressao'] ?? ''),
            trim($_POST['sintomas'] ?? ''),
            trim($_POST['notas'] ?? ''),
        ]
    );
    header('Location: index.php?guardado=1');
    exit;
}

// Apagar registo do diário
if (isset($_GET['apagar']) && $_GET['apagar']) {
    db_exec('DELETE FROM diario WHERE utilizador_id = ? AND data = ?', [$uid, $_GET['apagar']]);
    header('Location: index.php'); exit;
}

// Carregar registo para edição (via GET ?editar=YYYY-MM-DD)
$edit_reg = null;
if (isset($_GET['editar'])) {
    $edit_reg = db_row(
        'SELECT * FROM diario WHERE utilizador_id = ? AND data = ?',
        [$uid, $_GET['editar']]
    );
}

// Carregar registos recentes (7 dias)
$registos = db_query(
    'SELECT * FROM diario WHERE utilizador_id = ? ORDER BY data DESC LIMIT 7',
    [$uid]
);

// Calcular wellness score (média dos últimos 7 dias)
$wellness = 70;
if ($registos) {
    $sum = 0;
    $humorMap = ['otimo'=>10,'bom'=>8,'razoavel'=>5,'mau'=>3,'pessimo'=>1];
    foreach ($registos as $r) {
        $h = $humorMap[$r['humor']] ?? 5;
        $sum += ($r['energia'] * 6 + (10 - $r['dor']) * 4 + $h * 10) / 3;
    }
    $wellness = min(100, round($sum / count($registos)));
}
$dashoffset = round(314 - ($wellness / 100) * 314);

// Valores pré-preenchidos (edição ou defaults)
$v_humor    = $edit_reg['humor']    ?? 'bom';
$v_energia  = $edit_reg['energia']  ?? 5;
$v_dor      = $edit_reg['dor']      ?? 0;
$v_peso     = $edit_reg['peso']     ?? '';
$v_pressao  = $edit_reg['pressao']  ?? '';
$v_sintomas = $edit_reg['sintomas'] ?? '';
$v_notas    = $edit_reg['notas']    ?? '';

include '../includes/header.php';
?>

<?php if (isset($_GET['guardado'])): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> Registo guardado com sucesso!</div>
<?php endif ?>

<div class="two-col">
  <div>
    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <?php if ($edit_reg): ?>
            <i class="fas fa-edit"></i> Editar Registo — <?php echo date('d/m/Y', strtotime($edit_reg['data'])) ?>
          <?php else: ?>
            <i class="fas fa-plus-circle"></i> Registo de Hoje
          <?php endif ?>
        </div>
        <?php if ($edit_reg): ?>
          <a href="index.php" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i> Cancelar</a>
        <?php endif ?>
      </div>
      <div class="card-body">
        <form method="POST" action="index.php">
      <?php echo csrf_field() ?>
          <input type="hidden" name="acao" value="guardar">
          <! guarda a data original ao editar, para o ON DUPLICATE KEY funcionar >
          <?php if ($edit_reg): ?>
            <input type="hidden" name="edit_data" value="<?php echo $edit_reg['data'] ?>">
          <?php endif ?>
          <input type="hidden" name="humor" id="humor-input" value="<?php echo htmlspecialchars($v_humor) ?>">

          <div class="form-group">
            <label class="form-label">Como está o seu humor?</label>
            <div class="mood-grid" id="moodGrid">
              <?php foreach (['pessimo'=>['😞','Péssimo'],'mau'=>['😕','Mau'],'razoavel'=>['😐','Razoável'],'bom'=>['🙂','Bom'],'otimo'=>['😄','Ótimo']] as $val => [$emoji, $label]): ?>
                <button type="button" class="mood-btn <?php echo $v_humor === $val ? 'selected' : '' ?>"
                        data-val="<?php echo $val ?>" onclick="selectMood('<?php echo $val ?>',this)">
                  <span class="mood-emoji"><?php echo $emoji ?></span>
                  <span class="mood-label"><?php echo $label ?></span>
                </button>
              <?php endforeach ?>
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Energia <span id="energia-val" style="color:var(--primary);font-weight:700"><?php echo $v_energia ?>/10</span></label>
              <input type="range" name="energia" min="1" max="10" value="<?php echo $v_energia ?>" id="energia-slider"
                     oninput="document.getElementById('energia-val').textContent=this.value+'/10'">
            </div>
            <div class="form-group">
              <label class="form-label">Dor <span id="dor-val" style="color:#ef4444;font-weight:700"><?php echo $v_dor ?>/10</span></label>
              <input type="range" name="dor" min="0" max="10" value="<?php echo $v_dor ?>" id="dor-slider"
                     style="accent-color:#ef4444" oninput="document.getElementById('dor-val').textContent=this.value+'/10'">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Sintomas</label>
            <input type="text" name="sintomas" class="form-control" placeholder="ex: dor de cabeça, cansaço..." value="<?php echo htmlspecialchars($v_sintomas) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Notas</label>
            <textarea name="notas" class="form-control" placeholder="Como se sentiu hoje..."><?php echo htmlspecialchars($v_notas) ?></textarea>
          </div>

          <div style="display:flex;gap:12px">
            <button type="submit" class="btn btn-primary" style="flex:1;padding:12px">
              <i class="fas fa-save"></i> <?php echo $edit_reg ? 'Guardar Alterações' : 'Guardar Registo' ?>
            </button>
            <?php if ($edit_reg): ?>
              <a href="index.php" class="btn btn-ghost" style="flex:1;padding:12px;text-align:center">Cancelar</a>
            <?php endif ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div>
    <! WELLNESS SCORE >
    <div class="card" style="margin-bottom:16px">
      <div class="card-header"><div class="card-title"><i class="fas fa-heart"></i> Score de Bem-estar</div></div>
      <div class="card-body" style="text-align:center">
        <div class="wellness-ring">
          <svg width="120" height="120" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="50" fill="none" stroke="#e2e8f0" stroke-width="10"/>
            <circle cx="60" cy="60" r="50" fill="none" stroke="#2563eb" stroke-width="10"
                    stroke-dasharray="314" stroke-dashoffset="<?php echo $dashoffset ?>"
                    stroke-linecap="round"/>
          </svg>
          <div class="ring-text">
            <span class="ring-value"><?php echo $wellness ?></span>
            <span class="ring-label">Score</span>
          </div>
        </div>
        <div style="margin-top:16px;font-size:13px;color:var(--text-muted)">Score baseado nos seus últimos 7 dias</div>
      </div>
    </div>

    <! HISTÓRICO >
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-history"></i> Registos Recentes</div></div>
      <div style="font-size:13px">
        <?php
        $emojis = ['otimo'=>'😄','bom'=>'🙂','razoavel'=>'😐','mau'=>'😕','pessimo'=>'😞'];
        $badges = ['otimo'=>'badge-success','bom'=>'badge-info','razoavel'=>'badge-warning','mau'=>'badge-danger','pessimo'=>'badge-gray'];
        if (!$registos):
        ?>
          <div class="empty-state"><div class="empty-title">Sem registos</div></div>
        <?php else: foreach ($registos as $r):
          $isHoje    = $r['data'] === date('Y-m-d');
          $isEditing = $edit_reg && $edit_reg['data'] === $r['data'];
          $dias_pt   = ['Sun'=>'Dom','Mon'=>'Seg','Tue'=>'Ter','Wed'=>'Qua','Thu'=>'Qui','Fri'=>'Sex','Sat'=>'Sáb'];
          $dLabel    = $dias_pt[date('D', strtotime($r['data']))] . ', ' . date('d/m', strtotime($r['data']));
        ?>
          <div style="padding:14px 20px;border-bottom:1px solid var(--border);<?php echo $isEditing ? 'background:#eff6ff;border-radius:8px;' : '' ?>">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
              <span style="font-weight:700;font-size:13px">
                <?php echo $dLabel ?>
                <?php if ($isHoje): ?><span class="badge badge-info" style="font-size:10px">Hoje</span><?php endif ?>
              </span>
              <div style="display:flex;align-items:center;gap:6px">
                <span class="badge <?php echo $badges[$r['humor']] ?? 'badge-gray' ?>">
                  <?php echo ($emojis[$r['humor']] ?? '') . ' ' . htmlspecialchars($r['humor']) ?>
                </span>
                <a href="?editar=<?php echo $r['data'] ?>" class="btn btn-xs" style="background:#dbeafe;color:#1d4ed8;border:none;padding:3px 7px;border-radius:5px;font-size:11px">
                  <i class="fas fa-edit"></i>
                </a>
                <a href="?apagar=<?php echo $r['data'] ?>" class="btn btn-xs btn-danger" style="padding:3px 7px;font-size:11px" onclick="return confirm('Apagar este registo?')">
                  <i class="fas fa-trash"></i>
                </a>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
              <div><div style="font-size:11px;color:var(--text-muted);margin-bottom:3px">Energia</div>
                <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $r['energia']*10 ?>%;background:#3b82f6"></div></div></div>
              <div><div style="font-size:11px;color:var(--text-muted);margin-bottom:3px">Dor</div>
                <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $r['dor']*10 ?>%;background:#ef4444"></div></div></div>
            </div>
            <?php if ($r['sintomas']): ?><div style="font-size:12px;color:var(--text-muted);margin-top:6px">⚠️ <?php echo htmlspecialchars($r['sintomas']) ?></div><?php endif ?>
          </div>
        <?php endforeach; endif ?>
      </div>
    </div>
  </div>
</div>

<script>
function selectMood(val, btn) {
  document.getElementById('humor-input').value = val;
  document.querySelectorAll('.mood-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
}
</script>

<?php include '../includes/footer.php'; ?>
