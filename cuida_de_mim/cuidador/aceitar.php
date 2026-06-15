<?php
require_once __DIR__ . '/../config/config.php';
$token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');
$convite = $token ? db_row(
    'SELECT c.*, u.nome AS utente_nome FROM cuidadores c
     INNER JOIN utilizadores u ON u.id = c.utente_id
     WHERE c.token_convite = ? AND c.estado = "pendente"',
    [$token]
) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $convite) {
    csrf_verify();
    if ($_POST['acao'] === 'aceitar') {
        $cuidador_id = logged_in() ? user_id() : null;
        db_exec(
            'UPDATE cuidadores SET estado="ativo", cuidador_id=?, aceite_em=NOW() WHERE token_convite=?',
            [$cuidador_id, $token]
        );
        header('Location: index.php?aceite=1'); exit;
    } elseif ($_POST['acao'] === 'recusar') {
        db_exec('UPDATE cuidadores SET estado="recusado" WHERE token_convite=?', [$token]);
        header('Location: ../index.php'); exit;
    }
}

$page_id    = 'cuidador';
$page_title = 'Aceitar Convite';
if (logged_in()) include '../includes/header.php';
else {
    echo '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Convite Cuida de Mim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    </head><body style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px">';
}
?>

<div style="max-width:480px;margin:0 auto">
  <?php if (!$convite): ?>
    <div class="card">
      <div class="card-body" style="text-align:center;padding:40px">
        <div style="font-size:48px;margin-bottom:16px"></div>
        <div style="font-size:18px;font-weight:700;margin-bottom:8px">Convite inválido</div>
        <div style="color:var(--text-muted)">Este convite não existe ou já foi utilizado.</div>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-body" style="text-align:center;padding:40px">
        <div style="font-size:48px;margin-bottom:16px"></div>
        <div style="font-size:20px;font-weight:800;margin-bottom:8px">Convite de Cuidador</div>
        <div style="color:var(--text-muted);margin-bottom:24px;font-size:14px">
          <strong><?php echo htmlspecialchars($convite['utente_nome']) ?></strong> convidou-te para seres cuidador/a no <strong>Cuida de Mim</strong>.
        </div>
        <div style="background:var(--bg);border-radius:12px;padding:16px;text-align:left;margin-bottom:24px">
          <div style="font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:8px">PERMISSÕES</div>
          <?php foreach (explode(',', $convite['permissoes']) as $p): ?>
            <div style="font-size:13px;color:var(--text);padding:3px 0"><i class="fas fa-check" style="color:var(--secondary);margin-right:8px"></i><?php echo htmlspecialchars($p) ?></div>
          <?php endforeach ?>
        </div>
        <form method="POST" action="aceitar.php?token=<?php echo htmlspecialchars($token) ?>">
          <?php echo csrf_field() ?>
          <div style="display:flex;gap:12px">
            <button type="submit" name="acao" value="aceitar" class="btn btn-primary" style="flex:1;padding:12px"><i class="fas fa-check"></i> Aceitar</button>
            <button type="submit" name="acao" value="recusar" class="btn btn-ghost" style="flex:1;padding:12px"><i class="fas fa-times"></i> Recusar</button>
          </div>
          <?php if (!logged_in()): ?>
            <div class="alert alert-info" style="margin-top:12px;font-size:12.5px"><i class="fas fa-info-circle"></i> Precisas de <a href="../login.php">iniciar sessão</a> ou <a href="../registar.php">criar conta</a> primeiro.</div>
          <?php endif ?>
        </form>
      </div>
    </div>
  <?php endif ?>
</div>

<?php
if (logged_in()) include '../includes/footer.php';
else echo '</body></html>';
?>
