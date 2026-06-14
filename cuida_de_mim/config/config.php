<?php
/*Cuida de Mim — Configuração Central
 *Incluir SEMPRE como primeiro include em cada página:
 *require_once __DIR__ . '/config/config.php';       (raiz)*/

// Caminho absoluto do sistema até à raiz do projeto
define('ROOT_PATH', dirname(__DIR__));

// Detecta automaticamente o URL base do projeto (ex: /cuida_de_mim)
// Funciona independentemente de estar em localhost/cuida_de_mim ou num domínio
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

// Remove o ficheiro do fim: /cuida_de_mim/medicamentos/index.php → /cuida_de_mim/medicamentos
$dir = dirname($script);

// Descobre quantos níveis estamos abaixo da raiz do projeto
// A raiz do projeto é a pasta que contém a pasta "config"
$current_file = str_replace('\\', '/', __FILE__); // este ficheiro: .../config/config.php
$root_dir     = str_replace('\\', '/', dirname(dirname($current_file))); // pasta raiz do projeto

// Calcula o URL base do projeto 
// ex: se instalado em htdocs/cuida_de_mim → base_url = /cuida_de_mim
$doc_root  = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$base_url  = str_replace($doc_root, '', $root_dir);
$base_url  = '/' . trim($base_url, '/');
if ($base_url === '/') $base_url = '';

define('BASE_URL', $base_url); // ex: /cuida_de_mim

// BASE é o caminho relativo de volta à raiz do projeto a partir da página atual
// Calcula a profundidade: quantas pastas abaixo da raiz está o ficheiro atual
$calling_file = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$rel = str_replace($root_dir . '/', '', $calling_file);
$depth = substr_count($rel, '/'); // 0 para raiz, 1 para subpasta
$base = str_repeat('../', $depth);
define('BASE', $base); // ex: '' para raiz, '../' para subpastas

// Carrega a ligação à base de dados
require_once __DIR__ . '/database.php';

// Carrega autenticação / sessão
require_once dirname(__DIR__) . '/includes/auth.php';
require_once __DIR__ . '/csrf.php';
