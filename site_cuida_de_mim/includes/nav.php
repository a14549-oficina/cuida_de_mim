<?php
$host = 'http://' . $_SERVER['HTTP_HOST'] . '/14549';
?>
<nav>
    <a href="index.php" class="nav-logo">
        <div class="nav-logo-icon"><i class="fas fa-heartbeat"></i></div>
        <span class="nav-logo-text">Cuida de Mim</span>
    </a>
    <div class="nav-links">
        <a href="#funcionalidades">Funcionalidades</a>
        <a href="#como-funciona">Como funciona</a>
    </div>
    <div class="nav-cta">
        <a href="<?= $host ?>/cuida_de_mim/login.php" class="btn-login">Entrar</a>
        <a href="<?= $host ?>/cuida_de_mim/registar.php" class="btn-register">
            <i class="fas fa-user-plus"></i> Criar conta
        </a>
    </div>
</nav>
