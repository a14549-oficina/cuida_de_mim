<?php
/* Cuida de Mim — Ligação à Base de Dados (PDO)*/

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');       
define('DB_NAME', 'cuida_de_mim');
define('DB_CHARSET', 'utf8mb4');

/* Retorna a ligação PDO (singleton)*/

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('DB Connection Error: ' . $e->getMessage());
            $dev_mode = (defined('DB_HOST') && DB_HOST === 'localhost');
            $detail   = $dev_mode ? htmlspecialchars($e->getMessage()) : 'Contacte o administrador.';
            die('<div style="font-family:sans-serif;padding:40px;color:#991b1b;background:#fee2e2;border-radius:8px;max-width:600px;margin:60px auto">
                <h2>Erro de ligação à base de dados</h2>
                <p>' . $detail . '</p>
                ' . ($dev_mode ? '<p>Verifique se o MySQL está ativo e se a base de dados <strong>cuida_de_mim</strong> foi importada.</p>' : '') . '
            </div>');
        }
    }
    return $pdo;
}

/*Retorna todas as linhas de uma query */
function db_query(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/*Retorna uma única linha */
function db_row(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

/*Executa INSERT/UPDATE/DELETE e retorna o número de linhas afetadas */
function db_exec(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/*Executa INSERT e retorna o último ID inserido */
function db_insert(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) db()->lastInsertId();
}
