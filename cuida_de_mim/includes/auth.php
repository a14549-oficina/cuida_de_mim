<?php
/*Cuida de Mim — Autenticação / Sessão
 *Incluído automaticamente pelo config.php*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*Retorna o ID do utilizador logado (ou redireciona para login)*/
function user_id(): int {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
    return (int) $_SESSION['user_id'];
}

/*Retorna os dados do utilizador logado em cache na sessão*/
function user(): array {
    if (empty($_SESSION['user'])) {
        $_SESSION['user'] = db_row('SELECT * FROM utilizadores WHERE id = ?', [user_id()]);
    }
    return $_SESSION['user'] ?? [];
}

/*Verifica se existe sessão activa (sem redirecionar)*/
function logged_in(): bool {
    return !empty($_SESSION['user_id']);
}
