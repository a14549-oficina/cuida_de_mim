<?php
require_once __DIR__ . '/config/config.php';
$uid = user_id();
$u   = user();

// Dados da BD
$medicamentos = db_query('SELECT * FROM medicamentos WHERE utilizador_id = ? AND ativo = 1 ORDER BY horario', [$uid]);
$tomas        = db_query('SELECT * FROM tomas WHERE utilizador_id = ? AND data = CURDATE()', [$uid]);
$tomas_map    = array_column($tomas, 'tomado', 'medicamento_id');
$consultas    = db_query('SELECT * FROM consultas WHERE utilizador_id = ? AND datahora >= NOW() ORDER BY datahora LIMIT 5', [$uid]);
$lembretes    = db_query('SELECT * FROM lembretes WHERE utilizador_id = ? AND lido = 0 ORDER BY datahora LIMIT 5', [$uid]);
$tomadas_total = array_sum(array_column($tomas, 'tomado'));
$total_meds    = count($medicamentos);
// Total de tomas esperadas hoje (pode diferir do total de meds se houver meds sem toma criada)
$total_tomas_hoje = max($total_meds, count($tomas));

// Streak
require_once __DIR__ . '/api/streak.php';
$streak = calcular_streak($uid);

// Último peso e tensão
$ultimo_peso   = db_row('SELECT peso, imc FROM peso_historico WHERE utilizador_id = ? ORDER BY data DESC LIMIT 1', [$uid]);
$ultima_tensao = db_row('SELECT sistolica, diastolica FROM tensao_arterial WHERE utilizador_id = ? ORDER BY data DESC, hora DESC LIMIT 1', [$uid]);

$partes_nome = explode(' ', trim($u['nome']));
$primeiro_ultimo = count($partes_nome) > 1 ? $partes_nome[0] . ' ' . end($partes_nome) : $partes_nome[0];
$page_id    = 'dashboard';
$page_title = 'Dashboard';
include 'includes/header.php';
?>

<div class="hero-banner">
  <div class="hero-title" id="hero-saudacao"><?php $h=date("H"); echo ($h<12?"Bom dia":($h<19?"Boa tarde":"Boa noite")); ?>, <?php echo htmlspecialchars($primeiro_ultimo) ?>!</div>
  <div class="hero-subtitle">Aqui está o resumo da sua saúde hoje.</div>
  <div class="hero-stats">
    <div class="hero-stat"><div class="hero-stat-label"><i class="fas fa-pills"></i> Medicamentos</div><div class="hero-stat-value"><?php echo $total_meds ?></div><div class="hero-stat-change">ativos</div></div>
    <div class="hero-stat"><div class="hero-stat-label"><i class="fas fa-check-circle"></i> Tomas Hoje</div><div class="hero-stat-value"><?php echo $tomadas_total ?>/<?php echo $total_meds ?></div><div class="hero-stat-change">em curso</div></div>
    <div class="hero-stat"><div class="hero-stat-label"><i class="fas fa-calendar-check"></i> Consultas</div><div class="hero-stat-value"><?php echo count($consultas) ?></div><div class="hero-stat-change">próximas</div></div>
  </div>
</div>

