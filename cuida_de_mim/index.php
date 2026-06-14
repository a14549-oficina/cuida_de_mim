<?php
require_once __DIR__ . '/config/config.php';
// Redireciona para dashboard se logado, senão para login
if (logged_in()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
