<?php
/**
 * Model: Recebimento
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

class Recebimento
{
    private $db;
    private $tabela = 'recebimentos';

    public $id;
    public $usuario_id;
    public $descricao;
    public $valor;
    public $data_recebimento;
    public $categoria_id;
    public $recorrente;
    public $confirmado;
    public $data_criacao;

    public function __construct()
    {
        $this->db = Database::getInstancia()->getConexao();
    }

    /**
     * Criar recebimento
     */
    public function criar()
    {
        $query = "INSERT INTO {$this->tabela} 
              (usuario_id, descricao, valor, data_recebimento, categoria_id, recorrente, confirmado) 
              VALUES (:usuario_id, :descricao, :valor, :data_recebimento, :categoria_id, :recorrente, :confirmado)";
        
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':usuario_id', $this->usuario_id);
        $stmt->bindParam(':descricao', $this->descricao);
        $stmt->bindParam(':valor', $this->valor);
        $stmt->bindParam(':data_recebimento', $this->data_recebimento);
        $stmt->bindParam(':categoria_id', $this->categoria_id);
        $stmt->bindParam(':recorrente', $this->recorrente);
        $stmt->bindParam(':confirmado', $this->confirmado);

        return $stmt->execute();
    }

    /**
     * Buscar recebimentos por usuário e período
     */
    public function buscarPorUsuario($usuario_id, $data_inicio = null, $data_fim = null)
    {
        $query = "SELECT r.*, c.nome AS categoria_nome, c.id AS categoria_id
              FROM {$this->tabela} r
              LEFT JOIN categorias c ON c.id = r.categoria_id
              WHERE r.usuario_id = :usuario_id";
        
        if ($data_inicio && $data_fim) {
            $query .= " AND data_recebimento BETWEEN :data_inicio AND :data_fim";
        }
        
        $query .= " ORDER BY r.data_recebimento DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        
        if ($data_inicio && $data_fim) {
            $stmt->bindParam(':data_inicio', $data_inicio);
            $stmt->bindParam(':data_fim', $data_fim);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Buscar por ID
     */
    public function buscarPorId($id)
    {
        $query = "SELECT * FROM {$this->tabela} WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Atualizar recebimento
     */
    public function atualizar()
    {
        $query = "UPDATE {$this->tabela} 
                  SET descricao = :descricao, 
                      valor = :valor, 
                      data_recebimento = :data_recebimento,
                      categoria_id = :categoria_id,
                      recorrente = :recorrente,
                      confirmado = :confirmado
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':descricao', $this->descricao);
        $stmt->bindParam(':valor', $this->valor);
        $stmt->bindParam(':data_recebimento', $this->data_recebimento);
        $stmt->bindParam(':categoria_id', $this->categoria_id);
        $stmt->bindParam(':recorrente', $this->recorrente);
        $stmt->bindParam(':confirmado', $this->confirmado);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    /**
     * Confirmar recebimento
     */
    public function confirmar($id)
    {
        $query = "UPDATE {$this->tabela} SET confirmado = 1 WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Deletar recebimento
     */
    public function deletar($id)
    {
        $query = "DELETE FROM {$this->tabela} WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Calcular total de recebimentos por período
     */
    public function calcularTotal($usuario_id, $data_inicio, $data_fim, $apenas_confirmados = false)
    {
        $query = "SELECT SUM(valor) as total FROM {$this->tabela} 
                  WHERE usuario_id = :usuario_id 
                  AND data_recebimento BETWEEN :data_inicio AND :data_fim";
        
        if ($apenas_confirmados) {
            $query .= " AND confirmado = 1";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado['total'] ?? 0;
    }
}
