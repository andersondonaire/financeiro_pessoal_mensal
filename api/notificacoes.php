<?php
/**
 * API: Notificações
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Models/Pagamento.php';
require_once __DIR__ . '/../src/Models/Recebimento.php';

Auth::verificarLogin();

$usuario_id = Auth::getUsuarioId();
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    if ($metodo === 'GET') {
        $notificacoes = [];
        
        $db = Database::getInstancia()->getConexao();
        
        // 1. Pagamentos próximos ao vencimento (próximos 5 dias)
        $data_limite = date('Y-m-d', strtotime('+5 days'));
        $hoje = date('Y-m-d');
        
        $query = "SELECT p.id, p.descricao, p.valor_total, p.data_vencimento, c.nome as categoria_nome
                  FROM pagamentos p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  WHERE p.usuario_criador_id = :usuario_id
                  AND p.confirmado = 0
                  AND p.data_vencimento BETWEEN :hoje AND :data_limite
                  ORDER BY p.data_vencimento";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':hoje', $hoje);
        $stmt->bindParam(':data_limite', $data_limite);
        $stmt->execute();
        $pagamentos_proximos = $stmt->fetchAll();
        
        foreach ($pagamentos_proximos as $pag) {
            $dias = (strtotime($pag['data_vencimento']) - strtotime($hoje)) / 86400;
            $dias_texto = $dias == 0 ? 'hoje' : ($dias == 1 ? 'amanhã' : "em {$dias} dias");
            
            $notificacoes[] = [
                'tipo' => 'pagamento_vencimento',
                'titulo' => 'Pagamento próximo',
                'mensagem' => "{$pag['descricao']} - R$ " . number_format($pag['valor_total'], 2, ',', '.') . " vence {$dias_texto}",
                'icone' => 'fa-exclamation-triangle',
                'cor' => $dias == 0 ? 'danger' : 'warning',
                'data' => $pag['data_vencimento'],
                'link' => '/public/pagamentos.php'
            ];
        }
        
        // 2. Pagamentos compartilhados aguardando acerto
        $query = "SELECT COUNT(DISTINCT p.id) as total
                  FROM pagamentos p
                  LEFT JOIN pagamentos_ciclo pc ON p.id = pc.pagamento_id
                  WHERE p.compartilhado = 1
                  AND (p.usuario_criador_id = :usuario_id OR (
                      p.usuarios_compartilhados LIKE :usuario_like1 
                      OR p.usuarios_compartilhados LIKE :usuario_like2
                      OR p.usuarios_compartilhados LIKE :usuario_like3
                  ))
                  AND pc.id IS NULL
                  AND p.confirmado = 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        
        $usuario_like1 = '%[' . $usuario_id . ']%';
        $usuario_like2 = '%[' . $usuario_id . ',%';
        $usuario_like3 = '%,' . $usuario_id . ']%';
        
        $stmt->bindParam(':usuario_like1', $usuario_like1);
        $stmt->bindParam(':usuario_like2', $usuario_like2);
        $stmt->bindParam(':usuario_like3', $usuario_like3);
        
        $stmt->execute();
        $result = $stmt->fetch();
        $total_compartilhados = $result['total'] ?? 0;
        
        if ($total_compartilhados > 0) {
            $notificacoes[] = [
                'tipo' => 'ciclo_aberto',
                'titulo' => 'Contas aguardando acerto',
                'mensagem' => "{$total_compartilhados} pagamento(s) compartilhado(s) aguardando fechamento de ciclo",
                'icone' => 'fa-users',
                'cor' => 'info',
                'data' => date('Y-m-d'),
                'link' => '/public/ciclos.php',
                'badge' => $total_compartilhados
            ];
        }
        
        // 3. Recebimentos pendentes
        $query = "SELECT COUNT(*) as total
                  FROM recebimentos
                  WHERE usuario_id = :usuario_id
                  AND confirmado = 0
                  AND data_recebimento <= :hoje";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':hoje', $hoje);
        $stmt->execute();
        $result = $stmt->fetch();
        $total_recebimentos = $result['total'] ?? 0;
        
        if ($total_recebimentos > 0) {
            $notificacoes[] = [
                'tipo' => 'recebimento_pendente',
                'titulo' => 'Recebimentos pendentes',
                'mensagem' => "{$total_recebimentos} recebimento(s) para confirmar",
                'icone' => 'fa-money-bill-wave',
                'cor' => 'success',
                'data' => date('Y-m-d'),
                'link' => '/public/recebimentos.php',
                'badge' => $total_recebimentos
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $notificacoes,
            'total' => count($notificacoes)
        ]);
        
    } else {
        throw new Exception('Método não suportado');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
