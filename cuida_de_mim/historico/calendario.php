<?php
$page_id = 'calendario';
$page_title = 'Calendário';
require_once __DIR__ . '/../config/config.php';
$uid = user_id();

// Mês/ano actuais (por GET ou hoje)
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
if ($mes < 1) { $mes = 12; $ano--; }
if ($mes > 12) { $mes = 1; $ano++; }

$mesPad = str_pad($mes, 2, '0', STR_PAD_LEFT);
$inicio = "$ano-$mesPad-01";
$fim    = date('Y-m-t', strtotime($inicio));

// Consultas do mês
$consultas = db_query(
    'SELECT DAY(datahora) dia FROM consultas WHERE utilizador_id=? AND DATE(datahora) BETWEEN ? AND ?',
    [$uid, $inicio, $fim]
);
$diasConsulta = array_column($consultas, 'dia');

// Lembretes não lidos do mês
$lembretes = db_query(
    'SELECT DAY(datahora) dia FROM lembretes WHERE utilizador_id=? AND lido=0 AND DATE(datahora) BETWEEN ? AND ?',
    [$uid, $inicio, $fim]
);
$diasLembrete = array_column($lembretes, 'dia');

// Medicamentos tomados no mês
$medicamentos_cal = db_query(
    'SELECT DAY(data) dia FROM tomas WHERE utilizador_id=? AND tomado=1 AND data BETWEEN ? AND ? GROUP BY DAY(data)',
    [$uid, $inicio, $fim]
);
$diasMedicamento = array_column($medicamentos_cal, 'dia');

// Diário do mês
$diario = db_query(
    'SELECT DAY(data) dia, humor FROM diario WHERE utilizador_id=? AND data BETWEEN ? AND ?',
    [$uid, $inicio, $fim]
);
$diasDiario = array_column($diario, 'humor', 'dia');

$meses_pt = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

$mesPrev = $mes - 1; $anoPrev = $ano;
if ($mesPrev < 1) { $mesPrev = 12; $anoPrev--; }
$mesNext = $mes + 1; $anoNext = $ano;
if ($mesNext > 12) { $mesNext = 1; $anoNext++; }

include '../includes/header.php';
?>

<div style="display:flex;align-items:center;gap:16px;margin-bottom:16px">
  <a href="?mes=<?php echo $mesPrev ?>&ano=<?php echo $anoPrev ?>" class="btn btn-ghost btn-sm"><i class="fas fa-chevron-left"></i></a>
  <span style="font-size:15px;font-weight:700;min-width:180px;text-align:center"><?php echo $meses_pt[$mes] . ' ' . $ano ?></span>
  <a href="?mes=<?php echo $mesNext ?>&ano=<?php echo $anoNext ?>" class="btn btn-ghost btn-sm"><i class="fas fa-chevron-right"></i></a>
  <a href="calendario.php" class="btn btn-ghost btn-sm">Hoje</a>
</div>

<! Legenda >
<div style="display:flex;gap:16px;margin-bottom:16px;flex-wrap:wrap">
  <span style="font-size:12px;display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block"></span> Consulta</span>
  <span style="font-size:12px;display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block"></span> Lembrete</span>
  <span style="font-size:12px;display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block"></span> Diário</span>
  <span style="font-size:12px;display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:50%;background:#8b5cf6;display:inline-block"></span> Medicamentos</span>
</div>

<div class="card">
  <div class="cal-grid">
    <?php
    $diasSemana = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
    foreach ($diasSemana as $d) echo "<div class=\"cal-header-cell\">$d</div>";

    $primeiroDia = date('w', strtotime($inicio));
    $offset = ($primeiroDia + 6) % 7;
    for ($i = 0; $i < $offset; $i++) echo '<div class="cal-cell other-month"></div>';

    $diasNoMes = (int)date('t', strtotime($inicio));
    $hoje = (int)date('j');
    $mesHoje = (int)date('n');
    $anoHoje = (int)date('Y');

    for ($d = 1; $d <= $diasNoMes; $d++) {
        $isHoje = ($d === $hoje && $mes === $mesHoje && $ano === $anoHoje);
        $temC = in_array($d, $diasConsulta);
        $temL = in_array($d, $diasLembrete);
        $temD = isset($diasDiario[$d]);
        $temM = in_array($d, $diasMedicamento);
        echo "<div class=\"cal-cell ".($isHoje?'today':'')."\">";
        echo "<div class=\"cal-day\">$d</div>";
        if ($temC) echo '<span class="cal-dot" style="background:#f59e0b" title="Consulta"></span>';
        if ($temL) echo '<span class="cal-dot" style="background:#ef4444" title="Lembrete"></span>';
        if ($temD) echo '<span class="cal-dot" style="background:#10b981" title="Diário"></span>';
        if ($temM) echo '<span class="cal-dot" style="background:#8b5cf6" title="Medicamentos"></span>';
        echo "</div>";
    }

    $total = $offset + $diasNoMes;
    $restam = (7 - ($total % 7)) % 7;
    for ($i = 0; $i < $restam; $i++) echo '<div class="cal-cell other-month"></div>';
    ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
