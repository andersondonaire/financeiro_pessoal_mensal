<?php
/**
 * Configurações Gerais do Sistema
 * Gerenciador Financeiro
 */

// Carregar variáveis de ambiente do arquivo .env
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        die('Arquivo .env não encontrado. Copie .env.example para .env e configure.');
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    
    foreach ($lines as $line) {
        // Ignorar comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Processar linha KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remover aspas se existirem
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            
            $env[$key] = $value;
        }
    }
    
    return $env;
}

// Carregar .env
$env = loadEnv(__DIR__ . '/../.env');

// Configurações do Banco de Dados
define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_NAME', $env['DB_NAME'] ?? '');
define('DB_USER', $env['DB_USER'] ?? '');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('DB_CHARSET', $env['DB_CHARSET'] ?? 'utf8mb4');

// Configurações do Sistema
define('SITE_URL', $env['SITE_URL'] ?? '');
define('SITE_NAME', $env['SITE_NAME'] ?? 'Gerenciador Financeiro');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoload das classes
spl_autoload_register(function ($classe) {
    $base = __DIR__ . '/../src/';
    $arquivo = $base . str_replace('\\', '/', $classe) . '.php';
    
    if (file_exists($arquivo)) {
        require_once $arquivo;
    }
});
