<?php
/*Cuida de Mim — Pré-visualização do Relatório
 *Mostra o relatório em HTML. O botão "Guardar PDF" descarrega via relatorio_pdf.php*/

$page_id    = 'relatorio';
$page_title = 'Relatório PDF';
require_once __DIR__ . '/../config/config.php';
$uid = user_id();
$u   = user();

// Período
$dias  = (int)($_GET['dias'] ?? 30);
$dias  = in_array($dias, [7, 30, 90]) ? $dias : 30;
$desde = date('Y-m-d', strtotime("-{$dias} days"));

// Dados
$medicamentos  = db_query('SELECT * FROM medicamentos WHERE utilizador_id=? AND ativo=1', [$uid]);
$tomas_periodo = db_query(
    'SELECT data, SUM(tomado) tomados, COUNT(*) total
     FROM tomas WHERE utilizador_id=? AND data>=? GROUP BY data ORDER BY data ASC',
    [$uid, $desde]
);
$adesao_pct = 0;
if ($tomas_periodo) {
    $tot = array_sum(array_column($tomas_periodo, 'total'));
    $tom = array_sum(array_column($tomas_periodo, 'tomados'));
    $adesao_pct = $tot > 0 ? round($tom / $tot * 100) : 0;
}
$consultas     = db_query('SELECT * FROM consultas WHERE utilizador_id=? AND datahora>=? ORDER BY datahora ASC', [$uid, $desde.' 00:00:00']);
$diario        = db_query('SELECT * FROM diario WHERE utilizador_id=? AND data>=? ORDER BY data ASC', [$uid, $desde]);
$pesos         = db_query('SELECT * FROM peso_historico WHERE utilizador_id=? AND data>=? ORDER BY data ASC', [$uid, $desde]);
$tensoes       = db_query('SELECT * FROM tensao_arterial WHERE utilizador_id=? AND data>=? ORDER BY data ASC, hora ASC', [$uid, $desde]);
$ultimo_peso   = $pesos ? end($pesos) : null;
$primeiro_peso = $pesos ? $pesos[0]   : null;
$diff_peso     = ($ultimo_peso && $primeiro_peso && $primeiro_peso['peso'] != $ultimo_peso['peso'])
    ? round($ultimo_peso['peso'] - $primeiro_peso['peso'], 1) : null;
$energia_media = $diario ? round(array_sum(array_column($diario, 'energia')) / count($diario), 1) : null;
$dor_media     = $diario ? round(array_sum(array_column($diario, 'dor'))     / count($diario), 1) : null;

$nome_partes = explode(' ', trim($u['nome']));
$nome_curto  = count($nome_partes) > 1 ? $nome_partes[0].' '.end($nome_partes) : $nome_partes[0];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Relatório de Saúde — <?php echo htmlspecialchars($nome_curto) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',Arial,sans-serif; font-size:12pt; color:#1e293b; background:#e2e8f0; }

