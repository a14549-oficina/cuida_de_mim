<?php
/*Cuida de Mim — Header / Sidebar / Topbar*/
if (!defined('BASE')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

// Modo Cuidador: verifica se está a ver dashboard de utente 
$__cuidador_ativo = false;
$__utente_nome    = '';
if (logged_in() && isset($_SESSION['cuidador_utente_id'])) {
    try {
        $__utente = db_row('SELECT nome FROM utilizadores WHERE id = ?', [$_SESSION['cuidador_utente_id']]);
        if ($__utente) { $__cuidador_ativo = true; $__utente_nome = $__utente['nome']; }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Cuida de Mim — <?php echo $page_title ?? 'App de Saúde'; ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<?php if(function_exists("csrf_token")): ?><meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token()) ?>"><?php endif ?>
<meta name="theme-color" content="#2563eb">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Cuida de Mim">
<link rel="manifest" href="<?php echo BASE ?>manifest.json">
<link rel="apple-touch-icon" href="<?php echo BASE ?>icons/icon-192.png">
<link rel="stylesheet" href="<?php echo BASE ?>css/style.css?v=<?php echo filemtime(ROOT_PATH.'/css/style.css') ?>">
<script>
  const BASE_ROOT = '<?php echo BASE ?>';

</script>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('<?php echo BASE ?>sw.js').catch(() => {});
  });
}
</script>
</head>
<body>

<! SIDEBAR BACKDROP (mobile) >
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

<! SIDEBAR >
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon"><i class="fas fa-heartbeat"></i></div>
    <div><div class="logo-text">Cuida de Mim</div><div class="logo-sub" style="color:rgba(255,255,255,.55)!important">Saúde</div></div>
  </div>

  <div class="sidebar-section">
    <div class="sidebar-section-label">Visão Geral</div>
    <a href="<?php echo BASE ?>index.php" class="sidebar-link <?php echo ($page_id==='dashboard')?'active':'' ?>"><i class="fas fa-home"></i> Início</a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-section-label">Saúde Diária</div>
    <a href="<?php echo BASE ?>medicamentos/tomas.php" class="sidebar-link <?php echo ($page_id==='tomas')?'active':'' ?>"><i class="fas fa-pills"></i> Tomas do Dia</a>
    <a href="<?php echo BASE ?>diario/index.php" class="sidebar-link <?php echo ($page_id==='diario')?'active':'' ?>"><i class="fas fa-book-medical"></i> Diário de Saúde</a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-section-label">Saúde Física</div>
    <a href="<?php echo BASE ?>saude/peso.php" class="sidebar-link <?php echo ($page_id==='peso')?'active':'' ?>"><i class="fas fa-weight"></i> Peso & IMC</a>
    <a href="<?php echo BASE ?>saude/tensao.php" class="sidebar-link <?php echo ($page_id==='tensao')?'active':'' ?>"><i class="fas fa-heartbeat"></i> Tensão Arterial</a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-section-label">Gestão</div>
    <a href="<?php echo BASE ?>medicamentos/index.php" class="sidebar-link <?php echo ($page_id==='medicamentos')?'active':'' ?>"><i class="fas fa-prescription-bottle"></i> Medicamentos</a>
    <a href="<?php echo BASE ?>consultas/index.php" class="sidebar-link <?php echo ($page_id==='consultas')?'active':'' ?>"><i class="fas fa-calendar-check"></i> Consultas</a>
    <a href="<?php echo BASE ?>lembretes.php" class="sidebar-link <?php echo ($page_id==='lembretes')?'active':'' ?>">
      <i class="fas fa-bell"></i> Lembretes
      <span id="badge-sidebar" style="background:#ef4444;color:#fff;font-size:9px;font-weight:700;padding:1px 5px;border-radius:99px;margin-left:4px;display:none">0</span>
    </a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-section-label">Análise</div>
    <a href="<?php echo BASE ?>estatisticas.php" class="sidebar-link <?php echo ($page_id==='estatisticas')?'active':'' ?>"><i class="fas fa-chart-line"></i> Estatísticas</a>
    <a href="<?php echo BASE ?>historico/calendario.php" class="sidebar-link <?php echo ($page_id==='calendario')?'active':'' ?>"><i class="fas fa-calendar-alt"></i> Calendário</a>
    <a href="<?php echo BASE ?>historico/relatorio.php" class="sidebar-link <?php echo ($page_id==='relatorio')?'active':'' ?>"><i class="fas fa-file-medical"></i> Relatório IA</a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-section-label">Conta</div>
    <a href="<?php echo BASE ?>perfil.php" class="sidebar-link <?php echo ($page_id==='perfil')?'active':'' ?>"><i class="fas fa-user-circle"></i> Perfil</a>
    <a href="<?php echo BASE ?>configuracoes.php" class="sidebar-link <?php echo ($page_id==='configuracoes')?'active':'' ?>"><i class="fas fa-cog"></i> Configurações</a>
    <a href="<?php echo BASE ?>cuidador/index.php" class="sidebar-link <?php echo ($page_id==='cuidador')?'active':'' ?>"><i class="fas fa-user-shield"></i> Modo Cuidador</a>
  </div>

  <div class="sidebar-footer">
    <?php
      $__u = logged_in() ? user() : [];
      $__nome = $__u['nome'] ?? 'Utilizador';
      $__partes = array_filter(array_values(array_filter(explode(' ', $__nome))));
      $__primeiro_ultimo = count($__partes) > 1 ? reset($__partes) . ' ' . end($__partes) : reset($__partes);
      $__iniciais    = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice($__partes, 0, 2)));
      $__foto_perfil = $__u['foto_perfil'] ?? null;
      $__foto_url    = $__foto_perfil ? BASE . 'uploads/perfil/' . htmlspecialchars($__foto_perfil) : null;
    ?>
    <a href="<?php echo BASE ?>perfil.php" class="sidebar-user" style="text-decoration:none">
      <div class="user-avatar" style="<?php echo $__foto_url ? 'padding:0;overflow:hidden;' : '' ?>">
        <?php if ($__foto_url): ?>
          <img src="<?php echo $__foto_url ?>" alt="Foto" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
        <?php else: ?>
          <?php echo htmlspecialchars($__iniciais) ?>
        <?php endif ?>
      </div>
      <div><span class="user-name"><?php echo htmlspecialchars($__primeiro_ultimo) ?></span><span class="user-role">Utilizador</span></div>
    </a>
  </div>
