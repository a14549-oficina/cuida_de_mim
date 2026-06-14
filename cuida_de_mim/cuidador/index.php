<?php
$page_id    = 'cuidador';
$page_title = 'Modo Cuidador';
require_once __DIR__ . '/../config/config.php';
$uid = user_id();

// Garantir colunas
try {
    db()->exec("ALTER TABLE configuracoes ADD COLUMN IF NOT EXISTS notif_cuidador_tomas TINYINT(1) NOT NULL DEFAULT 1");
} catch(Exception $e){}

// Convidar cuidador 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'convidar') {
    csrf_verify();
    $email = trim(strtolower($_POST['email'] ?? ''));
    $nome  = trim($_POST['nome'] ?? '');
    $whats = preg_replace('/[^0-9+]/', '', $_POST['whatsapp'] ?? '');
    $perms = implode(',', array_intersect(
        $_POST['permissoes'] ?? [],
        ['medicamentos','consultas','diario','lembretes','relatorio']
    ));
    if (!$perms) $perms = 'medicamentos,consultas,lembretes';

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Verifica se já existe convite ativo
        $existe = db_row('SELECT id FROM cuidadores WHERE utente_id = ? AND email_cuidador = ? AND estado != "recusado"', [$uid, $email]);
        if (!$existe) {
            $token = bin2hex(random_bytes(16));
            db_exec(
                'INSERT INTO cuidadores (utente_id, email_cuidador, nome_cuidador, whatsapp_cuidador, token_convite, permissoes)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$uid, $email, $nome ?: null, $whats ?: null, $token, $perms]
            );
            $msg_ok = "Convite enviado para <strong>" . htmlspecialchars($email) . "</strong>!<br><small>Partilha o link: " . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/cuidador/aceitar.php?token=' . $token . "</small>";
        } else {
            $msg_erro = "Já existe um convite ativo para este email.";
        }
    } else {
        $msg_erro = "Email inválido.";
    }
}

// Remover cuidador 
if (isset($_GET['remover'])) {
    db_exec('DELETE FROM cuidadores WHERE id = ? AND utente_id = ?', [(int)$_GET['remover'], $uid]);
    header('Location: index.php?removido=1'); exit;
}

// Editar cuidador 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar') {
    csrf_verify();
    $id    = (int)($_POST['id'] ?? 0);
    $nome  = trim($_POST['nome'] ?? '');
    $whats = preg_replace('/[^0-9+]/', '', $_POST['whatsapp'] ?? '');
    $perms = implode(',', array_intersect(
        $_POST['permissoes'] ?? [],
        ['medicamentos','consultas','diario','lembretes','relatorio']
    ));
    if (!$perms) $perms = 'medicamentos';
    if ($id > 0) {
        db_exec('UPDATE cuidadores SET nome_cuidador=?, whatsapp_cuidador=?, permissoes=? WHERE id=? AND utente_id=?',
            [$nome ?: null, $whats ?: null, $perms, $id, $uid]);
    }
    header('Location: index.php?ok=1'); exit;
}

// Listar cuidadores 
$cuidadores = db_query('SELECT * FROM cuidadores WHERE utente_id = ? ORDER BY criado_em DESC', [$uid]);

// Se este utilizador for cuidador de alguém, listar 
$sou_cuidador = db_query(
    'SELECT c.*, u.nome AS utente_nome, u.email AS utente_email
     FROM cuidadores c
     INNER JOIN utilizadores u ON u.id = c.utente_id
     WHERE c.email_cuidador = ? AND c.estado = "ativo"',
    [user()['email'] ?? '']
);

include '../includes/header.php';
?>

<?php if (isset($msg_ok)): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg_ok ?></div><?php endif ?>
<?php if (isset($msg_erro)): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($msg_erro) ?></div><?php endif ?>
<?php if (isset($_GET['removido'])): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Cuidador removido.</div><?php endif ?>

