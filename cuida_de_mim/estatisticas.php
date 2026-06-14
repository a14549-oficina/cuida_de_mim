<?php
$page_id = 'estatisticas';
$page_title = 'Estatísticas';
require_once __DIR__ . '/config/config.php';
$uid = user_id();

// Stats da BD
$total_meds = (int)(db_row('SELECT COUNT(*) c FROM medicamentos WHERE utilizador_id=? AND ativo=1', [$uid])['c'] ?? 0);
$total_consultas = (int)(db_row('SELECT COUNT(*) c FROM consultas WHERE utilizador_id=? AND datahora>=NOW()', [$uid])['c'] ?? 0);
$total_diario = (int)(db_row('SELECT COUNT(*) c FROM diario WHERE utilizador_id=?', [$uid])['c'] ?? 0);

// Adesão média 30 dias
$adesao_row = db_row(
    'SELECT ROUND(AVG(adesao)*100) pct FROM (
        SELECT data, SUM(tomado)/COUNT(*) adesao
        FROM tomas WHERE utilizador_id=? AND data>=DATE_SUB(CURDATE(),INTERVAL 29 DAY)
        GROUP BY data
    ) t',
    [$uid]
);
$adesao_media = $adesao_row['pct'] ?? 0;

// Adesão 14 dias (para gráfico)
$adesao14 = db_query(
    'SELECT data, ROUND(SUM(tomado)/COUNT(*)*100) pct
     FROM tomas WHERE utilizador_id=? AND data>=DATE_SUB(CURDATE(),INTERVAL 13 DAY)
     GROUP BY data ORDER BY data ASC',
    [$uid]
);
$adesao14_map = array_column($adesao14, 'pct', 'data');

$labels14 = []; $vals_adesao = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $labels14[] = date('d/m', strtotime($d));
    $vals_adesao[] = isset($adesao14_map[$d]) ? (int)$adesao14_map[$d] : null;
}

// Diário: energia e dor 14 dias
$diario14 = db_query(
    'SELECT data, energia, dor, humor FROM diario WHERE utilizador_id=? AND data>=DATE_SUB(CURDATE(),INTERVAL 13 DAY) ORDER BY data ASC',
    [$uid]
);
$diario14_map = array_column($diario14, null, 'data');
$vals_energia = []; $vals_dor = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $vals_energia[] = isset($diario14_map[$d]) ? (int)$diario14_map[$d]['energia'] : null;
    $vals_dor[]     = isset($diario14_map[$d]) ? (int)$diario14_map[$d]['dor'] : null;
}

// Humor 14 dias
$humorMap = ['otimo'=>5,'bom'=>4,'razoavel'=>3,'mau'=>2,'pessimo'=>1];
$vals_humor = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $vals_humor[] = isset($diario14_map[$d]) ? ($humorMap[$diario14_map[$d]['humor']] ?? null) : null;
}

// Labels 7 dias
$labels7 = [];
for ($i = 6; $i >= 0; $i--) {
    $labels7[] = date('D', strtotime("-$i days"));
}

// Peso — últimos 30 dias
$peso30 = db_query('SELECT data, peso FROM peso_historico WHERE utilizador_id=? AND data>=DATE_SUB(CURDATE(),INTERVAL 29 DAY) ORDER BY data ASC', [$uid]);
$peso30_map = array_column($peso30, 'peso', 'data');
$labels30 = []; $vals_peso = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $labels30[] = date('d/m', strtotime($d));
    $vals_peso[] = isset($peso30_map[$d]) ? (float)$peso30_map[$d] : null;
}

// Tensão — últimos 30 dias
$tensao30 = db_query('SELECT data, sistolica, diastolica FROM tensao_arterial WHERE utilizador_id=? AND data>=DATE_SUB(CURDATE(),INTERVAL 29 DAY) ORDER BY data ASC', [$uid]);
$tensao30_map = [];
foreach ($tensao30 as $t) { $tensao30_map[$t['data']] = $t; }
$vals_sis = []; $vals_dia = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $vals_sis[] = isset($tensao30_map[$d]) ? (int)$tensao30_map[$d]['sistolica']  : null;
    $vals_dia[] = isset($tensao30_map[$d]) ? (int)$tensao30_map[$d]['diastolica'] : null;
}

include 'includes/header.php';
?>

<div class="stats-grid four-cols">
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Medicamentos</div><div class="stat-card-icon icon-blue"><i class="fas fa-pills"></i></div></div><div class="stat-card-value"><?php echo $total_meds ?></div><div class="stat-card-sub">ativos</div></div>
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Adesão (30d)</div><div class="stat-card-icon icon-green"><i class="fas fa-chart-line"></i></div></div><div class="stat-card-value"><?php echo $adesao_media ?>%</div><div class="stat-card-sub">média</div></div>
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Consultas</div><div class="stat-card-icon icon-yellow"><i class="fas fa-calendar"></i></div></div><div class="stat-card-value"><?php echo $total_consultas ?></div><div class="stat-card-sub">agendadas</div></div>
  <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Registos Diário</div><div class="stat-card-icon icon-purple"><i class="fas fa-book"></i></div></div><div class="stat-card-value"><?php echo $total_diario ?></div><div class="stat-card-sub">entradas</div></div>
