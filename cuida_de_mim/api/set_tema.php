<?php
endpoint para evitar erros 404
header('Content-Type: application/json');
echo json_encode(['ok' => true]);