/* Barra de controlo */
.no-print {
    position:sticky; top:0; z-index:100;
    padding:10px 16px; background:#1e293b;
    display:flex; gap:8px; align-items:center; flex-wrap:wrap;
    box-shadow:0 2px 8px rgba(0,0,0,.3);
    overflow:hidden;
}
.btn-back  { background:transparent; border:1.5px solid #475569; border-radius:8px; padding:7px 12px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none; color:#94a3b8; display:flex; align-items:center; gap:6px; white-space:nowrap; flex-shrink:0; }
.btn-back:hover { background:#334155; color:#fff; }
.btn-save  { background:#2563eb; color:#fff; border:none; border-radius:8px; padding:8px 16px; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:7px; text-decoration:none; box-shadow:0 2px 8px rgba(37,99,235,.4); transition:background .15s; white-space:nowrap; flex-shrink:0; }
.btn-save:hover { background:#1d4ed8; }
.period-btns { margin-left:auto; display:flex; gap:6px; flex-shrink:0; }
.period-btn { background:#334155; border:1.5px solid #475569; border-radius:7px; padding:6px 12px; font-size:12px; font-weight:600; color:#94a3b8; cursor:pointer; text-decoration:none; transition:.15s; white-space:nowrap; }
.period-btn:hover { background:#475569; color:#fff; }
.period-btn.active { background:#2563eb; border-color:#2563eb; color:#fff; }
@media(max-width:520px){
  .no-print{padding:8px 10px;gap:6px;flex-wrap:wrap}
  /* linha 1: Voltar + Guardar PDF */
  .btn-back,.btn-save{flex:1;justify-content:center;padding:8px 10px;font-size:12px}
  /* linha 2: período — ocupa linha inteira */
  .period-btns{margin-left:0;width:100%;justify-content:stretch;gap:6px}
  .period-btn{flex:1;text-align:center;padding:7px 4px;font-size:12px}
}

/* Folha de papel */
html,body{overflow-x:hidden;max-width:100vw}
.report-wrap { max-width:900px; margin:24px auto; padding:0 10px 40px; overflow-x:hidden; }
.report { background:#fff; border-radius:12px; padding:32px 28px; box-shadow:0 4px 24px rgba(0,0,0,.12); overflow:hidden; }

/* Cabeçalho */
.report-header { display:flex; justify-content:space-between; align-items:flex-start; padding-bottom:20px; border-bottom:2px solid #2563eb; margin-bottom:28px; flex-wrap:wrap; gap:12px; }
.report-header > div { min-width:0; }
.report-logo { font-size:16pt; font-weight:800; color:#2563eb; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.report-logo-icon { width:40px; height:40px; background:#2563eb; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; flex-shrink:0; }
.report-meta { text-align:right; font-size:10pt; color:#64748b; line-height:1.7; word-break:break-word; }
.report-patient { font-size:13pt; font-weight:800; color:#0f172a; }

/* Secções */
.section { margin-bottom:28px; }
.section-title { font-size:13pt; font-weight:800; color:#0f172a; padding:10px 16px; background:#f1f5f9; border-left:4px solid #2563eb; border-radius:0 8px 8px 0; margin-bottom:16px; }

/* Cards de métricas */
.metrics-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
.metric { text-align:center; background:#f8fafc; border-radius:10px; padding:16px 10px; border:1px solid #e2e8f0; }
.metric-value { font-size:24pt; font-weight:800; color:#0f172a; line-height:1.1; }
.metric-label { font-size:8.5pt; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-top:6px; }
.adesao-bar { height:8px; background:#e2e8f0; border-radius:99px; overflow:hidden; margin-top:8px; }
.adesao-fill { height:100%; border-radius:99px; }

/* Tabelas — scroll no mobile */
.table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
table { width:100%; border-collapse:collapse; font-size:10.5pt; }
th { background:#f1f5f9; padding:9px 12px; text-align:left; font-weight:700; font-size:9pt; color:#475569; text-transform:uppercase; letter-spacing:.04em; border-bottom:2px solid #e2e8f0; white-space:nowrap; }
td { padding:9px 12px; border-bottom:1px solid #f1f5f9; }
tr:nth-child(even) td { background:#f8fafc; }
tr:last-child td { border-bottom:none; }

/* Badges */
.badge { display:inline-block; padding:2px 8px; border-radius:99px; font-size:9pt; font-weight:700; }
.badge-ok   { background:#d1fae5; color:#065f46; }
.badge-warn { background:#fef3c7; color:#92400e; }
.badge-bad  { background:#fee2e2; color:#991b1b; }

.report-footer { margin-top:32px; padding-top:16px; border-top:1px solid #e2e8f0; font-size:9pt; color:#94a3b8; text-align:center; }

@media(max-width:600px){
  .report-wrap{margin:8px auto;padding:0 4px 20px}
  .report{padding:14px 10px;border-radius:8px}
  .report-header{flex-direction:column;gap:8px}
  .report-meta{text-align:left;font-size:9pt}
  .report-patient{font-size:11pt}
  .report-logo{font-size:13pt}
  /* 4 métricas → 2×2 */
  .metrics-grid{grid-template-columns:1fr 1fr;gap:8px}
  .metric-value{font-size:14pt}
  .metric{padding:10px 6px}
  .section-title{font-size:11pt;padding:8px 12px}
  /* tabelas com scroll horizontal */
  table{font-size:8.5pt;display:block;overflow-x:auto;-webkit-overflow-scrolling:touch;width:100%}
  th,td{padding:5px 6px;white-space:nowrap}
}
@media print { .no-print { display:none !important; } .report-wrap { margin:0; padding:0; } .report { box-shadow:none; border-radius:0; } }
</style>
</head>
<body>

<! BARRA DE CONTROLO >
<div class="no-print">
    <a href="relatorio.php" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
    <a href="relatorio_pdf.php?dias=<?php echo $dias ?>" class="btn-save">
        <i class="fas fa-file-pdf"></i> Guardar PDF
    </a>
    <div class="period-btns">
        <a href="?dias=7"  class="period-btn <?php echo $dias===7  ? 'active':'' ?>">7 dias</a>
        <a href="?dias=30" class="period-btn <?php echo $dias===30 ? 'active':'' ?>">30 dias</a>
        <a href="?dias=90" class="period-btn <?php echo $dias===90 ? 'active':'' ?>">90 dias</a>
    </div>
</div>

<! RELATÓRIO >
<div class="report-wrap">
<div class="report">

  <! Cabeçalho >
  <div class="report-header">
    <div>
      <div class="report-logo">
        <div class="report-logo-icon"><i class="fas fa-heartbeat"></i></div>
        Cuida de Mim
      </div>
      <div style="font-size:10pt;color:#64748b;margin-top:4px">Relatório de Saúde Pessoal</div>
    </div>
    <div class="report-meta">
      <div class="report-patient"><?php echo htmlspecialchars($u['nome']) ?></div>
      <div><?php echo htmlspecialchars($u['email']) ?></div>
      <div>Período: <?php echo date('d/m/Y', strtotime($desde)) ?> – <?php echo date('d/m/Y') ?></div>
      <div>Gerado em: <?php echo date('d/m/Y \à\s H:i') ?>h</div>
    </div>
  </div>

  <! RESUMO >
  <div class="section">
    <div class="section-title"> Resumo do Período</div>
    <div class="metrics-grid">
      <div class="metric">
        <div class="metric-value" style="color:<?php echo $adesao_pct>=80?'#059669':($adesao_pct>=50?'#d97706':'#dc2626') ?>"><?php echo $adesao_pct ?>%</div>
        <div class="metric-label">Adesão à medicação</div>
        <div class="adesao-bar"><div class="adesao-fill" style="width:<?php echo $adesao_pct ?>%;background:<?php echo $adesao_pct>=80?'#10b981':($adesao_pct>=50?'#f59e0b':'#ef4444') ?>"></div></div>
      </div>
      <div class="metric"><div class="metric-value"><?php echo count($medicamentos) ?></div><div class="metric-label">Medicamentos ativos</div></div>
      <div class="metric"><div class="metric-value"><?php echo count($consultas) ?></div><div class="metric-label">Consultas</div></div>
      <div class="metric"><div class="metric-value"><?php echo count($sintomas) ?></div><div class="metric-label">Sintomas registados</div></div>
    </div>
    <?php if ($energia_media !== null): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="metric"><div class="metric-value"><?php echo $energia_media ?>/10</div><div class="metric-label">Energia média</div></div>
      <div class="metric"><div class="metric-value"><?php echo $dor_media ?>/10</div><div class="metric-label">Dor média</div></div>
    </div>
    <?php endif ?>
  </div>

  <! MEDICAMENTOS >
  <?php if ($medicamentos): ?>
  <div class="section">
    <div class="section-title"> Medicamentos Atuais</div>
    <table>
      <thead><tr><th>Medicamento</th><th>Dosagem</th><th>Forma</th><th>Horário</th><th>Frequência</th></tr></thead>
      <tbody>
      <?php foreach ($medicamentos as $m): ?>
        <tr>
          <td style="font-weight:600"><?php echo htmlspecialchars($m['nome']) ?></td>
          <td><?php echo htmlspecialchars($m['dosagem']) ?></td>
          <td><?php echo htmlspecialchars($m['forma']) ?></td>
          <td><?php echo substr($m['horario'],0,5) ?></td>
          <td><?php echo htmlspecialchars($m['frequencia']) ?></td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?php endif ?>

  <! DADOS FÍSICOS >
  <?php if ($pesos || $tensoes): ?>
  <div class="section">
    <div class="section-title"> Dados Físicos</div>
    <?php if ($ultimo_peso): ?>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px">
      <div class="metric"><div class="metric-value"><?php echo number_format($ultimo_peso['peso'],1) ?> kg</div><div class="metric-label">Peso atual</div></div>
      <?php if ($ultimo_peso['imc']): ?>
      <div class="metric"><div class="metric-value"><?php echo $ultimo_peso['imc'] ?></div><div class="metric-label">IMC</div></div>
      <?php endif ?>
      <?php if ($diff_peso !== null): ?>
      <div class="metric">
        <div class="metric-value" style="color:<?php echo $diff_peso<0?'#059669':($diff_peso>0?'#dc2626':'#64748b') ?>">
          <?php echo ($diff_peso>0?'+':'').number_format($diff_peso,1) ?> kg
        </div>
        <div class="metric-label">Variação no período</div>
      </div>
      <?php endif ?>
    </div>
    <?php endif ?>
    <?php if ($tensoes): $ult_t = end($tensoes); ?>
    <p style="font-size:10.5pt;font-weight:600">
      Última tensão arterial: <strong style="color:#2563eb"><?php echo $ult_t['sistolica'].'/'.$ult_t['diastolica'] ?> mmHg</strong>
      <?php if ($ult_t['pulsacao']): ?> · <?php echo $ult_t['pulsacao'] ?> bpm<?php endif ?>
    </p>
    <?php endif ?>
  </div>
  <?php endif ?>

  <! CONSULTAS >
  <?php if ($consultas): ?>
  <div class="section">
    <div class="section-title"> Consultas no Período</div>
    <table>
      <thead><tr><th>Data</th><th>Médico</th><th>Especialidade</th><th>Local</th><th>Notas</th></tr></thead>
      <tbody>
      <?php foreach ($consultas as $c): ?>
        <tr>
          <td style="white-space:nowrap"><?php echo date('d/m/Y H:i', strtotime($c['datahora'])) ?></td>
          <td style="font-weight:600"><?php echo htmlspecialchars($c['medico']) ?></td>
          <td><?php echo htmlspecialchars($c['especialidade']) ?></td>
          <td><?php echo htmlspecialchars($c['local'] ?? '—') ?></td>
          <td style="font-size:9.5pt;color:#64748b"><?php echo $c['notas'] ? mb_substr(htmlspecialchars($c['notas']),0,60).(mb_strlen($c['notas'])>60?'…':'') : '—' ?></td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?php endif ?>

  <! SINTOMAS >
  <?php if ($sintomas): ?>
  <div class="section">
    <div class="section-title"> Sintomas Registados</div>
    <table>
      <thead><tr><th>Data</th><th>Sintoma</th></tr></thead>
      <tbody>
      <?php foreach (array_slice($sintomas, 0, 20) as $s): ?>
        <tr>
          <td><?php echo date('d/m/Y', strtotime($s['data'])) ?></td>
          <td style="font-weight:600"><?php echo htmlspecialchars($s['tipo']) ?></td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?php endif ?>

  <div class="report-footer">
    <strong>Cuida de Mim</strong> — Relatório gerado automaticamente.<br>
    Este documento é um resumo informativo. Consulte sempre o seu médico para diagnóstico e tratamento.
  </div>

</div>
</div>
</body>
</html>
