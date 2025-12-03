<?php
/**
 * API: Itens de Pagamentos (Detalhamento de Faturas)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::verificarLogin();

$usuario_id = Auth::getUsuarioId();
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstancia()->getConexao();
    
    switch ($metodo) {
        case 'GET':
            // Buscar itens de um pagamento específico
            $pagamento_id = $_GET['pagamento_id'] ?? null;
            
            if (!$pagamento_id) {
                throw new Exception('ID do pagamento não informado');
            }
            
            // Verificar se o usuário tem acesso a este pagamento
            $query = "SELECT usuario_criador_id FROM pagamentos WHERE id = :pagamento_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':pagamento_id', $pagamento_id);
            $stmt->execute();
            $pagamento = $stmt->fetch();
            
            if (!$pagamento || $pagamento['usuario_criador_id'] != $usuario_id) {
                throw new Exception('Acesso negado');
            }
            
            // Buscar itens
            $query = "SELECT * FROM pagamentos_itens WHERE pagamento_id = :pagamento_id ORDER BY data_compra, id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':pagamento_id', $pagamento_id);
            $stmt->execute();
            $itens = $stmt->fetchAll();
            
            // Processar JSON
            foreach ($itens as &$item) {
                $item['usuarios_compartilhados'] = $item['usuarios_compartilhados'] ? json_decode($item['usuarios_compartilhados'], true) : null;
                $item['percentuais_divisao'] = $item['percentuais_divisao'] ? json_decode($item['percentuais_divisao'], true) : null;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $itens
            ]);
            break;
            
        case 'POST':
            // Salvar múltiplos itens de uma vez
            $rawInput = file_get_contents('php://input');
            
            if (empty($rawInput)) {
                throw new Exception('Nenhum dado recebido');
            }
            
            $data = json_decode($rawInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON inválido: ' . json_last_error_msg());
            }
            
            $pagamento_id = $data['pagamento_id'] ?? null;
            $itens = $data['itens'] ?? [];
            
            if (!$pagamento_id || empty($itens)) {
                throw new Exception('Dados incompletos (pagamento_id ou itens vazios)');
            }
            
            if (!is_array($itens)) {
                throw new Exception('Itens deve ser um array');
            }
            
            // Verificar se o usuário é o criador do pagamento
            $query = "SELECT usuario_criador_id FROM pagamentos WHERE id = :pagamento_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':pagamento_id', $pagamento_id);
            $stmt->execute();
            $pagamento = $stmt->fetch();
            
            if (!$pagamento || $pagamento['usuario_criador_id'] != $usuario_id) {
                throw new Exception('Acesso negado');
            }
            
            $db->beginTransaction();
            
            try {
                $query = "INSERT INTO pagamentos_itens 
                          (pagamento_id, data_compra, descricao, valor, compartilhado, 
                           usuarios_compartilhados, percentuais_divisao)
                          VALUES (:pagamento_id, :data_compra, :descricao, :valor, :compartilhado,
                                  :usuarios_comp, :percentuais)";
                
                $stmt = $db->prepare($query);
                $count = 0;
                
                foreach ($itens as $item) {
                    // Validar item
                    if (!isset($item['data_compra']) || !isset($item['descricao']) || !isset($item['valor'])) {
                        throw new Exception('Item incompleto na posição ' . $count);
                    }
                    
                    // Converter arrays para JSON se necessário
                    $usuarios_comp = null;
                    $percentuais = null;
                    
                    if (isset($item['usuarios_compartilhados']) && is_array($item['usuarios_compartilhados'])) {
                        $usuarios_comp = json_encode($item['usuarios_compartilhados']);
                    } elseif (isset($item['usuarios_compartilhados']) && is_string($item['usuarios_compartilhados'])) {
                        $usuarios_comp = $item['usuarios_compartilhados'];
                    }
                    
                    if (isset($item['percentuais_divisao']) && is_array($item['percentuais_divisao'])) {
                        $percentuais = json_encode($item['percentuais_divisao']);
                    } elseif (isset($item['percentuais_divisao']) && is_string($item['percentuais_divisao'])) {
                        $percentuais = $item['percentuais_divisao'];
                    }
                    
                    $stmt->bindParam(':pagamento_id', $pagamento_id);
                    $stmt->bindParam(':data_compra', $item['data_compra']);
                    $stmt->bindParam(':descricao', $item['descricao']);
                    $stmt->bindParam(':valor', $item['valor']);
                    $stmt->bindParam(':compartilhado', $item['compartilhado']);
                    $stmt->bindParam(':usuarios_comp', $usuarios_comp);
                    $stmt->bindParam(':percentuais', $percentuais);
                    
                    if (!$stmt->execute()) {
                        throw new Exception('Erro ao inserir item ' . $count . ': ' . implode(', ', $stmt->errorInfo()));
                    }
                    
                    $count++;
                }
                
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => $count . ' item(ns) salvos com sucesso'
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;
            
        case 'DELETE':
            // Deletar um item específico
            $data = json_decode(file_get_contents('php://input'), true);
            $item_id = $data['id'] ?? null;
            
            if (!$item_id) {
                throw new Exception('ID do item não informado');
            }
            
            // Verificar permissão
            $query = "SELECT pi.*, p.usuario_criador_id 
                      FROM pagamentos_itens pi
                      JOIN pagamentos p ON pi.pagamento_id = p.id
                      WHERE pi.id = :item_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':item_id', $item_id);
            $stmt->execute();
            $item = $stmt->fetch();
            
            if (!$item || $item['usuario_criador_id'] != $usuario_id) {
                throw new Exception('Acesso negado');
            }
            
            $query = "DELETE FROM pagamentos_itens WHERE id = :item_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':item_id', $item_id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Item removido'
            ]);
            break;
            
        default:
            throw new Exception('Método não suportado');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