<! STREAK + MÉTRICAS RÁPIDAS >
<div class="stats-grid dash-stats" style="margin-bottom:24px">

  <! Streak — ocupa as 2 colunas >
  <?php
    $s_bg  = $streak['streak_atual'] === 0
              ? 'linear-gradient(135deg,#94a3b8,#64748b)'
              : 'linear-gradient(135deg,#f59e0b,#d97706)';
    $s_shadow = $streak['streak_atual'] === 0 ? 'none' : '0 4px 16px rgba(245,158,11,.3)';
    $s_emoji  = $streak['streak_atual'] > 0 ? '' : '';
  ?>
  <div style="background:<?php echo $s_bg ?>;border-radius:12px;padding:20px;color:#fff;display:flex;align-items:center;gap:16px;box-shadow:<?php echo $s_shadow ?>;min-height:100px" class="dash-streak">
    <div style="font-size:40px;line-height:1"><?php echo $s_emoji ?></div>
    <div style="flex:1">
      <div style="font-size:36px;font-weight:800;letter-spacing:-.03em;line-height:1"><?php echo $streak['streak_atual'] ?></div>
      <div style="font-size:13px;opacity:.85;font-weight:600;margin-top:2px"><?php echo $streak['streak_atual'] === 1 ? 'dia em sequência' : 'dias em sequência' ?></div>
      <div style="font-size:11px;opacity:.7;margin-top:4px">Recorde: <?php echo $streak['streak_max'] ?> <?php echo $streak['streak_max'] === 1 ? 'dia' : 'dias' ?></div>
    </div>
  </div>

  <! Peso >
  <a href="saude/peso.php" class="stat-card" style="text-decoration:none;display:block">
    <div class="stat-card-header">
      <div class="stat-card-label">Último Peso</div>
      <div class="stat-card-icon icon-blue"><i class="fas fa-weight"></i></div>
    </div>
    <?php if ($ultimo_peso): ?>
      <div class="stat-card-value"><?php echo number_format($ultimo_peso['peso'],1) ?> <span style="font-size:16px;font-weight:500">kg</span></div>
      <?php if ($ultimo_peso['imc']): ?>
        <div class="stat-card-sub">IMC <?php echo $ultimo_peso['imc'] ?></div>
      <?php endif ?>
    <?php else: ?>
      <div class="stat-card-value" style="font-size:18px;color:var(--text-muted)">Sem dados</div>
      <div class="stat-card-sub">Toque para registar</div>
    <?php endif ?>
  </a>

  <! Tensão >
  <a href="saude/tensao.php" class="stat-card" style="text-decoration:none;display:block">
    <div class="stat-card-header">
      <div class="stat-card-label">Tensão Arterial</div>
      <div class="stat-card-icon icon-red"><i class="fas fa-heartbeat"></i></div>
    </div>
    <?php if ($ultima_tensao): ?>
      <div class="stat-card-value" style="font-size:24px"><?php echo $ultima_tensao['sistolica'] ?>/<span style="font-size:18px"><?php echo $ultima_tensao['diastolica'] ?></span></div>
      <div class="stat-card-sub">mmHg</div>
    <?php else: ?>
      <div class="stat-card-value" style="font-size:18px;color:var(--text-muted)">Sem dados</div>
      <div class="stat-card-sub">Toque para registar</div>
    <?php endif ?>
  </a>

</div>

<! AÇÕES RÁPIDAS >
<div class="action-grid">
  <a href="medicamentos/tomas.php" class="action-card"><div class="action-card-icon"><i class="fas fa-pills"></i></div><div class="action-card-title">Tomas</div><div class="action-card-desc">Registar medicamentos</div></a>
  <a href="diario/index.php" class="action-card"><div class="action-card-icon" style="background:#d1fae5;color:#10b981"><i class="fas fa-book-medical"></i></div><div class="action-card-title">Diário</div><div class="action-card-desc">Como me sinto hoje</div></a>
  <a href="consultas/index.php" class="action-card"><div class="action-card-icon" style="background:#fef3c7;color:#f59e0b"><i class="fas fa-calendar-plus"></i></div><div class="action-card-title">Consulta</div><div class="action-card-desc">Agendar consulta</div></a>
  <a href="lembretes.php" class="action-card"><div class="action-card-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-bell"></i></div><div class="action-card-title">Lembretes</div><div class="action-card-desc">Gerir alertas</div></a>
  <a href="saude/peso.php" class="action-card"><div class="action-card-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-weight"></i></div><div class="action-card-title">Peso</div><div class="action-card-desc">Registar peso & IMC</div></a>
  <a href="saude/tensao.php" class="action-card"><div class="action-card-icon" style="background:#fee2e2;color:#ef4444"><i class="fas fa-heartbeat"></i></div><div class="action-card-title">Tensão</div><div class="action-card-desc">Registar tensão arterial</div></a>
  <a href="estatisticas.php" class="action-card"><div class="action-card-icon" style="background:#fff7ed;color:#ea580c"><i class="fas fa-chart-line"></i></div><div class="action-card-title">Estatísticas</div><div class="action-card-desc">Ver evolução</div></a>
  <a href="historico/relatorio.php" class="action-card"><div class="action-card-icon" style="background:#ecfdf5;color:#047857"><i class="fas fa-robot"></i></div><div class="action-card-title">Relatório IA</div><div class="action-card-desc">Análise automática</div></a>
</div>

