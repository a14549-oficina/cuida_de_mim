<?php
require_once __DIR__ . '/config/config.php';

if (logged_in()) { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

$token  = trim($_GET['token'] ?? '');
$erro   = '';
$sucesso = '';

// Valida o token
$reset = null;
if ($token) {
    $reset = db_row(
        'SELECT * FROM password_resets WHERE token = ? AND usado = 0 AND expira_em > NOW()',
        [$token]
    );
}

if (!$token || !$reset) {
    $erro_fatal = 'Este link é inválido ou já expirou. Peça um novo link de recuperação.';
}

if (!isset($erro_fatal) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass  = $_POST['password']  ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (strlen($pass) < 6) {
        $erro = 'A palavra-passe deve ter pelo menos 6 caracteres.';
    } elseif ($pass !== $pass2) {
        $erro = 'As palavras-passe não coincidem.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);

        // Atualiza a password do utilizador
        db_exec(
            'UPDATE utilizadores SET password_hash = ? WHERE email = ?',
            [$hash, $reset['email']]
        );

        // Marca o token como usado
        db_exec(
            'UPDATE password_resets SET usado = 1 WHERE token = ?',
            [$token]
        );

        $sucesso = 'Palavra-passe alterada com sucesso! Pode agora iniciar sessão.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cuida de Mim — Nova Palavra-passe</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo BASE ?>css/style.css">
<style>
  body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#1e3a8a,#2563eb)}
  .auth-box{background:#fff;border-radius:20px;padding:40px 36px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
  .auth-logo{text-align:center;margin-bottom:32px}
  .auth-logo .logo-icon{width:60px;height:60px;background:var(--primary);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:26px;margin-bottom:16px}
  .auth-logo h1{font-size:22px;font-weight:800;margin-bottom:8px}
  .auth-logo p{font-size:13px;color:var(--text-muted);line-height:1.6}
  .auth-footer{text-align:center;margin-top:28px;font-size:13px;color:var(--text-muted)}
  .auth-footer a{color:var(--primary);font-weight:600;text-decoration:none}
  .form-group{margin-bottom:20px}
  .password-strength{height:4px;border-radius:2px;margin-top:6px;transition:all .3s}
</style>
</head>
<body>
<div class="auth-box">
  <div class="auth-logo">
    <div class="logo-icon"><i class="fas fa-lock"></i></div>
    <h1>Nova Palavra-passe</h1>
    <p>Escolha uma nova palavra-passe segura</p>
  </div>

  <?php if (isset($erro_fatal)): ?>
    <div class="alert alert-danger"><i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($erro_fatal) ?></div>
    <div class="auth-footer">
      <a href="<?php echo BASE ?>recuperar.php"><i class="fas fa-redo"></i> Pedir novo link</a>
    </div>

  <?php elseif ($sucesso): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($sucesso) ?></div>
    <div class="auth-footer" style="margin-top:24px">
      <a href="<?php echo BASE ?>login.php" class="btn btn-primary" style="width:100%;padding:13px;font-size:14px;display:block;text-align:center;color:#fff;text-decoration:none">
        <i class="fas fa-sign-in-alt"></i> Ir para o login
      </a>
    </div>

  <?php else: ?>
    <?php if ($erro): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro) ?></div>
    <?php endif ?>

    <form method="POST">
    <?php echo csrf_field() ?>
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token) ?>">
      <div class="form-group">
        <label class="form-label">Nova Palavra-passe</label>
        <input type="password" name="password" id="pass1" class="form-control"
               placeholder="Mínimo 6 caracteres" required minlength="6"
               oninput="avaliarForca(this.value)">
        <div class="password-strength" id="forca-bar"></div>
        <small id="forca-texto" style="font-size:11px;color:var(--text-muted)"></small>
      </div>
      <div class="form-group">
        <label class="form-label">Confirmar Palavra-passe</label>
        <input type="password" name="password2" class="form-control"
               placeholder="Repita a nova palavra-passe" required minlength="6">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;font-size:14px;margin-top:4px">
        <i class="fas fa-save"></i> Guardar nova palavra-passe
      </button>
    </form>

    <div class="auth-footer">
      <a href="<?php echo BASE ?>login.php"><i class="fas fa-arrow-left"></i> Voltar ao login</a>
    </div>
  <?php endif ?>
</div>

<script>
function avaliarForca(pass) {
  const bar   = document.getElementById('forca-bar');
  const texto = document.getElementById('forca-texto');
  if (!pass) { bar.style.width = '0'; texto.textContent = ''; return; }

  let pontos = 0;
  if (pass.length >= 6)  pontos++;
  if (pass.length >= 10) pontos++;
  if (/[A-Z]/.test(pass)) pontos++;
  if (/[0-9]/.test(pass)) pontos++;
  if (/[^A-Za-z0-9]/.test(pass)) pontos++;

  const niveis = [
    { cor: '#ef4444', label: 'Muito fraca',  w: '20%' },
    { cor: '#f97316', label: 'Fraca',        w: '40%' },
    { cor: '#eab308', label: 'Razoável',     w: '60%' },
    { cor: '#22c55e', label: 'Boa',          w: '80%' },
    { cor: '#16a34a', label: 'Excelente',    w: '100%' },
  ];
  const n = niveis[Math.min(pontos, 4)];
  bar.style.cssText   = `width:${n.w};background:${n.cor};height:4px;border-radius:2px;transition:all .3s`;
  texto.textContent   = n.label;
  texto.style.color   = n.cor;
}
</script>
</body>
</html>
