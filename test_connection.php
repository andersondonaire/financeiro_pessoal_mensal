<?php
/**
 * Teste de Conexão com Banco de Dados
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

echo "<h1>Teste de Conexão - Gerenciador Financeiro</h1>";

// Testar conexão
try {
    $db = Database::getInstancia()->getConexao();
    echo "<p style='color: green;'>✓ Conexão com banco de dados estabelecida com sucesso!</p>";
    
    // Testar se as tabelas existem
    $tabelas = ['usuarios', 'categorias', 'recebimentos', 'pagamentos', 'ciclos', 'pagamentos_ciclo', 'saldo_ajuste'];
    
    echo "<h2>Tabelas do Banco:</h2><ul>";
    
    foreach ($tabelas as $tabela) {
        $stmt = $db->query("SHOW TABLES LIKE '$tabela'");
        if ($stmt->rowCount() > 0) {
            echo "<li style='color: green;'>✓ $tabela</li>";
        } else {
            echo "<li style='color: red;'>✗ $tabela (não encontrada)</li>";
        }
    }
    
    echo "</ul>";
    
    // Contar registros
    echo "<h2>Registros:</h2><ul>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $usuarios = $stmt->fetch();
    echo "<li>Usuários: " . $usuarios['total'] . "</li>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM categorias");
    $categorias = $stmt->fetch();
    echo "<li>Categorias: " . $categorias['total'] . "</li>";
    
    echo "</ul>";
    
    echo "<p><strong>Usuário padrão para teste:</strong><br>";
    echo "Email: admin@financeiro.com<br>";
    echo "Senha: admin123</p>";
    
    echo "<hr><p><a href='public/login.php'>→ Ir para Login</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erro na conexão: " . $e->getMessage() . "</p>";
    echo "<p><strong>Verifique:</strong></p>";
    echo "<ul>";
    echo "<li>MySQL está rodando?</li>";
    echo "<li>Banco 'financeiro_db' foi criado?</li>";
    echo "<li>Usuário 'root' sem senha está correto?</li>";
    echo "</ul>";
}
?>