</aside>

<! TOPBAR >
<div class="main-wrapper">
<div class="topbar">
  <div class="topbar-left">
    <span class="page-title"><?php echo $page_title ?? 'Dashboard'; ?></span>
  </div>
  <div class="topbar-right">
    <a href="<?php echo BASE ?>medicamentos/tomas.php" class="topbar-btn" title="Tomas de hoje">
      <i class="fas fa-pills"></i>
    </a>
    <button class="topbar-btn pulsar" id="bell-btn" onclick="toggleNotifPanel()" title="Lembretes">
      <i class="fas fa-bell"></i>
      <span class="badge-count" id="bell-count" style="display:none">0</span>
    </button>
    <a href="<?php echo BASE ?>logout.php" class="btn btn-ghost btn-sm topbar-sair"><i class="fas fa-sign-out-alt"></i><span class="sair-texto"> Sair</span></a>
    <button class="topbar-menu-btn" onclick="event.stopPropagation();toggleSidebar()" title="Menu"><i class="fas fa-bars"></i></button>
  </div>
</div>

<! NOTIF PANEL >
<div class="notif-panel" id="notifPanel">
  <div style="padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
    <span style="font-weight:700;font-size:14px"><i class="fas fa-bell" style="color:var(--primary);margin-right:8px"></i>Notificações</span>
    <button onclick="marcarTodasLidas()" class="btn btn-sm btn-ghost">Marcar todas lidas</button>
  </div>
  <div id="notif-list" style="max-height:400px;overflow-y:auto"></div>
</div>

<?php if ($__cuidador_ativo): ?>
<! Banner modo cuidador >
<div class="cuidador-banner" style="margin:16px 28px 0">
  <i class="fas fa-user-shield"></i>
  <div>A ver como cuidador de <strong><?php echo htmlspecialchars($__utente_nome) ?></strong></div>
  <a href="<?php echo BASE ?>cuidador/sair.php" class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;margin-left:auto">Sair do modo cuidador</a>
</div>
<?php endif ?>

<! PAGE CONTENT >
<div class="page-content">
