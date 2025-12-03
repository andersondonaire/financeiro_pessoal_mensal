<?php
/**
 * Configurações Gerais do Sistema - TEMPLATE
 * Gerenciador Financeiro
 * 
 * INSTRUÇÕES:
 * 1. Copie este arquivo para config.php
 * 2. Preencha as configurações abaixo com seus valores
 * 3. Nunca commite o arquivo config.php no Git
 */

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');           // Host do MySQL
define('DB_NAME', 'financeiro_db');       // Nome do banco de dados
define('DB_USER', 'root');                // Usuário do MySQL
define('DB_PASS', '');                    // Senha do MySQL
define('DB_CHARSET', 'utf8mb4');

// Configurações do Sistema
define('SITE_URL', 'http://localhost:3030/public');  // URL base do sistema
define('SITE_NAME', 'Gerenciador Financeiro');        // Nome do sistema

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