<div class="two-col">

  <!COLUNA ESQUERDA: os meus cuidadores>
  <div>

    <! Explicação>
    <div class="card" style="margin-bottom:20px">
      <div class="card-body" style="display:flex;gap:16px;align-items:flex-start">
        <div style="font-size:36px"></div>
        <div>
          <div style="font-size:15px;font-weight:700;margin-bottom:6px">Modo Cuidador</div>
          <div style="font-size:13.5px;color:var(--text-muted);line-height:1.6">
            Permite que um familiar ou profissional de saúde receba notificações sobre as tuas tomas e consultas via WhatsApp, e visualize o teu dashboard de saúde.
          </div>
        </div>
      </div>
    </div>

    <! Formulário convite >
    <div class="card" style="margin-bottom:20px">
      <div class="card-header"><div class="card-title"><i class="fas fa-user-plus"></i> Convidar Cuidador</div></div>
      <div class="card-body">
        <form method="POST" action="index.php">
          <?php echo csrf_field() ?>
          <input type="hidden" name="acao" value="convidar">
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Email do cuidador *</label>
              <input type="email" name="email" class="form-control" placeholder="familiar@email.com" required>
            </div>
            <div class="form-group">
              <label class="form-label">Nome (opcional)</label>
              <input type="text" name="nome" class="form-control" placeholder="ex: Mãe">
            </div>
            <div class="form-group" style="grid-column:1/-1">
              <label class="form-label">WhatsApp do cuidador (opcional)</label>
              <input type="text" name="whatsapp" class="form-control" placeholder="+351912345678">
              <small style="color:var(--text-muted);font-size:11.5px">Se preenchido, o cuidador recebe alertas de tomas e consultas via WhatsApp.</small>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Permissões de acesso</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px">
              <?php
              $perms_labels = ['medicamentos'=>' Medicamentos','consultas'=>' Consultas','lembretes'=>' Lembretes','diario'=>' Diário','relatorio'=>' Relatório IA'];
              foreach ($perms_labels as $k => $l): ?>
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;background:var(--bg);padding:6px 12px;border-radius:8px;border:1.5px solid var(--border)">
                  <input type="checkbox" name="permissoes[]" value="<?php echo $k ?>"
                    <?php echo in_array($k, ['medicamentos','consultas','lembretes']) ? 'checked' : '' ?>
                    style="accent-color:var(--primary)">
                  <?php echo $l ?>
                </label>
              <?php endforeach ?>
            </div>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;padding:12px"><i class="fas fa-paper-plane"></i> Enviar Convite</button>
        </form>
      </div>
    </div>

    <! Lista dos meus cuidadores >
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-users"></i> Os Meus Cuidadores</div></div>
      <div style="padding:12px 16px">
        <?php if (!$cuidadores): ?>
          <div class="empty-state" style="padding:40px">
            <div class="empty-icon"><i class="fas fa-user-shield"></i></div>
            <div class="empty-title">Sem cuidadores</div>
            <div class="empty-text">Convida um familiar ou cuidador acima.</div>
          </div>
        <?php else: ?>
          <?php foreach ($cuidadores as $c): ?>
            <?php
              $nome_display = $c['nome_cuidador'] ?: $c['email_cuidador'];
              $partes = explode(' ', trim($nome_display));
              $iniciais = strtoupper(substr($partes[0], 0, 1));
              if (count($partes) > 1) $iniciais .= strtoupper(substr(end($partes), 0, 1));
              $cores = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];
              $cor = $cores[crc32($nome_display) % count($cores)];
            ?>
            <div class="cuidador-card" style="margin-bottom:12px">
              <div style="width:44px;height:44px;border-radius:50%;background:<?php echo $cor ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;flex-shrink:0;letter-spacing:.02em"><?php echo $iniciais ?></div>
              <div class="cuidador-info">
                <div class="cuidador-nome"><?php echo htmlspecialchars($c['nome_cuidador'] ?: '—') ?></div>
                <div class="cuidador-email"><?php echo htmlspecialchars($c['email_cuidador']) ?></div>
                <?php if ($c['whatsapp_cuidador']): ?>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><i class="fab fa-whatsapp" style="color:#25d366"></i> <?php echo htmlspecialchars($c['whatsapp_cuidador']) ?></div>
                <?php endif ?>
                <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:4px">
                  <?php foreach (explode(',', $c['permissoes']) as $p): ?>
                    <span class="badge badge-info" style="font-size:10px"><?php echo $p ?></span>
                  <?php endforeach ?>
                </div>
              </div>
              <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
                <span class="cuidador-estado estado-<?php echo $c['estado'] ?>"><?php echo ucfirst($c['estado']) ?></span>
                <?php if ($c['estado'] === 'pendente'): ?>
                  <?php
                  $link_convite = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/cuidador/aceitar.php?token=' . $c['token_convite'];
                  ?>
                  <button onclick="copiarLink('<?php echo addslashes($link_convite) ?>')" class="btn btn-xs" style="background:#dbeafe;color:#1d4ed8;border:none"><i class="fas fa-copy"></i> Link</button>
                <?php endif ?>
                <button type="button" onclick="abrirEditarCuidador(<?php echo htmlspecialchars(json_encode($c)) ?>)" class="btn btn-xs" style="background:#dbeafe;color:#1d4ed8;border:none"><i class="fas fa-pen"></i></button>
                <a href="?remover=<?php echo $c['id'] ?>" class="btn btn-xs btn-danger" onclick="return confirm('Remover cuidador?')"><i class="fas fa-times"></i></a>
              </div>
            </div>
          <?php endforeach ?>
        <?php endif ?>
      </div>
    </div>

  </div>

  <! COLUNA DIREITA: sou cuidador de alguém? >
  <div>
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-heart"></i> Sou Cuidador De</div></div>
      <div style="padding:12px 16px">
        <?php if (!$sou_cuidador): ?>
          <div class="empty-state" style="padding:40px">
            <div class="empty-icon"><i class="fas fa-heart"></i></div>
            <div class="empty-title">Sem utilizadores</div>
            <div class="empty-text">Quando aceitares um convite de alguém aparece aqui.</div>
          </div>
        <?php else: ?>
          <?php foreach ($sou_cuidador as $u): ?>
            <?php
              $partes_u = explode(' ', trim($u['utente_nome']));
              $iniciais_u = strtoupper(substr($partes_u[0], 0, 1));
              if (count($partes_u) > 1) $iniciais_u .= strtoupper(substr(end($partes_u), 0, 1));
            ?>
            <div class="cuidador-card" style="margin-bottom:12px">
              <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;flex-shrink:0;letter-spacing:.02em"><?php echo $iniciais_u ?></div>
              <div class="cuidador-info">
                <div class="cuidador-nome"><?php echo htmlspecialchars($u['utente_nome']) ?></div>
                <div class="cuidador-email"><?php echo htmlspecialchars($u['utente_email']) ?></div>
                <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:4px">
                  <?php foreach (explode(',', $u['permissoes']) as $p): ?>
                    <span class="badge badge-info" style="font-size:10px"><?php echo $p ?></span>
                  <?php endforeach ?>
                </div>
              </div>
              <a href="entrar.php?utente=<?php echo $u['utente_id'] ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-eye"></i> Ver
              </a>
            </div>
          <?php endforeach ?>
        <?php endif ?>
      </div>
    </div>

    <! Informação sobre como funciona >
    <div class="card" style="margin-top:20px">
      <div class="card-header"><div class="card-title"><i class="fas fa-question-circle"></i> Como funciona</div></div>
      <div class="card-body" style="font-size:13.5px;color:var(--text-muted);line-height:1.7">
        <ol style="padding-left:16px">
          <li>Preenches o email do cuidador e defines as permissões.</li>
          <li>Copias o link de convite e envias ao cuidador (WhatsApp, email, etc.).</li>
          <li>O cuidador abre o link, cria conta (se não tiver) e aceita.</li>
          <li>A partir daí, o cuidador pode ver o teu dashboard e receber alertas de WhatsApp.</li>
        </ol>
      </div>
    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>

