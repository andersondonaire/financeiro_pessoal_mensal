<?php
/**
 * API: Ciclos (Fechamento de Contas Compartilhadas)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Models/Pagamento.php';
require_once __DIR__ . '/../src/Models/Usuario.php';

Auth::verificarLogin();

$usuario_id = Auth::getUsuarioId();
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    switch ($metodo) {
        case 'GET':
            if (isset($_GET['action']) && $_GET['action'] === 'calcular') {
                // Calcular acertos entre usuários
                $data_inicio = $_GET['data_inicio'] ?? null;
                $data_fim = $_GET['data_fim'] ?? null;
                
                if (!$data_inicio || !$data_fim) {
                    throw new Exception('Período não informado');
                }
                
                $pagamentoModel = new Pagamento();
                $db = Database::getInstancia()->getConexao();
                
                // Buscar pagamentos compartilhados no período que não foram fechados
                $query = "SELECT p.*, u.nome as criador_nome
                          FROM pagamentos p
                          LEFT JOIN usuarios u ON p.usuario_criador_id = u.id
                          LEFT JOIN pagamentos_ciclo pc ON p.id = pc.pagamento_id
                          WHERE p.compartilhado = 1 
                          AND p.data_vencimento BETWEEN :data_inicio AND :data_fim
                          AND pc.id IS NULL
                          ORDER BY p.data_vencimento";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':data_inicio', $data_inicio);
                $stmt->bindParam(':data_fim', $data_fim);
                $stmt->execute();
                $pagamentos = $stmt->fetchAll();
                
                // Calcular quanto cada usuário pagou e deve
                $usuarios_balanco = [];
                
                foreach ($pagamentos as $pag) {
                    $criador_id = $pag['usuario_criador_id'];
                    $valor_total = $pag['valor_total'];
                    $usuarios_comp = json_decode($pag['usuarios_compartilhados'], true);
                    $percentuais = json_decode($pag['percentuais_divisao'], true);
                    
                    // Inicializar criador se necessário
                    if (!isset($usuarios_balanco[$criador_id])) {
                        $usuarios_balanco[$criador_id] = ['pagou' => 0, 'deve' => 0];
                    }
                    
                    // Se tem compartilhamento, calcular valores compartilhados
                    if ($usuarios_comp && $percentuais) {
                        // Calcular o valor total que foi compartilhado (soma de todos os percentuais)
                        // Por exemplo: se os percentuais são {1: 50%, 2: 50%} de R$ 4250,48
                        // mas representam apenas R$ 448,85 compartilhados,
                        // então cada usuário tem sua parte proporcional
                        
                        foreach ($usuarios_comp as $uid) {
                            if (!isset($usuarios_balanco[$uid])) {
                                $usuarios_balanco[$uid] = ['pagou' => 0, 'deve' => 0];
                            }
                            
                            $percentual = $percentuais[$uid] ?? 0;
                            $valor_usuario = ($valor_total * $percentual) / 100;
                            
                            if ($uid == $criador_id) {
                                // Criador pagou sua parte dos itens compartilhados
                                $usuarios_balanco[$uid]['pagou'] += $valor_usuario;
                            } else {
                                // Não-criador deve sua parte ao criador
                                $usuarios_balanco[$uid]['deve'] += $valor_usuario;
                            }
                        }
                    } else {
                        // Sem compartilhamento: criador pagou tudo sozinho (não entra no acerto)
                        // Não precisa contabilizar pois não há nada a acertar
                    }
                }
                
                // Calcular saldos (quem pagou - quem deve)
                $saldos = [];
                $usuarioModel = new Usuario();
                
                foreach ($usuarios_balanco as $uid => $balanco) {
                    $usuario = $usuarioModel->buscarPorId($uid);
                    $saldo = $balanco['pagou'] - $balanco['deve'];
                    
                    $saldos[] = [
                        'usuario_id' => $uid,
                        'usuario_nome' => $usuario['nome'] ?? 'Desconhecido',
                        'total_pagou' => $balanco['pagou'],
                        'total_deve' => $balanco['deve'],
                        'saldo' => $saldo
                    ];
                }
                
                // Calcular acertos (quem deve pagar a quem)
                $acertos = [];
                $credores = array_filter($saldos, fn($s) => $s['saldo'] > 0.01);
                $devedores = array_filter($saldos, fn($s) => $s['saldo'] < -0.01);
                
                usort($credores, fn($a, $b) => $b['saldo'] <=> $a['saldo']);
                usort($devedores, fn($a, $b) => $a['saldo'] <=> $b['saldo']);
                
                $credores = array_values($credores);
                $devedores = array_values($devedores);
                
                $i = 0;
                $j = 0;
                
                while ($i < count($credores) && $j < count($devedores)) {
                    $credor = $credores[$i];
                    $devedor = $devedores[$j];
                    
                    $valor_acerto = min($credor['saldo'], abs($devedor['saldo']));
                    
                    if ($valor_acerto > 0.01) {
                        $acertos[] = [
                            'de_usuario_id' => $devedor['usuario_id'],
                            'de_usuario_nome' => $devedor['usuario_nome'],
                            'para_usuario_id' => $credor['usuario_id'],
                            'para_usuario_nome' => $credor['usuario_nome'],
                            'valor' => $valor_acerto
                        ];
                    }
                    
                    $credores[$i]['saldo'] -= $valor_acerto;
                    $devedores[$j]['saldo'] += $valor_acerto;
                    
                    if ($credores[$i]['saldo'] < 0.01) $i++;
                    if (abs($devedores[$j]['saldo']) < 0.01) $j++;
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'pagamentos' => $pagamentos,
                        'saldos' => $saldos,
                        'acertos' => $acertos
                    ]
                ]);
                
            } elseif (isset($_GET['action']) && $_GET['action'] === 'listar') {
                // Listar ciclos fechados
                $db = Database::getInstancia()->getConexao();
                
                $query = "SELECT c.*, 
                          (SELECT COUNT(*) FROM pagamentos_ciclo WHERE ciclo_id = c.id) as total_pagamentos
                          FROM ciclos c
                          ORDER BY c.data_criacao DESC
                          LIMIT 50";
                
                $stmt = $db->prepare($query);
                $stmt->execute();
                $ciclos = $stmt->fetchAll();
                
                echo json_encode(['success' => true, 'data' => $ciclos]);
                
            } else {
                throw new Exception('Ação não especificada');
            }
            break;

        case 'POST':
            // Fechar ciclo
            $data = json_decode(file_get_contents('php://input'), true);
            
            $data_inicio = $data['data_inicio'] ?? null;
            $data_fim = $data['data_fim'] ?? null;
            $descricao = $data['descricao'] ?? null;
            $pagamentos_ids = $data['pagamentos_ids'] ?? [];
            $acertos = $data['acertos'] ?? [];
            
            if (!$data_inicio || !$data_fim || !$descricao || empty($pagamentos_ids)) {
                throw new Exception('Dados incompletos');
            }
            
            $db = Database::getInstancia()->getConexao();
            $db->beginTransaction();
            
            try {
                // Criar ciclo
                $query = "INSERT INTO ciclos (descricao, data_inicio, data_fim, usuario_criador_id) 
                          VALUES (:descricao, :data_inicio, :data_fim, :usuario_id)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':descricao', $descricao);
                $stmt->bindParam(':data_inicio', $data_inicio);
                $stmt->bindParam(':data_fim', $data_fim);
                $stmt->bindParam(':usuario_id', $usuario_id);
                $stmt->execute();
                
                $ciclo_id = $db->lastInsertId();
                
                // Vincular pagamentos ao ciclo
                $query = "INSERT INTO pagamentos_ciclo (ciclo_id, pagamento_id) VALUES (:ciclo_id, :pagamento_id)";
                $stmt = $db->prepare($query);
                
                foreach ($pagamentos_ids as $pag_id) {
                    $stmt->bindParam(':ciclo_id', $ciclo_id);
                    $stmt->bindParam(':pagamento_id', $pag_id);
                    $stmt->execute();
                }
                
                // Buscar ID da categoria "Acerto de Contas"
                $query = "SELECT id FROM categorias WHERE nome = 'Acerto de Contas' LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $categoria_acerto = $stmt->fetch();
                $categoria_acerto_id = $categoria_acerto['id'] ?? 1;
                
                // Criar lançamentos de acerto para cada devedor/credor
                if (!empty($acertos)) {
                    foreach ($acertos as $acerto) {
                        $devedor_id = $acerto['de_usuario_id'];
                        $credor_id = $acerto['para_usuario_id'];
                        $valor = $acerto['valor'];
                        
                        // Criar PAGAMENTO para o devedor (NÃO confirmado - aguardando pagamento)
                        $query = "INSERT INTO pagamentos 
                                  (usuario_criador_id, categoria_id, descricao, valor_total, data_vencimento, 
                                   compartilhado, confirmado, acerto_ciclo_id) 
                                  VALUES (:usuario_id, :categoria_id, :descricao, :valor, :data, 0, 0, :ciclo_id)";
                        $stmt = $db->prepare($query);
                        $desc_pag = 'ACERTO - ' . strtoupper($descricao);
                        $stmt->bindParam(':usuario_id', $devedor_id);
                        $stmt->bindParam(':categoria_id', $categoria_acerto_id);
                        $stmt->bindParam(':descricao', $desc_pag);
                        $stmt->bindParam(':valor', $valor);
                        $stmt->bindParam(':data', $data_fim);
                        $stmt->bindParam(':ciclo_id', $ciclo_id);
                        $stmt->execute();
                        
                        // Criar RECEBIMENTO para o credor (NÃO confirmado - aguardando recebimento)
                        $query = "INSERT INTO recebimentos 
                                  (usuario_id, descricao, valor, data_recebimento, confirmado, acerto_ciclo_id) 
                                  VALUES (:usuario_id, :descricao, :valor, :data, 0, :ciclo_id)";
                        $stmt = $db->prepare($query);
                        $desc_rec = 'ACERTO - ' . strtoupper($descricao);
                        $stmt->bindParam(':usuario_id', $credor_id);
                        $stmt->bindParam(':descricao', $desc_rec);
                        $stmt->bindParam(':valor', $valor);
                        $stmt->bindParam(':data', $data_fim);
                        $stmt->bindParam(':ciclo_id', $ciclo_id);
                        $stmt->execute();
                    }
                }
                
                // Marcar ciclo como fechado
                $query = "UPDATE ciclos SET fechado = 1, data_fechamento = NOW() WHERE id = :ciclo_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':ciclo_id', $ciclo_id);
                $stmt->execute();
                
                $db->commit();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Ciclo fechado com sucesso! Lançamentos de acerto criados automaticamente.',
                    'ciclo_id' => $ciclo_id
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        default:
            throw new Exception('Método não suportado');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
