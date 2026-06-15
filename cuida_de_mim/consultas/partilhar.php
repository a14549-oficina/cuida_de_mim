<?php
/*Cuida de Mim — Partilhar Consulta
 *GET  ?id=X          → gera/mostra o link de partilha
 *GET  ?token=XXXX    → página pública (sem login)*/
require_once __DIR__ . '/../config/config.php';

// PÁGINA PÚBLICA (sem login) 
if (isset($_GET['token']) && !isset($_GET['id'])) {
    $token = preg_replace('/[^a-f0-9]/', '', $_GET['token']);
    $p = db_row(
        'SELECT cp.*, c.medico, c.especialidade, c.local, c.datahora, c.notas, u.nome AS utente_nome
         FROM consultas_partilha cp
         INNER JOIN consultas c ON c.id = cp.consulta_id
         INNER JOIN utilizadores u ON u.id = cp.utilizador_id
         WHERE cp.token = ? AND cp.expira_em > NOW()',
        [$token]
    );
    if (!$p) {
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title>Link inválido</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f1f5f9}
        .box{background:#fff;border-radius:16px;padding:40px;text-align:center;max-width:400px;box-shadow:0 4px 24px rgba(0,0,0,.1)}</style>
        </head><body><div class="box"><i class="fas fa-link-slash" style="font-size:48px;color:#ef4444;margin-bottom:16px"></i>
        <h2 style="margin:0 0 8px">Link inválido ou expirado</h2>
        <p style="color:#64748b">Este link de partilha já não é válido.</p></div></body></html>';
        exit;
    }

    // Incrementa contagem de vistas
    db_exec('UPDATE consultas_partilha SET vistas = vistas + 1 WHERE token = ?', [$token]);

    $dt = new DateTime($p['datahora']);
    echo '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Consulta — ' . htmlspecialchars($p['medico']) . '</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:"Segoe UI",sans-serif;background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
    .card{background:#fff;border-radius:20px;padding:36px;max-width:480px;width:100%;box-shadow:0 8px 40px rgba(0,0,0,.1)}
    .logo{font-size:13px;font-weight:700;color:#2563eb;text-transform:uppercase;letter-spacing:.08em;margin-bottom:24px;display:flex;align-items:center;gap:8px}
    .logo i{background:#2563eb;color:#fff;width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px}
    h1{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:4px}
    .esp{font-size:14px;color:#64748b;margin-bottom:24px}
    .info-row{display:flex;align-items:center;gap:12px;padding:14px;background:#f8fafc;border-radius:12px;margin-bottom:10px}
    .info-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
    .info-label{font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px}
    .info-val{font-size:14px;font-weight:700;color:#0f172a}
    .notas{background:#fefce8;border:1px solid #fde68a;border-radius:12px;padding:14px;margin-top:10px;font-size:13.5px;color:#78350f}
    .footer{margin-top:24px;text-align:center;font-size:12px;color:#94a3b8}
    .footer a{color:#2563eb;text-decoration:none;font-weight:600}
    </style></head><body>
    <div class="card">
      <div class="logo"><div class="logo i"><i class="fas fa-heartbeat"></i></div> Cuida de Mim</div>
      <h1>' . htmlspecialchars($p['medico']) . '</h1>
      <div class="esp">' . htmlspecialchars($p['especialidade']) . '</div>
      <div class="info-row"><div class="info-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-calendar"></i></div><div><div class="info-label">Data</div><div class="info-val">' . $dt->format('d \d\e F \d\e Y') . '</div></div></div>
      <div class="info-row"><div class="info-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-clock"></i></div><div><div class="info-label">Hora</div><div class="info-val">' . $dt->format('H:i') . 'h</div></div></div>'
      . ($p['local'] ? '<div class="info-row"><div class="info-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-map-marker-alt"></i></div><div><div class="info-label">Local</div><div class="info-val">' . htmlspecialchars($p['local']) . '</div></div></div>' : '')
      . ($p['notas'] ? '<div class="notas"><i class="fas fa-sticky-note" style="margin-right:6px"></i>' . nl2br(htmlspecialchars($p['notas'])) . '</div>' : '')
      . '<div class="footer">Partilhado por <strong>' . htmlspecialchars($p['utente_nome']) . '</strong> via <a href="#">Cuida de Mim</a></div>
    </div></body></html>';
    exit;
}

// GERAR LINK (requer login) 
$uid = user_id();
$id  = (int)($_GET['id'] ?? 0);
$consulta = $id ? db_row('SELECT * FROM consultas WHERE id = ? AND utilizador_id = ?', [$id, $uid]) : null;
if (!$consulta) { header('Location: index.php'); exit; }

// Gera ou reutiliza token existente (válido por 7 dias)
$partilha = db_row(
    'SELECT * FROM consultas_partilha WHERE consulta_id = ? AND utilizador_id = ? AND expira_em > NOW() ORDER BY criado_em DESC LIMIT 1',
    [$id, $uid]
);
if (!$partilha) {
    $token = bin2hex(random_bytes(16)); // 32 chars hex
    $expira = date('Y-m-d H:i:s', strtotime('+7 days'));
    $pid = db_insert(
        'INSERT INTO consultas_partilha (consulta_id, utilizador_id, token, expira_em) VALUES (?, ?, ?, ?)',
        [$id, $uid, $token, $expira]
    );
    $partilha = ['token' => $token, 'expira_em' => $expira, 'vistas' => 0];
}

// URL pública
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . BASE_URL . '/consultas/partilhar.php?token=' . $partilha['token'];

$page_id    = 'consultas';
$page_title = 'Partilhar Consulta';
include '../includes/header.php';
?>

<div style="max-width:600px;margin:0 auto">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-share-alt"></i> Partilhar Consulta</div>
      <a href="index.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
    <div class="card-body">

      <!-- Resumo da consulta -->
      <div style="background:var(--bg);border-radius:12px;padding:16px;margin-bottom:20px">
        <div style="font-weight:700;font-size:15px;margin-bottom:6px"><?php echo htmlspecialchars($consulta['medico']) ?></div>
        <div style="font-size:13px;color:var(--text-muted)"><?php echo htmlspecialchars($consulta['especialidade']) ?></div>
        <div style="font-size:13px;color:var(--text-muted);margin-top:4px"><i class="fas fa-calendar" style="width:14px"></i> <?php echo date('d/m/Y \à\s H:i', strtotime($consulta['datahora'])) ?>h</div>
        <?php if ($consulta['local']): ?><div style="font-size:13px;color:var(--text-muted);margin-top:4px"><i class="fas fa-map-marker-alt" style="width:14px"></i> <?php echo htmlspecialchars($consulta['local']) ?></div><?php endif ?>
      </div>

      <!-- Link gerado -->
      <div style="margin-bottom:16px">
        <div style="font-size:13px;font-weight:600;margin-bottom:8px;color:var(--text)"><i class="fas fa-link" style="color:var(--primary);margin-right:6px"></i>Link de partilha (válido por 7 dias)</div>
        <div class="share-link-box">
          <div class="share-link-url" id="linkUrl"><?php echo htmlspecialchars($base_url) ?></div>
          <button class="share-copy-btn" onclick="copiarLink('<?php echo addslashes($base_url) ?>')"><i class="fas fa-copy"></i> Copiar</button>
        </div>
      </div>

      <!-- Botão WhatsApp -->
      <a href="https://wa.me/?text=<?php echo urlencode('A minha consulta: ' . $base_url) ?>" target="_blank" class="btn btn-success" style="width:100%;padding:12px;margin-bottom:12px">
        <i class="fab fa-whatsapp"></i> Partilhar via WhatsApp
      </a>

      <!-- Info extra -->
      <div style="display:flex;gap:12px">
        <div style="flex:1;background:var(--bg);border-radius:10px;padding:12px;text-align:center">
          <div style="font-size:20px;font-weight:800;color:var(--text)"><?php echo $partilha['vistas'] ?></div>
          <div style="font-size:11px;color:var(--text-muted)">visualizações</div>
        </div>
        <div style="flex:1;background:var(--bg);border-radius:10px;padding:12px;text-align:center">
          <div style="font-size:12px;font-weight:700;color:var(--text)"><?php echo date('d/m/Y', strtotime($partilha['expira_em'])) ?></div>
          <div style="font-size:11px;color:var(--text-muted)">expira em</div>
        </div>
      </div>

      <div class="alert alert-info" style="margin-top:16px;font-size:12.5px">
        <i class="fas fa-info-circle"></i>
        Qualquer pessoa com este link pode ver os detalhes da consulta. Não partilhe com desconhecidos.
      </div>

    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
