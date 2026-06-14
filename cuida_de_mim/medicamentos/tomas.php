<?php
$page_id    = 'tomas';
$page_title = 'Tomas do Dia';
require_once __DIR__ . '/../config/config.php';
$uid = user_id();

$medicamentos  = db_query('SELECT * FROM medicamentos WHERE utilizador_id = ? AND ativo = 1 ORDER BY horario', [$uid]);

// Garantir que existe registo de toma para hoje para cada medicamento
foreach ($medicamentos as $m) {
    db_exec(
        'INSERT IGNORE INTO tomas (medicamento_id, utilizador_id, data, tomado) VALUES (?, ?, CURDATE(), 0)',
        [$m['id'], $uid]
    );
}

$tomas         = db_query('SELECT * FROM tomas WHERE utilizador_id = ? AND data = CURDATE()', [$uid]);
$tomas_map     = array_column($tomas, 'tomado', 'medicamento_id');
$total_meds    = count($medicamentos);
$tomadas_total = array_sum(array_column($tomas, 'tomado'));
$pendentes     = $total_meds - $tomadas_total;
$adesao        = $total_meds ? round($tomadas_total / $total_meds * 100) : 0;

$historico = db_query(
    'SELECT data, SUM(tomado) as tomadas, COUNT(*) as total
     FROM tomas WHERE utilizador_id = ? AND data >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY data ORDER BY data ASC',
    [$uid]
);

include '../includes/header.php';
?>

<div class="stats-grid four-cols">
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Total</div><div class="stat-card-icon icon-blue"><i class="fas fa-pills"></i></div></div><div class="stat-card-value"><?php echo $total_meds ?></div><div class="stat-card-sub">medicamentos</div></div>
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Tomados</div><div class="stat-card-icon icon-green"><i class="fas fa-check"></i></div></div><div class="stat-card-value"><?php echo $tomadas_total ?></div><div class="stat-card-sub">hoje</div></div>
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Pendentes</div><div class="stat-card-icon icon-yellow"><i class="fas fa-clock"></i></div></div><div class="stat-card-value"><?php echo $pendentes ?></div><div class="stat-card-sub">por tomar</div></div>
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Adesão</div><div class="stat-card-icon icon-purple"><i class="fas fa-chart-line"></i></div></div><div class="stat-card-value"><?php echo $adesao ?>%</div><div class="stat-card-sub">hoje</div></div>
</div>

<! Lista + gráfico lado a lado >
<div class="tomas-grid">

  <! Lista de tomas >
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-pills"></i> <?php
        $dias = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
        $meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
        echo $dias[date('w')] . ', ' . date('d') . ' de ' . $meses[date('n')-1];
      ?></div>
      <a href="index.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Novo</a>
    </div>
    <div style="padding:12px;display:flex;flex-direction:column;gap:8px">
      <?php if (!$medicamentos): ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="fas fa-pills"></i></div>
          <div class="empty-title">Sem medicamentos</div>
          <div class="empty-text">Adicione medicamentos para acompanhar as suas tomas.</div>
          <a href="index.php" class="btn btn-primary" style="margin-top:12px"><i class="fas fa-plus"></i> Adicionar</a>
        </div>
      <?php else: ?>
        <?php foreach ($medicamentos as $m): ?>
          <?php $tomado = $tomas_map[$m['id']] ?? 0; ?>
          <form method="POST" action="tomas_action.php">
            <input type="hidden" name="med_id" value="<?php echo $m['id'] ?>">
            <input type="hidden" name="tomado" value="<?php echo $tomado ? 0 : 1 ?>">
            <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:<?php echo $tomado ? '#f0fdf4' : 'var(--bg)' ?>;cursor:pointer" onclick="this.closest('form').submit()">
              <div style="width:20px;height:20px;border-radius:5px;border:2px solid <?php echo $tomado ? '#10b981' : 'var(--border)' ?>;background:<?php echo $tomado ? '#10b981' : '#fff' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <?php echo $tomado ? '<i class="fas fa-check" style="font-size:10px;color:#fff"></i>' : '' ?>
              </div>
              <div style="flex:1;min-width:0">
                <div style="font-weight:700;font-size:13px;color:<?php echo $tomado ? '#059669' : 'var(--text)' ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                  <?php echo htmlspecialchars($m['nome']) ?> <span style="font-weight:400;color:var(--text-muted)"><?php echo htmlspecialchars($m['dosagem']) ?></span>
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:1px">
                  <?php echo htmlspecialchars($m['instrucoes'] ?: '—') ?> · Stock: <span style="color:<?php echo $m['quantidade'] <= 10 ? 'var(--danger)' : 'inherit' ?>"><?php echo $m['quantidade'] ?> un.</span>
                </div>
              </div>
              <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;background:<?php echo $tomado ? '#d1fae5' : '#dbeafe' ?>;color:<?php echo $tomado ? '#065f46' : '#1d4ed8' ?>;white-space:nowrap;flex-shrink:0">
                <?php echo $tomado ? '✓ Tomado' : substr($m['horario'], 0, 5) ?>
              </span>
            </div>
          </form>
        <?php endforeach ?>
      <?php endif ?>
    </div>
  </div>

  <!-- Gráfico de adesão -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-history"></i> Adesão — 7 dias</div></div>
    <div class="card-body"><canvas id="chartAdesaoSemana" height="180"></canvas></div>
  </div>

</div>

<script>
<?php
$dias_pt = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$hist_map = [];
foreach ($historico as $h) {
    $hist_map[$h['data']] = $h['total'] > 0 ? round($h['tomadas'] / $h['total'] * 100) : 0;
}
$labels = []; $valores = [];
for ($i = 6; $i >= 0; $i--) {
    $data = date('Y-m-d', strtotime("-$i days"));
    $labels[] = $dias_pt[date('w', strtotime($data))];
    $valores[] = $hist_map[$data] ?? 0;
}
?>
const ctx = document.getElementById('chartAdesaoSemana').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($labels) ?>,
    datasets: [{
      label: 'Adesão %',
      data: <?php echo json_encode($valores) ?>,
      backgroundColor: <?php echo json_encode(array_map(fn($v) => $v>=80?'#d1fae5':($v>=50?'#fef3c7':'#fee2e2'), $valores)) ?>,
      borderColor:     <?php echo json_encode(array_map(fn($v) => $v>=80?'#10b981':($v>=50?'#f59e0b':'#ef4444'), $valores)) ?>,
      borderWidth: 2, borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    animation: false,
    plugins: { legend: { display: false } },
    scales: {
      y: { min:0, max:100, ticks: { callback: v => v+'%' }, grid: { color:'#f1f5f9' } },
      x: { grid: { display: false } }
    }
  }
});
</script>

<?php include '../includes/footer.php'; ?>
