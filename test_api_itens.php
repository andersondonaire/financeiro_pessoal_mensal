<?php
/**
 * Teste da API pagamentos_itens.php
 */

// Simular ambiente de requisição POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost';

// Simular sessão (ajuste o ID do usuário conforme necessário)
session_start();
$_SESSION['usuario_id'] = 1; // Ajuste conforme seu usuário

// Dados de teste
$dados = [
    'pagamento_id' => 1, // Ajuste para um ID válido no seu banco
    'itens' => [
        [
            'data_compra' => '2024-01-15',
            'descricao' => 'Teste Item 1',
            'valor' => 50.00,
            'compartilhado' => 0,
            'usuarios_compartilhados' => null,
            'percentuais_divisao' => null
        ],
        [
            'data_compra' => '2024-01-16',
            'descricao' => 'Teste Item 2',
            'valor' => 75.50,
            'compartilhado' => 1,
            'usuarios_compartilhados' => json_encode([1, 2]),
            'percentuais_divisao' => json_encode([50, 50])
        ]
    ]
];

// Simular entrada PHP
file_put_contents('php://input', json_encode($dados));

echo "=== TESTE DA API pagamentos_itens.php ===\n\n";
echo "Dados enviados:\n";
print_r($dados);
echo "\n\n--- Resposta da API ---\n";

// Capturar saída
ob_start();

try {
    // Simular input
    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($dados);
    
    // Incluir a API
    include 'api/pagamentos_itens.php';
    
    $output = ob_get_clean();
    echo $output;
    
    echo "\n\n--- Validação JSON ---\n";
    $json = json_decode($output, true);
    if ($json) {
        echo "✓ Resposta é JSON válido\n";
        print_r($json);
    } else {
        echo "✗ Resposta NÃO é JSON válido\n";
        echo "Erro: " . json_last_error_msg() . "\n";
    }
    
} catch (Exception $e) {
    $output = ob_get_clean();
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Saída capturada: " . $output . "\n";
}
