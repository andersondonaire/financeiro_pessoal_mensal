<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Models/Usuario.php';

$email = 'admin@financeiro.com';
$senha = 'admin123';

echo "<h2>Teste de Login</h2>";

$usuarioModel = new Usuario();
$usuario = $usuarioModel->buscarPorEmail($email);

if ($usuario) {
    echo "<p>✓ Usuário encontrado: " . $usuario['nome'] . "</p>";
    echo "<p>Hash no banco: " . substr($usuario['senha'], 0, 30) . "...</p>";
    
    if (password_verify($senha, $usuario['senha'])) {
        echo "<p style='color: green;'>✓ Senha correta! Login deve funcionar.</p>";
    } else {
        echo "<p style='color: red;'>✗ Senha incorreta!</p>";
        echo "<p>Vou gerar novo hash para 'admin123':</p>";
        $novo_hash = password_hash('admin123', PASSWORD_DEFAULT);
        echo "<code>$novo_hash</code>";
        
        echo "<h3>Execute este SQL para corrigir:</h3>";
        echo "<code>UPDATE usuarios SET senha = '$novo_hash' WHERE email = '$email';</code>";
    }
} else {
    echo "<p style='color: red;'>✗ Usuário não encontrado!</p>";
}
?>
