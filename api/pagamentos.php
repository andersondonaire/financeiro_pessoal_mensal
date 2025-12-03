<?php
/**
 * API: Pagamentos
 * CRUD via fetch com compartilhamento e parcelamento
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Models/Pagamento.php';
require_once __DIR__ . '/../src/Models/Usuario.php';
require_once __DIR__ . '/../src/Services/CalculoService.php';

Auth::verificarLogin();

$usuario_id = Auth::getUsuarioId();
$metodo = $_SERVER['REQUEST_METHOD'];
$pagamentoModel = new Pagamento();

try {
    switch ($metodo) {
        case 'GET':
            if (isset($_GET['action']) && $_GET['action'] === 'usuarios') {
                // Listar usuários para compartilhamento
                $usuarioModel = new Usuario();
                $usuarios = $usuarioModel->buscarTodos();
                echo json_encode(['success' => true, 'data' => $usuarios]);
            } else {
                // Listar pagamentos
                $data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
                $data_fim = $_GET['data_fim'] ?? date('Y-m-t');
                
                $pagamentos = $pagamentoModel->buscarPorUsuario($usuario_id, $data_inicio, $data_fim);
                echo json_encode(['success' => true, 'data' => $pagamentos]);
            }
            break;

        case 'POST':
            // Criar pagamento (com parcelamento se necessário)
            $data = json_decode(file_get_contents('php://input'), true);

            // Validações básicas
            if (!$data) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'JSON inválido']);
                break;
            }

            $descricao = isset($data['descricao']) ? trim($data['descricao']) : '';
            $categoria_id = isset($data['categoria_id']) ? (int)$data['categoria_id'] : 0;
            $valor_total = isset($data['valor_total']) ? (float)$data['valor_total'] : null;
            $data_venc = isset($data['data_vencimento']) ? $data['data_vencimento'] : '';
            $total_parcelas = isset($data['total_parcelas']) ? (int)$data['total_parcelas'] : 1;

            if ($descricao === '' || $categoria_id <= 0 || $valor_total === null || $valor_total <= 0 || $data_venc === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Campos obrigatórios ausentes: descrição, categoria, valor e data']);
                break;
            }
            if ($total_parcelas < 1) { $total_parcelas = 1; }
            
            // Calcular divisão se compartilhado
            $usuarios_compartilhados = null;
            $percentuais_divisao = null;
            
            if (!empty($data['compartilhado']) && !empty($data['usuarios'])) {
                $usuarios_ids = $data['usuarios'];
                
                // IMPORTANTE: Incluir o criador na lista de usuários compartilhados
                if (!in_array($usuario_id, $usuarios_ids)) {
                    array_unshift($usuarios_ids, $usuario_id);
                }
                
                // Se tem percentuais customizados
                if (!empty($data['percentuais'])) {
                    $percentuais = $data['percentuais'];
                } else {
                    // Divisão igual entre TODOS (incluindo o criador)
                    $percentuais = CalculoService::gerarPercentuaisIguais($usuarios_ids);
                }
                
                $usuarios_compartilhados = json_encode($usuarios_ids);
                $percentuais_divisao = json_encode($percentuais);
            }
            
            // Se for parcelado, criar múltiplos registros
            // Já temos $total_parcelas validado
            $valor_parcela = $valor_total / $total_parcelas;
            
            $ultimo_id = null;
            
            for ($i = 1; $i <= $total_parcelas; $i++) {
                $pagamentoModel->usuario_criador_id = $usuario_id;
                $pagamentoModel->categoria_id = $categoria_id;
                $pagamentoModel->descricao = strtoupper($descricao) . ($total_parcelas > 1 ? " ($i/$total_parcelas)" : '');
                $pagamentoModel->valor_total = $valor_parcela;
                
                // Calcular data de vencimento (mês + i-1)
                $data_base = new DateTime($data_venc);
                if ($i > 1) {
                    $data_base->modify('+' . ($i - 1) . ' month');
                }
                $pagamentoModel->data_vencimento = $data_base->format('Y-m-d');
                
                $pagamentoModel->parcela_atual = $total_parcelas > 1 ? $i : null;
                $pagamentoModel->total_parcelas = $total_parcelas > 1 ? $total_parcelas : null;
                $pagamentoModel->recorrente = $data['recorrente'] ?? 0;
                $pagamentoModel->cartao_credito = $data['cartao_credito'] ?? 0;
                $pagamentoModel->compartilhado = !empty($data['compartilhado']) ? 1 : 0;
                $pagamentoModel->usuarios_compartilhados = $usuarios_compartilhados;
                $pagamentoModel->percentuais_divisao = $percentuais_divisao;
                $pagamentoModel->confirmado = $data['confirmado'] ?? 0;
                
                if (!$pagamentoModel->criar()) {
                    throw new Exception('Erro ao criar pagamento');
                }
                
                // Guardar o ID do primeiro pagamento criado
                if ($i == 1) {
                    $db = Database::getInstancia()->getConexao();
                    $ultimo_id = $db->lastInsertId();
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Pagamento(s) criado(s) com sucesso!',
                'data' => ['id' => $ultimo_id]
            ]);
            break;

        case 'PUT':
            // Atualizar pagamento
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Recalcular divisão se compartilhado
            $usuarios_compartilhados = null;
            $percentuais_divisao = null;
            
            if (!empty($data['compartilhado']) && !empty($data['usuarios'])) {
                $usuarios_ids = $data['usuarios'];
                
                // IMPORTANTE: Incluir o criador na lista de usuários compartilhados
                if (!in_array($usuario_id, $usuarios_ids)) {
                    array_unshift($usuarios_ids, $usuario_id);
                }
                
                if (!empty($data['percentuais'])) {
                    $percentuais = $data['percentuais'];
                } else {
                    $percentuais = CalculoService::gerarPercentuaisIguais($usuarios_ids);
                }
                
                $usuarios_compartilhados = json_encode($usuarios_ids);
                $percentuais_divisao = json_encode($percentuais);
            }
            
            $pagamentoModel->id = $data['id'];
            $pagamentoModel->categoria_id = $data['categoria_id'];
            $pagamentoModel->descricao = strtoupper($data['descricao']);
            $pagamentoModel->valor_total = $data['valor_total'];
            $pagamentoModel->data_vencimento = $data['data_vencimento'];
            $pagamentoModel->parcela_atual = $data['parcela_atual'] ?? null;
            $pagamentoModel->total_parcelas = $data['total_parcelas'] ?? null;
            $pagamentoModel->recorrente = $data['recorrente'] ?? 0;
            $pagamentoModel->cartao_credito = $data['cartao_credito'] ?? 0;
            $pagamentoModel->compartilhado = !empty($data['compartilhado']) ? 1 : 0;
            $pagamentoModel->usuarios_compartilhados = $usuarios_compartilhados;
            $pagamentoModel->percentuais_divisao = $percentuais_divisao;
            $pagamentoModel->confirmado = $data['confirmado'] ?? 0;
            
            if ($pagamentoModel->atualizar()) {
                echo json_encode(['success' => true, 'message' => 'Pagamento atualizado!']);
            } else {
                throw new Exception('Erro ao atualizar');
            }
            break;

        case 'DELETE':
            // Deletar pagamento
            $id = $_GET['id'] ?? null;
            
            if ($pagamentoModel->deletar($id)) {
                echo json_encode(['success' => true, 'message' => 'Pagamento excluído!']);
            } else {
                throw new Exception('Erro ao excluir');
            }
            break;

        case 'PATCH':
            // Confirmar pagamento
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($pagamentoModel->confirmar($data['id'])) {
                echo json_encode(['success' => true, 'message' => 'Pagamento confirmado!']);
            } else {
                throw new Exception('Erro ao confirmar');
            }
            break;

        default:
            throw new Exception('Método não suportado');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