<! Editar Cuidador >
<div id="modalEditarCuidador" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.25);margin:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h3 style="font-size:16px;font-weight:700;margin:0"><i class="fas fa-pen" style="color:var(--primary);margin-right:8px"></i> Editar Cuidador</h3>
      <button onclick="fecharEditarCuidador()" style="background:none;border:none;font-size:22px;cursor:pointer;color:var(--text-muted)">&times;</button>
    </div>
    <form method="POST" action="index.php">
      <?php echo csrf_field() ?>
      <input type="hidden" name="acao" value="editar">
      <input type="hidden" name="id" id="editCuidadorId">
      <div class="form-group">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" id="editCuidadorNome" class="form-control" placeholder="Nome do cuidador">
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fab fa-whatsapp" style="color:#25d366"></i> WhatsApp</label>
        <input type="tel" name="whatsapp" id="editCuidadorWhats" class="form-control" placeholder="+351912345678">
      </div>
      <div class="form-group">
        <label class="form-label">Permissões</label>
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:6px">
          <?php foreach (['medicamentos','consultas','diario','lembretes','relatorio'] as $p): ?>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px">
              <input type="checkbox" name="permissoes[]" value="<?php echo $p ?>" class="edit-perm-cb" style="width:15px;height:15px">
              <?php echo ucfirst($p) ?>
            </label>
          <?php endforeach ?>
        </div>
      </div>
      <div style="display:flex;gap:10px;margin-top:8px">
        <button type="button" onclick="fecharEditarCuidador()" class="btn btn-ghost" style="flex:1">Cancelar</button>
        <button type="submit" class="btn btn-primary" style="flex:1"><i class="fas fa-save"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>
<script>
function abrirEditarCuidador(c) {
  document.getElementById('editCuidadorId').value    = c.id;
  document.getElementById('editCuidadorNome').value  = c.nome_cuidador || '';
  document.getElementById('editCuidadorWhats').value = c.whatsapp_cuidador || '';
  const perms = (c.permissoes || '').split(',');
  document.querySelectorAll('.edit-perm-cb').forEach(cb => {
    cb.checked = perms.includes(cb.value);
  });
  document.getElementById('modalEditarCuidador').style.display = 'flex';
}
function fecharEditarCuidador() {
  document.getElementById('modalEditarCuidador').style.display = 'none';
}
document.getElementById('modalEditarCuidador').addEventListener('click', function(e) {
  if (e.target === this) fecharEditarCuidador();
});
</script>