</div>

<div class="charts-grid three-cols">
  <div class="chart-box"><div class="chart-title"><i class="fas fa-chart-line"></i> Adesão — 14 dias (%)</div><canvas id="chartAdesao14" height="180"></canvas></div>
  <div class="chart-box"><div class="chart-title"><i class="fas fa-heartbeat"></i> Energia vs Dor — 14 dias</div><canvas id="chartEnergiaDor" height="180"></canvas></div>
  <div class="chart-box"><div class="chart-title"><i class="fas fa-smile"></i> Humor — 14 dias</div><canvas id="chartHumor" height="180"></canvas></div>
  <div class="chart-box"><div class="chart-title"><i class="fas fa-weight"></i> Peso — 30 dias (kg)</div><canvas id="chartPeso30" height="180"></canvas></div>
  <div class="chart-box"><div class="chart-title"><i class="fas fa-stethoscope"></i> Tensão Arterial — 30 dias</div><canvas id="chartTensao30" height="180"></canvas></div>
  <div></div>
</div>

<script>
const labels14 = <?php echo json_encode($labels14) ?>;

new Chart(document.getElementById('chartAdesao14').getContext('2d'), {
  type: 'line',
  data: {
    labels: labels14,
    datasets: [{
      label: 'Adesão %',
      data: <?php echo json_encode($vals_adesao) ?>,
      borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.08)',
      fill: true, tension: .4, borderWidth: 2.5, spanGaps: true
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } },
    scales: { y: { min:0, max:100, ticks: { callback: v=>v+'%' }, grid: { color:'#f1f5f9' } }, x: { grid: { display:false } } } }
});

new Chart(document.getElementById('chartEnergiaDor').getContext('2d'), {
  type: 'line',
  data: {
    labels: labels14,
    datasets: [
      { label: 'Energia', data: <?php echo json_encode($vals_energia) ?>, borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,.06)', fill:true, tension:.4, borderWidth:2, spanGaps:true },
      { label: 'Dor',     data: <?php echo json_encode($vals_dor) ?>,     borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,.06)',  fill:true, tension:.4, borderWidth:2, spanGaps:true }
    ]
  },
  options: { responsive: true, plugins: { legend: { position:'top' } },
    scales: { y: { min:0, max:10, grid:{ color:'#f1f5f9' } }, x: { grid:{ display:false } } } }
});

new Chart(document.getElementById('chartHumor').getContext('2d'), {
  type: 'line',
  data: {
    labels: labels14,
    datasets: [{
      label: 'Humor (1-5)',
      data: <?php echo json_encode($vals_humor) ?>,
      borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.08)',
      fill: true, tension: .4, borderWidth: 2.5, spanGaps: true
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } },
    scales: { y: { min:1, max:5, ticks: { callback: v => ({1:'😞',2:'😕',3:'😐',4:'🙂',5:'😄'}[v]||v) }, grid: { color:'#f1f5f9' } }, x: { grid: { display:false } } } }
});
new Chart(document.getElementById('chartPeso30').getContext('2d'), {
  type: 'line',
  data: {
    labels: <?php echo json_encode($labels30) ?>,
    datasets: [{
      label: 'Peso (kg)',
      data: <?php echo json_encode($vals_peso) ?>,
      borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.08)',
      fill: true, tension: .4, borderWidth: 2, pointRadius: 2, spanGaps: true
    }]
  },
  options: { responsive: true, animation: false, plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: false, ticks: { callback: v => v+' kg', font: { size:10 } }, grid: { color:'#f1f5f9' } }, x: { grid: { display:false }, ticks: { font: { size:9 }, maxTicksLimit: 8 } } } }
});

new Chart(document.getElementById('chartTensao30').getContext('2d'), {
  type: 'line',
  data: {
    labels: <?php echo json_encode($labels30) ?>,
    datasets: [
      { label: 'Sistólica',  data: <?php echo json_encode($vals_sis) ?>, borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,.05)',  fill:false, tension:.4, borderWidth:2, pointRadius:2, spanGaps:true },
      { label: 'Diastólica', data: <?php echo json_encode($vals_dia) ?>, borderColor:'#10b981', backgroundColor:'rgba(16,185,129,.05)', fill:false, tension:.4, borderWidth:2, pointRadius:2, spanGaps:true }
    ]
  },
  options: { responsive: true, animation: false, plugins: { legend: { position:'top', labels: { font: { size:10 }, boxWidth:16, padding:8 } } },
    scales: { y: { beginAtZero: false, grid: { color:'#f1f5f9' }, ticks: { font: { size:10 } } }, x: { grid: { display:false }, ticks: { font: { size:9 }, maxTicksLimit: 8 } } } }
});
</script>

<?php include 'includes/footer.php'; ?>
