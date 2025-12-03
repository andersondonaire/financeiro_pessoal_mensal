<?php
/**
 * API: Gerar Recorrências
 * Cria automaticamente lançamentos recorrentes para o próximo mês
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Models/Pagamento.php';
require_once __DIR__ . '/../src/Models/Recebimento.php';

Auth::verificarLogin();

$usuario_id = Auth::getUsuarioId();

try {
    $db = Database::getInstancia()->getConexao();
    
    // Data do próximo mês
    $proximo_mes_inicio = date('Y-m-01', strtotime('+1 month'));
    $proximo_mes_fim = date('Y-m-t', strtotime('+1 month'));
    
    $pagamentos_criados = 0;
    $recebimentos_criados = 0;
    
    // 1. Buscar pagamentos recorrentes do mês atual que ainda não foram criados no próximo mês
    $query_pag = "SELECT p.* FROM pagamentos p
                  WHERE p.recorrente = 1 
                  AND p.acerto_ciclo_id IS NULL
                  AND (p.usuario_criador_id = :usuario_id OR p.compartilhado = 1)
                  AND p.data_vencimento BETWEEN :mes_atual_inicio AND :mes_atual_fim
                  AND NOT EXISTS (
                      SELECT 1 FROM pagamentos p2 
                      WHERE p2.descricao = p.descricao 
                      AND p2.recorrente = 1
                      AND p2.data_vencimento BETWEEN :proximo_mes_inicio AND :proximo_mes_fim
                  )";
    
    $stmt_pag = $db->prepare($query_pag);
    $mes_atual_inicio = date('Y-m-01');
    $mes_atual_fim = date('Y-m-t');
    $stmt_pag->bindParam(':usuario_id', $usuario_id);
    $stmt_pag->bindParam(':mes_atual_inicio', $mes_atual_inicio);
    $stmt_pag->bindParam(':mes_atual_fim', $mes_atual_fim);
    $stmt_pag->bindParam(':proximo_mes_inicio', $proximo_mes_inicio);
    $stmt_pag->bindParam(':proximo_mes_fim', $proximo_mes_fim);
    $stmt_pag->execute();
    
    $pagamentos_recorrentes = $stmt_pag->fetchAll();
    
    // Criar pagamentos para próximo mês
    foreach ($pagamentos_recorrentes as $pag) {
        // Calcular nova data de vencimento (mesmo dia do próximo mês)
        $dia_vencimento = date('d', strtotime($pag['data_vencimento']));
        $nova_data = date('Y-m-' . $dia_vencimento, strtotime('+1 month'));
        
        // Ajustar se o dia não existir no próximo mês
        if (!checkdate(date('m', strtotime($nova_data)), $dia_vencimento, date('Y', strtotime($nova_data)))) {
            $nova_data = date('Y-m-t', strtotime('+1 month')); // Último dia do mês
        }
        
        $insert_pag = "INSERT INTO pagamentos 
                       (usuario_criador_id, categoria_id, descricao, valor_total, data_vencimento, 
                        parcela_atual, total_parcelas, cartao_credito, recorrente, compartilhado, 
                        usuarios_compartilhados, percentuais_divisao, confirmado)
                       VALUES 
                       (:usuario_criador_id, :categoria_id, :descricao, :valor_total, :data_vencimento,
                        :parcela_atual, :total_parcelas, :cartao_credito, 1, :compartilhado,
                        :usuarios_compartilhados, :percentuais_divisao, 0)";
        
        $stmt_insert = $db->prepare($insert_pag);
        $stmt_insert->bindParam(':usuario_criador_id', $pag['usuario_criador_id']);
        $stmt_insert->bindParam(':categoria_id', $pag['categoria_id']);
        $stmt_insert->bindParam(':descricao', $pag['descricao']);
        $stmt_insert->bindParam(':valor_total', $pag['valor_total']);
        $stmt_insert->bindParam(':data_vencimento', $nova_data);
        $stmt_insert->bindParam(':parcela_atual', $pag['parcela_atual']);
        $stmt_insert->bindParam(':total_parcelas', $pag['total_parcelas']);
        $stmt_insert->bindParam(':cartao_credito', $pag['cartao_credito']);
        $stmt_insert->bindParam(':compartilhado', $pag['compartilhado']);
        $stmt_insert->bindParam(':usuarios_compartilhados', $pag['usuarios_compartilhados']);
        $stmt_insert->bindParam(':percentuais_divisao', $pag['percentuais_divisao']);
        
        if ($stmt_insert->execute()) {
            $pagamentos_criados++;
        }
    }
    
    // 2. Buscar recebimentos recorrentes do mês atual que ainda não foram criados no próximo mês
    $query_rec = "SELECT r.* FROM recebimentos r
                  WHERE r.recorrente = 1 
                  AND r.acerto_ciclo_id IS NULL
                  AND r.usuario_id = :usuario_id
                  AND r.data_recebimento BETWEEN :mes_atual_inicio AND :mes_atual_fim
                  AND NOT EXISTS (
                      SELECT 1 FROM recebimentos r2 
                      WHERE r2.descricao = r.descricao 
                      AND r2.recorrente = 1
                      AND r2.data_recebimento BETWEEN :proximo_mes_inicio AND :proximo_mes_fim
                  )";
    
    $stmt_rec = $db->prepare($query_rec);
    $stmt_rec->bindParam(':usuario_id', $usuario_id);
    $stmt_rec->bindParam(':mes_atual_inicio', $mes_atual_inicio);
    $stmt_rec->bindParam(':mes_atual_fim', $mes_atual_fim);
    $stmt_rec->bindParam(':proximo_mes_inicio', $proximo_mes_inicio);
    $stmt_rec->bindParam(':proximo_mes_fim', $proximo_mes_fim);
    $stmt_rec->execute();
    
    $recebimentos_recorrentes = $stmt_rec->fetchAll();
    
    // Criar recebimentos para próximo mês
    foreach ($recebimentos_recorrentes as $rec) {
        // Calcular nova data de recebimento (mesmo dia do próximo mês)
        $dia_recebimento = date('d', strtotime($rec['data_recebimento']));
        $nova_data = date('Y-m-' . $dia_recebimento, strtotime('+1 month'));
        
        // Ajustar se o dia não existir no próximo mês
        if (!checkdate(date('m', strtotime($nova_data)), $dia_recebimento, date('Y', strtotime($nova_data)))) {
            $nova_data = date('Y-m-t', strtotime('+1 month')); // Último dia do mês
        }
        
        $insert_rec = "INSERT INTO recebimentos 
                       (usuario_id, descricao, valor, data_recebimento, categoria_id, recorrente, confirmado)
                       VALUES 
                       (:usuario_id, :descricao, :valor, :data_recebimento, :categoria_id, 1, 0)";
        
        $stmt_insert = $db->prepare($insert_rec);
        $stmt_insert->bindParam(':usuario_id', $rec['usuario_id']);
        $stmt_insert->bindParam(':descricao', $rec['descricao']);
        $stmt_insert->bindParam(':valor', $rec['valor']);
        $stmt_insert->bindParam(':data_recebimento', $nova_data);
        $stmt_insert->bindParam(':categoria_id', $rec['categoria_id']);
        
        if ($stmt_insert->execute()) {
            $recebimentos_criados++;
        }
    }
    
    $mensagem = "Recorrências geradas: {$pagamentos_criados} pagamento(s) e {$recebimentos_criados} recebimento(s)";
    
    if ($pagamentos_criados === 0 && $recebimentos_criados === 0) {
        $mensagem = "Nenhuma recorrência nova para gerar. Todas já existem no próximo mês.";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $mensagem,
        'pagamentos_criados' => $pagamentos_criados,
        'recebimentos_criados' => $recebimentos_criados
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao gerar recorrências: ' . $e->getMessage()
    ]);
}
