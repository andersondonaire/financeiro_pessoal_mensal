<?php
/**
 * Gerador de hash de senha
 * Uso: php gerar_senha.php
 */

echo "=== GERADOR DE HASH DE SENHA ===\n\n";

$nome_usuario = '';
$email_usuario = '';

// Altere aqui a senha desejada
$senha = '';

$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "Senha original: {$senha}\n";
echo "Hash gerado: {$hash}\n\n";

// SQL pronto para copiar
echo "--- SQL PARA INSERIR NOVO USUÁRIO ---\n";
echo "INSERT INTO usuarios (nome, email, senha, cor, ativo) \n";
echo "VALUES ('{$nome_usuario}', '{$email_usuario}', '{$hash}', '#db34cdff', 1);\n\n";

echo "Ou use este UPDATE se o usuário já existe:\n";
echo "UPDATE usuarios SET senha = '{$hash}' WHERE email = '{$email_usuario}';\n";