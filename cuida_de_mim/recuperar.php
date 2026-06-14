<?php
require_once __DIR__ . '/config/config.php';

if (logged_in()) { header('Location: ' . BASE_URL . '/dashboard.php'); exit; }

$erro    = '';
$sucesso = '';

// Cria a tabela se não existir (evita erro se o SQL não foi importado)
db()->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email     VARCHAR(255) NOT NULL,
    token     VARCHAR(64)  NOT NULL UNIQUE,
    expira_em DATETIME     NOT NULL,
    usado     TINYINT(1)   NOT NULL DEFAULT 0,
    criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Introduza um endereço de email válido.';
    } else {
        $user = db_row('SELECT id, nome FROM utilizadores WHERE email = ?', [$email]);

        // Mesmo que o email não exista, mostramos sempre a mesma mensagem
        // (por segurança, não revelamos se o email está registado)
        if ($user) {
            // Apaga tokens antigos deste email
            db_exec('DELETE FROM password_resets WHERE email = ?', [$email]);

            // Gera token seguro
            $token    = bin2hex(random_bytes(32)); // 64 chars hex
            $expira   = date('Y-m-d H:i:s', time() + 3600); // válido 1 hora

            db_insert(
                'INSERT INTO password_resets (email, token, expira_em) VALUES (?, ?, ?)',
                [$email, $token, $expira]
            );

            $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                  . '://' . $_SERVER['HTTP_HOST']
                  . BASE_URL . '/nova_password.php?token=' . $token;

            
            @mail(
                $email,
                'Recuperar palavra-passe — Cuida de Mim',
                "Olá {$user['nome']},\n\nClique no link abaixo para definir uma nova palavra-passe (válido 1 hora):\n\n{$link}\n\nSe não pediu este email, ignore-o.\n\nCuida de Mim",
            );

            // Guarda o link na sessão para mostrar no ecrã (remova em produção)
            if (DB_HOST === 'localhost') { $_SESSION['_reset_link_dev'] = $link; }
        }

        $sucesso = 'Para definir uma nova palavra-passe, clique no botão abaixo.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cuida de Mim — Recuperar Palavra-passe</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo BASE ?>css/style.css">
<style>
  body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#1e3a8a,#2563eb);padding:16px}
  .auth-box{background:#fff;border-radius:20px;padding:40px 36px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
  .auth-logo{text-align:center;margin-bottom:32px}
  .auth-logo .logo-icon{width:60px;height:60px;background:var(--primary);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:26px;margin-bottom:16px}
  .auth-logo h1{font-size:22px;font-weight:800;margin-bottom:8px}
  .auth-logo p{font-size:13px;color:var(--text-muted);line-height:1.6}
  .auth-footer{text-align:center;margin-top:24px;font-size:13px}
  .auth-footer a{color:var(--primary);font-weight:600;text-decoration:none}
  .dev-box{border:2px dashed #f59e0b;background:#fffbeb;border-radius:10px;padding:16px;margin-top:20px}
  .dev-box-label{font-size:11px;font-weight:700;color:#92400e;margin-bottom:10px}
  .dev-btn{display:block;background:#2563eb;color:#fff!important;text-align:center;padding:12px 16px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none!important}
</style>
</head>
<body>
<div class="auth-box">
  <div class="auth-logo">
    <div class="logo-icon"><i class="fas fa-key"></i></div>
    <h1>Recuperar Palavra-passe</h1>
    <p>Introduza o seu email para alterar <br> a sua palavra-passe</p>
  </div>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro) ?></div>
  <?php endif ?>

  <?php if ($sucesso): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($sucesso) ?></div>

    <?php
    // DEV ONLY: mostra link no ecrã se SMTP não estiver configurado
    // REMOVA este bloco em produção!
    if (!empty($_SESSION['_reset_link_dev']) && (defined('DB_HOST') && DB_HOST === 'localhost')):
    ?>
      <div class="dev-box">
        <div class="dev-box-label"></div>
        <a href="<?php echo htmlspecialchars($_SESSION['_reset_link_dev']) ?>" class="dev-btn">
          <i class="fas fa-key"></i> Clique aqui para definir <br> nova palavra-passe
        </a>
      </div>
      <?php unset($_SESSION['_reset_link_dev']); ?>
    <?php endif ?>

  <?php else: ?>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" placeholder="nome@gmail.com"
               value="<?php echo htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;font-size:14px;margin-top:4px">
        <i class="fas fa-paper-plane"></i> Recuperar
      </button>
    </form>
  <?php endif ?>

  <div class="auth-footer">
    <a href="<?php echo BASE ?>login.php"><i class="fas fa-arrow-left"></i> Voltar ao login</a>
  </div>
</div>
</body>
</html>
