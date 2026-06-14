<?php
require_once __DIR__ . '/config/config.php';

// Se já está logado vai para o dashboard
if (logged_in()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$erro = '';

// Rate limiting: máx 5 tentativas por IP em 5 minutos 
$ip_key = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION[$ip_key])) $_SESSION[$ip_key] = ['count' => 0, 'since' => time()];
if (time() - $_SESSION[$ip_key]['since'] > 300) {
    $_SESSION[$ip_key] = ['count' => 0, 'since' => time()]; // reset ao fim de 5 min
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION[$ip_key]['count'] >= 5) { $erro = 'Demasiadas tentativas. Aguarde 5 minutos.'; } else {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $erro = 'Preencha o email e a palavra-passe.';
    } else {
        $user = db_row('SELECT * FROM utilizadores WHERE email = ?', [$email]);
        if ($user && password_verify($pass, $user['password_hash'])) {
            session_regenerate_id(true);
            unset($_SESSION[$ip_key]);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user']    = $user;
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        } else {
            $erro = 'Email ou palavra-passe incorretos.';
            $_SESSION[$ip_key]['count']++;
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cuida de Mim — Entrar</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo BASE ?>css/style.css">
<style>
  body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#1e3a8a,#2563eb);padding:24px 20px;box-sizing:border-box}
  .auth-box{background:#fff;border-radius:20px;padding:40px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
  @media(max-width:480px){.auth-box{padding:32px 24px;border-radius:16px}}
  .auth-logo{text-align:center;margin-bottom:28px}
  .auth-logo .logo-icon{width:56px;height:56px;background:var(--primary);border-radius:14px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:24px;margin-bottom:12px}
  .auth-logo h1{font-size:22px;font-weight:800;margin-bottom:4px}
  .auth-logo p{font-size:13px;color:var(--text-muted)}
  .auth-footer{text-align:center;margin-top:20px;font-size:13px;color:var(--text-muted)}
  .auth-footer a{color:var(--primary);font-weight:600;text-decoration:none}
</style>
</head>
<body>
<div class="auth-box">
  <div class="auth-logo">
    <div class="logo-icon"><i class="fas fa-heartbeat"></i></div>
    <h1>Cuida de Mim</h1>
    <p>Inicie sessão para continuar</p>
  </div>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro) ?></div>
  <?php endif ?>

  <form method="POST">
    <?php echo csrf_field() ?>
    <div class="form-group">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" placeholder="nome@gmail.com"
             value="<?php echo htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
    </div>
    <div class="form-group">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
        <label class="form-label" style="margin:0">Palavra-passe</label>
        <a href="<?php echo BASE ?>recuperar.php" style="font-size:12px;color:var(--primary);text-decoration:none;font-weight:500">Recuperar a palavra-passe</a>
      </div>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;font-size:14px;margin-top:4px">
      <i class="fas fa-sign-in-alt"></i> Entrar
    </button>
  </form>

  <div class="auth-footer">
    Não tem conta? <a href="<?php echo BASE ?>registar.php">Criar conta</a>
  </div>
</div>
</body>
</html>
