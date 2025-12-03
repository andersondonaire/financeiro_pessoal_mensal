<?php
/**
 * API: Recebimentos
 * CRUD via fetch
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Models/Recebimento.php';

Auth::verificarLogin();

$usuario_id = Auth::getUsuarioId();
$metodo = $_SERVER['REQUEST_METHOD'];
$recebimentoModel = new Recebimento();

try {
    switch ($metodo) {
        case 'GET':
            // Listar recebimentos
            $data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
            $data_fim = $_GET['data_fim'] ?? date('Y-m-t');
            
            $recebimentos = $recebimentoModel->buscarPorUsuario($usuario_id, $data_inicio, $data_fim);
            echo json_encode(['success' => true, 'data' => $recebimentos]);
            break;

        case 'POST':
            // Criar recebimento
            $data = json_decode(file_get_contents('php://input'), true);
            
            $recebimentoModel->usuario_id = $usuario_id;
            $recebimentoModel->descricao = strtoupper($data['descricao']);
            $recebimentoModel->valor = $data['valor'];
            $recebimentoModel->data_recebimento = $data['data_recebimento'];
            $recebimentoModel->recorrente = $data['recorrente'] ?? 0;
            $recebimentoModel->confirmado = $data['confirmado'] ?? 0;
            
            if ($recebimentoModel->criar()) {
                echo json_encode(['success' => true, 'message' => 'Recebimento criado com sucesso!']);
            } else {
                throw new Exception('Erro ao criar recebimento');
            }
            break;

        case 'PUT':
            // Atualizar recebimento
            $data = json_decode(file_get_contents('php://input'), true);
            
            $recebimentoModel->id = $data['id'];
            $recebimentoModel->descricao = strtoupper($data['descricao']);
            $recebimentoModel->valor = $data['valor'];
            $recebimentoModel->data_recebimento = $data['data_recebimento'];
            $recebimentoModel->recorrente = $data['recorrente'] ?? 0;
            $recebimentoModel->confirmado = $data['confirmado'] ?? 0;
            
            if ($recebimentoModel->atualizar()) {
                echo json_encode(['success' => true, 'message' => 'Recebimento atualizado!']);
            } else {
                throw new Exception('Erro ao atualizar');
            }
            break;

        case 'DELETE':
            // Deletar recebimento
            $id = $_GET['id'] ?? null;
            
            if ($recebimentoModel->deletar($id)) {
                echo json_encode(['success' => true, 'message' => 'Recebimento excluído!']);
            } else {
                throw new Exception('Erro ao excluir');
            }
            break;

        case 'PATCH':
            // Confirmar recebimento
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($recebimentoModel->confirmar($data['id'])) {
                echo json_encode(['success' => true, 'message' => 'Recebimento confirmado!']);
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
