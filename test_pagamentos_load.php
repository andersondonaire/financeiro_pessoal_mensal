<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Models/Categoria.php';

echo "1. Config carregado\n";

try {
    Auth::verificarLogin();
    echo "2. Auth OK (ou redirecionou)\n";
} catch (Exception $e) {
    echo "ERRO Auth: " . $e->getMessage() . "\n";
}

try {
    $categoriaModel = new Categoria();
    echo "3. Categoria model criado\n";
    
    $categorias = $categoriaModel->buscarTodas();
    echo "4. Categorias: " . count($categorias) . " encontradas\n";
} catch (Exception $e) {
    echo "ERRO Categoria: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}