<div class="two-col">
  <! TOMAS HOJE >
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-list-check"></i> Tomas de Hoje</div>
      <a href="medicamentos/tomas.php" class="btn btn-success btn-sm"><i class="fas fa-pills"></i> Ver todas</a>
    </div>
    <div style="padding:12px 16px">
      <?php if (!$medicamentos): ?>
        <div class="empty-state" style="padding:30px"><div class="empty-icon"><i class="fas fa-pills"></i></div><div class="empty-title">Sem medicamentos</div><div class="empty-text"><a href="medicamentos/index.php" class="btn btn-primary btn-sm">Adicionar</a></div></div>
      <?php else: ?>
        <?php foreach ($medicamentos as $m): ?>
          <?php $tomado = $tomas_map[$m['id']] ?? 0; ?>
          <form method="POST" action="medicamentos/tomas_action.php" style="display:contents">
            <input type="hidden" name="med_id" value="<?php echo $m['id'] ?>">
            <input type="hidden" name="tomado" value="<?php echo $tomado ? 0 : 1 ?>">
            <div class="dose-row" style="background:<?php echo $tomado ? '#f0fdf4' : 'var(--bg)' ?>">
              <button type="submit" class="dose-check <?php echo $tomado ? 'checked' : '' ?>" style="border:none;cursor:pointer">
                <?php echo $tomado ? '<i class="fas fa-check" style="font-size:11px"></i>' : '' ?>
              </button>
              <div class="dose-name"><?php echo htmlspecialchars($m['nome']) ?> <span class="dose-dose"><?php echo htmlspecialchars($m['dosagem']) ?></span><?php echo $tomado ? ' <span class="badge badge-success" style="font-size:10px">✓</span>' : '' ?></div>
              <div class="dose-time"><?php echo substr($m['horario'],0,5) ?></div>
            </div>
          </form>
        <?php endforeach ?>
      <?php endif ?>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px">
    <! LEMBRETES >
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-bell"></i> Lembretes Próximos</div><a href="lembretes.php" class="btn btn-ghost btn-sm">Ver todos</a></div>
      <?php if (!$lembretes): ?>
        <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px"><i class="fas fa-check-circle" style="color:var(--secondary)"></i> Sem lembretes pendentes</div>
      <?php else: ?>
        <?php $cores=['medicamento'=>'#3b82f6','consulta'=>'#f59e0b','exercicio'=>'#10b981','urgente'=>'#ef4444','geral'=>'#7c3aed']; ?>
        <?php foreach ($lembretes as $l): ?>
          <div class="lembrete-item nao-lido <?php echo $l['prioridade']==='urgente'?'urgente':'' ?>">
            <div style="display:flex;align-items:center;gap:12px">
              <div class="activity-avatar" style="background:<?php echo $cores[$l['tipo']]??'#64748b' ?>;width:30px;height:30px;font-size:12px"><i class="fas fa-bell"></i></div>
              <div style="flex:1"><div style="font-weight:700;font-size:13px"><?php echo htmlspecialchars($l['titulo']) ?></div><div style="font-size:11px;color:var(--text-muted)"><?php echo date('d/m H:i', strtotime($l['datahora'])) ?></div></div>
              <a href="lembretes_action.php?action=lida&id=<?php echo $l['id'] ?>" class="btn btn-sm btn-success" style="padding:3px 8px;font-size:11px"><i class="fas fa-check"></i></a>
            </div>
          </div>
        <?php endforeach ?>
      <?php endif ?>
    </div>

    <! CONSULTAS >
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-calendar"></i> Próximas Consultas</div><a href="consultas/index.php" class="btn btn-ghost btn-sm">+ Agendar</a></div>
      <div style="padding:12px 16px">
        <?php if (!$consultas): ?>
          <div class="empty-state" style="padding:24px"><div class="empty-icon" style="width:40px;height:40px;font-size:18px"><i class="fas fa-calendar-times"></i></div><div class="empty-title" style="font-size:14px">Sem consultas</div></div>
        <?php else: ?>
          <?php foreach ($consultas as $c): ?>
            <div class="activity-item">
              <div class="activity-avatar" style="background:#f59e0b"><i class="fas fa-stethoscope" style="font-size:12px"></i></div>
              <div class="activity-text"><strong><?php echo htmlspecialchars($c['medico']) ?></strong><br><span style="font-size:12px;color:var(--text-muted)"><?php echo htmlspecialchars($c['especialidade']) ?></span></div>
              <div class="activity-time"><?php echo date('d/m', strtotime($c['datahora'])) ?></div>
            </div>
          <?php endforeach ?>
        <?php endif ?>
      </div>
    </div>
  </div>
</div>

<script>
const hora = new Date().getHours();
const s = hora < 12 ? 'Bom dia' : hora < 18 ? 'Boa tarde' : 'Boa noite';
document.getElementById('hero-saudacao').textContent = s + ', <?php echo addslashes(htmlspecialchars($primeiro_ultimo)) ?>!';
</script>

<?php include 'includes/footer.php'; ?>
