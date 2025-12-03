<?php
/**
 * Script: Gerar Pagamentos Recorrentes
 * Uso: php gerar_recorrencias.php
 * 
 * Este script deve ser executado mensalmente (cron job ou agendador de tarefas)
 * para gerar automaticamente os pagamentos marcados como recorrentes.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

echo "=== GERADOR DE PAGAMENTOS RECORRENTES ===\n";
echo "Data/Hora: " . date('d/m/Y H:i:s') . "\n\n";

try {
    $db = Database::getInstancia()->getConexao();
    
    // ====================================
    // 1. PROCESSAR PAGAMENTOS RECORRENTES
    // ====================================
    
    echo "--- PROCESSANDO RECORRÊNCIAS ---\n";
    
    // Buscar pagamentos recorrentes do mês anterior
    $mes_anterior = date('Y-m', strtotime('-1 month'));
    $mes_atual = date('Y-m');
    
    echo "Buscando pagamentos recorrentes de {$mes_anterior}...\n";
    
    $query = "SELECT p.* 
              FROM pagamentos p
              WHERE p.recorrente = 1
              AND DATE_FORMAT(p.data_vencimento, '%Y-%m') = :mes_anterior
              ORDER BY p.data_vencimento";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':mes_anterior', $mes_anterior);
    $stmt->execute();
    $pagamentos_recorrentes = $stmt->fetchAll();
    
    $total_encontrados = count($pagamentos_recorrentes);
    echo "Encontrados: {$total_encontrados} pagamento(s) recorrente(s)\n\n";
    
    $processados = 0;
    $erros = 0;
    $ignorados = 0;
    
    foreach ($pagamentos_recorrentes as $pag) {
        $descricao_original = $pag['descricao'];
        $valor_original = $pag['valor_total'];
        $dia_vencimento = date('d', strtotime($pag['data_vencimento']));
        
        // Calcular nova data de vencimento (mesmo dia do próximo mês)
        $ultimo_dia_mes = date('t', strtotime($mes_atual . '-01'));
        if ($dia_vencimento > $ultimo_dia_mes) {
            $dia_vencimento = $ultimo_dia_mes;
        }
        $nova_data = $mes_atual . '-' . str_pad($dia_vencimento, 2, '0', STR_PAD_LEFT);
        
        // Verificar se já existe lançamento para este mês
        $query_check = "SELECT COUNT(*) as total 
                        FROM pagamentos 
                        WHERE usuario_criador_id = :usuario_id
                        AND categoria_id = :categoria_id
                        AND DATE_FORMAT(data_vencimento, '%Y-%m') = :mes_atual
                        AND descricao = :descricao";
        
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(':usuario_id', $pag['usuario_criador_id']);
        $stmt_check->bindParam(':categoria_id', $pag['categoria_id']);
        $stmt_check->bindParam(':mes_atual', $mes_atual);
        $stmt_check->bindParam(':descricao', $descricao_original);
        $stmt_check->execute();
        $check = $stmt_check->fetch();
        
        if ($check['total'] > 0) {
            echo "  [SKIP] {$descricao_original} - Já existe para {$mes_atual}\n";
            $ignorados++;
            continue;
        }
        
        try {
            // Inserir novo pagamento recorrente
            $query_insert = "INSERT INTO pagamentos 
                            (usuario_criador_id, categoria_id, descricao, valor_total, data_vencimento,
                             parcela_atual, total_parcelas, cartao_credito, recorrente, compartilhado,
                             usuarios_compartilhados, percentuais_divisao, confirmado)
                            VALUES 
                            (:usuario_id, :categoria_id, :descricao, :valor, :data_vencimento,
                             1, 1, :cartao, 1, :compartilhado, :usuarios_comp, :percentuais, 0)";
            
            $stmt_insert = $db->prepare($query_insert);
            $stmt_insert->bindParam(':usuario_id', $pag['usuario_criador_id']);
            $stmt_insert->bindParam(':categoria_id', $pag['categoria_id']);
            $stmt_insert->bindParam(':descricao', $descricao_original);
            $stmt_insert->bindParam(':valor', $valor_original);
            $stmt_insert->bindParam(':data_vencimento', $nova_data);
            $stmt_insert->bindParam(':cartao', $pag['cartao_credito']);
            $stmt_insert->bindParam(':compartilhado', $pag['compartilhado']);
            $stmt_insert->bindParam(':usuarios_comp', $pag['usuarios_compartilhados']);
            $stmt_insert->bindParam(':percentuais', $pag['percentuais_divisao']);
            $stmt_insert->execute();
            
            echo "  [OK] {$descricao_original} - R$ {$valor_original} - Vencimento: {$nova_data}\n";
            $processados++;
            
        } catch (Exception $e) {
            echo "  [ERRO] {$descricao_original} - {$e->getMessage()}\n";
            $erros++;
        }
    }
    
    echo "\n--- RESUMO RECORRÊNCIAS ---\n";
    echo "Total encontrados: {$total_encontrados}\n";
    echo "Processados: {$processados}\n";
    echo "Erros: {$erros}\n";
    echo "Ignorados (já existentes): {$ignorados}\n\n";
    
    // ====================================
    // 2. PROCESSAR PARCELAS PENDENTES
    // ====================================
    
    echo "--- PROCESSANDO PARCELAS PENDENTES ---\n";
    
    // Buscar pagamentos parcelados que têm parcelas pendentes
    $query = "SELECT p.*
              FROM pagamentos p
              WHERE p.total_parcelas > 1
              AND p.parcela_atual < p.total_parcelas
              AND p.data_vencimento < :hoje
              ORDER BY p.data_vencimento, p.parcela_atual";
    
    $hoje = date('Y-m-d');
    $stmt = $db->prepare($query);
    $stmt->bindParam(':hoje', $hoje);
    $stmt->execute();
    $pagamentos_parcelados = $stmt->fetchAll();
    
    $total_parcelados = count($pagamentos_parcelados);
    echo "Encontrados: {$total_parcelados} pagamento(s) com parcelas pendentes\n\n";
    
    $parcelas_geradas = 0;
    $parcelas_erros = 0;
    $parcelas_ignoradas = 0;
    
    foreach ($pagamentos_parcelados as $pag) {
        $parcela_atual = (int)$pag['parcela_atual'];
        $total_parcelas = (int)$pag['total_parcelas'];
        $parcelas_restantes = $total_parcelas - $parcela_atual;
        
        echo "  {$pag['descricao']} - Parcela {$parcela_atual}/{$total_parcelas} (faltam {$parcelas_restantes})\n";
        
        // Gerar as próximas parcelas
        for ($i = 1; $i <= $parcelas_restantes; $i++) {
            $proxima_parcela = $parcela_atual + $i;
            
            // Calcular data da próxima parcela (adicionar meses)
            $data_original = new DateTime($pag['data_vencimento']);
            $data_proxima = clone $data_original;
            $data_proxima->modify("+{$i} month");
            $nova_data = $data_proxima->format('Y-m-d');
            
            // Verificar se já existe esta parcela
            $query_check = "SELECT COUNT(*) as total 
                            FROM pagamentos 
                            WHERE usuario_criador_id = :usuario_id
                            AND categoria_id = :categoria_id
                            AND descricao = :descricao
                            AND parcela_atual = :parcela
                            AND data_vencimento = :data";
            
            $stmt_check = $db->prepare($query_check);
            $stmt_check->bindParam(':usuario_id', $pag['usuario_criador_id']);
            $stmt_check->bindParam(':categoria_id', $pag['categoria_id']);
            $stmt_check->bindParam(':descricao', $pag['descricao']);
            $stmt_check->bindParam(':parcela', $proxima_parcela);
            $stmt_check->bindParam(':data', $nova_data);
            $stmt_check->execute();
            $check_parcela = $stmt_check->fetch();
            
            if ($check_parcela['total'] > 0) {
                echo "    [SKIP] Parcela {$proxima_parcela}/{$total_parcelas} - Já existe\n";
                $parcelas_ignoradas++;
                continue;
            }
            
            try {
                // Inserir próxima parcela
                $query_insert = "INSERT INTO pagamentos 
                                (usuario_criador_id, categoria_id, descricao, valor_total, data_vencimento,
                                 parcela_atual, total_parcelas, cartao_credito, recorrente, compartilhado,
                                 usuarios_compartilhados, percentuais_divisao, confirmado)
                                VALUES 
                                (:usuario_id, :categoria_id, :descricao, :valor, :data_vencimento,
                                 :parcela_atual, :total_parcelas, :cartao, :recorrente, :compartilhado, 
                                 :usuarios_comp, :percentuais, 0)";
                
                $stmt_insert = $db->prepare($query_insert);
                $stmt_insert->bindParam(':usuario_id', $pag['usuario_criador_id']);
                $stmt_insert->bindParam(':categoria_id', $pag['categoria_id']);
                $stmt_insert->bindParam(':descricao', $pag['descricao']);
                $stmt_insert->bindParam(':valor', $pag['valor_total']);
                $stmt_insert->bindParam(':data_vencimento', $nova_data);
                $stmt_insert->bindParam(':parcela_atual', $proxima_parcela);
                $stmt_insert->bindParam(':total_parcelas', $total_parcelas);
                $stmt_insert->bindParam(':cartao', $pag['cartao_credito']);
                $stmt_insert->bindParam(':recorrente', $pag['recorrente']);
                $stmt_insert->bindParam(':compartilhado', $pag['compartilhado']);
                $stmt_insert->bindParam(':usuarios_comp', $pag['usuarios_compartilhados']);
                $stmt_insert->bindParam(':percentuais', $pag['percentuais_divisao']);
                $stmt_insert->execute();
                
                echo "    [OK] Parcela {$proxima_parcela}/{$total_parcelas} - R$ {$pag['valor_total']} - Vencimento: {$nova_data}\n";
                $parcelas_geradas++;
                
            } catch (Exception $e) {
                echo "    [ERRO] Parcela {$proxima_parcela}/{$total_parcelas} - {$e->getMessage()}\n";
                $parcelas_erros++;
            }
        }
    }
    
    echo "\n--- RESUMO PARCELAS ---\n";
    echo "Pagamentos com parcelas pendentes: {$total_parcelados}\n";
    echo "Parcelas geradas: {$parcelas_geradas}\n";
    echo "Erros: {$parcelas_erros}\n";
    echo "Ignoradas (já existentes): {$parcelas_ignoradas}\n";
    
    echo "\n=== RESUMO GERAL ===\n";
    echo "Recorrências processadas: {$processados}\n";
    echo "Parcelas geradas: {$parcelas_geradas}\n";
    echo "Total de lançamentos criados: " . ($processados + $parcelas_geradas) . "\n";
    
} catch (Exception $e) {
    echo "ERRO CRÍTICO: " . $e->getMessage() . "\n";
    exit(1);
}
