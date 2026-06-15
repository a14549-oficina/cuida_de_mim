<?php
require_once __DIR__ . '/config/config.php';

if (logged_in()) { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (!$nome || !$email || !$pass) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Endereço de email inválido.';
    } elseif ($pass !== $pass2) {
        $erro = 'As palavras-passe não coincidem.';
    } elseif (strlen($pass) < 6) {
        $erro = 'A palavra-passe deve ter pelo menos 6 caracteres.';
    } elseif (db_row('SELECT id FROM utilizadores WHERE email = ?', [$email])) {
        $erro = 'Este email já está registado.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        db_insert(
            'INSERT INTO utilizadores (nome, email, password_hash) VALUES (?, ?, ?)',
            [$nome, $email, $hash]
        );
        $sucesso = 'Conta criada com sucesso! Pode agora iniciar sessão.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cuida de Mim — Criar Conta</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo BASE ?>css/style.css">
<style>
  body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#1e3a8a,#2563eb);padding:24px 20px;box-sizing:border-box}
  .auth-box{background:#fff;border-radius:20px;padding:40px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
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
    <h1>Criar Conta</h1>
    <p>Junte-se ao Cuida de Mim</p>
  </div>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro) ?></div>
  <?php endif ?>
  <?php if ($sucesso): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($sucesso) ?> <a href="<?php echo BASE ?>login.php">Clique aqui para entrar.</a></div>
  <?php endif ?>

  <form method="POST">
    <?php echo csrf_field() ?>
    <div class="form-group">
      <label class="form-label">Nome Completo *</label>
      <input type="text" name="nome" class="form-control" placeholder="O seu nome"
             value="<?php echo htmlspecialchars($_POST['nome'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">Email *</label>
      <input type="email" name="email" class="form-control" placeholder="nome@gmail.com"
             value="<?php echo htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label class="form-label">Palavra-passe *</label>
      <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
    </div>
    <div class="form-group">
      <label class="form-label">Confirmar Palavra-passe *</label>
      <input type="password" name="password2" class="form-control" placeholder="Repita a palavra-passe" required>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;font-size:14px">
      <i class="fas fa-user-plus"></i> Criar Conta
    </button>
  </form>

  <div class="auth-footer">
    Já tem conta? <a href="<?php echo BASE ?>login.php">Iniciar sessão</a>
  </div>
</div>
</body>
</html>
