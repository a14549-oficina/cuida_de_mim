<?php
$page_id = 'perfil';
$page_title = 'Perfil';
require_once __DIR__ . '/config/config.php';
$uid = user_id();

$sucesso = '';
$erro = '';

// Garante coluna foto_perfil na tabela utilizadores
try {
    db()->exec("ALTER TABLE utilizadores ADD COLUMN IF NOT EXISTS foto_perfil VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {}

// Upload de foto de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
    csrf_verify();
    $file    = $_FILES['foto_perfil'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxsize = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowed)) {
        $erro = 'Formato não suportado. Use JPG, PNG, GIF ou WebP.';
    } elseif ($file['size'] > $maxsize) {
        $erro = 'Imagem demasiado grande. Máximo 2MB.';
    } else {
        $ext     = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nome    = 'perfil_' . $uid . '_' . time() . '.' . $ext;
        $dir     = __DIR__ . '/uploads/perfil/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // Remove foto anterior
        $foto_anterior = db_row('SELECT foto_perfil FROM utilizadores WHERE id=?', [$uid])['foto_perfil'] ?? null;
        if ($foto_anterior && file_exists($dir . $foto_anterior)) {
            unlink($dir . $foto_anterior);
        }

        if (move_uploaded_file($file['tmp_name'], $dir . $nome)) {
            db_exec('UPDATE utilizadores SET foto_perfil=? WHERE id=?', [$nome, $uid]);
            $_SESSION['user'] = null;
            $sucesso = 'Foto atualizada com sucesso!';
        } else {
            $erro = 'Erro ao guardar a foto. Tente novamente.';
        }
    }
}

// Remover foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'remover_foto') {
    csrf_verify();
    $foto_atual = db_row('SELECT foto_perfil FROM utilizadores WHERE id=?', [$uid])['foto_perfil'] ?? null;
    if ($foto_atual) {
        $path = __DIR__ . '/uploads/perfil/' . $foto_atual;
        if (file_exists($path)) unlink($path);
        db_exec('UPDATE utilizadores SET foto_perfil=NULL WHERE id=?', [$uid]);
        $_SESSION['user'] = null;
    }
    $sucesso = 'Foto removida.';
}

// Guardar dados do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'guardar_dados') {
    csrf_verify();
    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $nasc     = $_POST['nascimento'] ?? '';
    $medico   = trim($_POST['medico_familia'] ?? '');

    if (!$nome || !$email) {
        $erro = 'Nome e email são obrigatórios.';
    } else {
        $existente = db_row('SELECT id FROM utilizadores WHERE email=? AND id!=?', [$email, $uid]);
        if ($existente) {
            $erro = 'Este email já está em uso por outra conta.';
        } else {
            db_exec(
                'UPDATE utilizadores SET nome=?, email=?, telefone=?, nascimento=?, medico_familia=? WHERE id=?',
                [$nome, $email, $telefone ?: null, $nasc ?: null, $medico ?: null, $uid]
            );
            $_SESSION['user'] = null;
            $sucesso = 'Perfil atualizado com sucesso!';
        }
    }
}

$u = user();
$iniciais = implode('', array_map(fn($p) => strtoupper($p[0]), array_slice(explode(' ', $u['nome']), 0, 2)));
$foto_perfil = $u['foto_perfil'] ?? null;
$foto_url    = $foto_perfil ? BASE . 'uploads/perfil/' . htmlspecialchars($foto_perfil) : null;

include 'includes/header.php';
?>

<?php if ($sucesso): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($sucesso) ?></div><?php endif ?>
<?php if ($erro): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro) ?></div><?php endif ?>

<div style="max-width:760px">

  <! CARD TOPO: avatar + info >
  <div class="card" style="margin-bottom:20px">
    <div class="card-body" style="display:flex;align-items:center;gap:24px;padding:24px">

      <!Avatar com botão de upload >
      <div style="position:relative;flex-shrink:0">
        <div id="avatar-circle" style="width:80px;height:80px;border-radius:50%;overflow:hidden;background:linear-gradient(135deg,var(--primary),var(--primary-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px;font-weight:800;cursor:pointer;border:3px solid #e2e8f0" onclick="document.getElementById('input-foto').click()" title="Clique para alterar a foto">
          <?php if ($foto_url): ?>
            <img src="<?php echo $foto_url ?>" alt="Foto de perfil" style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            <?php echo htmlspecialchars($iniciais) ?>
          <?php endif ?>
        </div>
        <! Ícone de câmera sobreposto >
        <div onclick="document.getElementById('input-foto').click()" style="position:absolute;bottom:0;right:0;width:26px;height:26px;background:#2563eb;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid #fff" title="Alterar foto">
          <i class="fas fa-camera" style="color:#fff;font-size:11px"></i>
        </div>
      </div>

      <div style="flex:1">
        <div style="font-size:18px;font-weight:800"><?php echo htmlspecialchars($u['nome']) ?></div>
        <div style="font-size:13px;color:var(--text-muted)"><?php echo htmlspecialchars($u['email']) ?></div>
        <?php if ($u['telefone']): ?><div style="font-size:13px;color:var(--text-muted)"><?php echo htmlspecialchars($u['telefone']) ?></div><?php endif ?>
        <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
          <button type="button" onclick="document.getElementById('input-foto').click()" class="btn btn-ghost btn-sm">
            <i class="fas fa-camera"></i> <?php echo $foto_url ? 'Alterar foto' : 'Adicionar foto' ?>
          </button>
          <?php if ($foto_url): ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Remover a foto de perfil?')">
            <?php echo csrf_field() ?>
            <input type="hidden" name="acao" value="remover_foto">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Remover foto</button>
          </form>
          <?php endif ?>
        </div>
      </div>
    </div>
  </div>

  <! Upload de foto (form oculto) >
  <form method="POST" enctype="multipart/form-data" id="form-foto" style="display:none">
    <?php echo csrf_field() ?>
    <input type="file" id="input-foto" name="foto_perfil" accept="image/jpeg,image/png,image/gif,image/webp"
           onchange="document.getElementById('form-foto').submit()">
  </form>

  <! FORMULÁRIO DE DADOS >
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-user-edit"></i> Editar Dados</div></div>
    <div class="card-body">
      <form method="POST">
        <?php echo csrf_field() ?>
        <input type="hidden" name="acao" value="guardar_dados">
        <div class="form-grid-2">
          <div class="form-group" style="grid-column:1/-1">
            <label class="form-label">Nome Completo *</label>
            <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($u['nome']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($u['email']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Telemóvel</label>
            <input type="tel" name="telefone" class="form-control" value="<?php echo htmlspecialchars($u['telefone'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Data de Nascimento</label>
            <input type="date" name="nascimento" class="form-control" value="<?php echo htmlspecialchars($u['nascimento'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Médico de Família</label>
            <input type="text" name="medico_familia" class="form-control" placeholder="Dr. nome..." value="<?php echo htmlspecialchars($u['medico_familia'] ?? '') ?>">
          </div>
        </div>
        <button type="submit" class="btn btn-primary" style="padding:12px 32px">
          <i class="fas fa-save"></i> Guardar
        </button>
      </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
