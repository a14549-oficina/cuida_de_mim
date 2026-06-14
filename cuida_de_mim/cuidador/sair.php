<?php
require_once __DIR__ . '/../config/config.php';
unset($_SESSION['cuidador_utente_id']);
header('Location: ' . BASE . 'index.php'); exit;
