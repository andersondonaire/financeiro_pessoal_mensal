<?php
/**
 * Model: Ciclo
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

class Ciclo
{
    private $db;
    private $tabela = 'ciclos';

    public $id;
    public $nome;
    public $data_inicio;
    public $data_fim;
    public $usuarios_envolvidos;
    public $fechado;
    public $data_fechamento;
    public $observacoes;
    public $data_criacao;

    public function __construct()
    {
        $this->db = Database::getInstancia()->getConexao();
    }

    /**
     * Criar novo ciclo
     */
    public function criar()
    {
        $query = "INSERT INTO {$this->tabela} 
                  (nome, data_inicio, data_fim, usuarios_envolvidos, observacoes) 
                  VALUES (:nome, :data_inicio, :data_fim, :usuarios_envolvidos, :observacoes)";
        
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':data_inicio', $this->data_inicio);
        $stmt->bindParam(':data_fim', $this->data_fim);
        $stmt->bindParam(':usuarios_envolvidos', $this->usuarios_envolvidos);
        $stmt->bindParam(':observacoes', $this->observacoes);

        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Fechar ciclo
     */
    public function fechar($id)
    {
        $query = "UPDATE {$this->tabela} 
                  SET fechado = 1, data_fechamento = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Buscar ciclos por usuário
     */
    public function buscarPorUsuario($usuario_id, $apenas_abertos = false)
    {
        $query = "SELECT * FROM {$this->tabela} 
                  WHERE usuarios_envolvidos LIKE :usuario_like";
        
        if ($apenas_abertos) {
            $query .= " AND fechado = 0";
        }
        
        $query .= " ORDER BY data_inicio DESC";
        
        $stmt = $this->db->prepare($query);
        $usuario_like = '%"' . $usuario_id . '"%';
        $stmt->bindParam(':usuario_like', $usuario_like);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Buscar ciclo por ID
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
     * Adicionar pagamentos ao ciclo
     */
    public function adicionarPagamentos($ciclo_id, $pagamentos_ids)
    {
        $query = "INSERT INTO pagamentos_ciclo (ciclo_id, pagamento_id) VALUES (:ciclo_id, :pagamento_id)";
        $stmt = $this->db->prepare($query);
        
        foreach ($pagamentos_ids as $pag_id) {
            $stmt->bindParam(':ciclo_id', $ciclo_id);
            $stmt->bindParam(':pagamento_id', $pag_id);
            $stmt->execute();
        }
        
        return true;
    }

    /**
     * Buscar pagamentos de um ciclo
     */
    public function buscarPagamentosCiclo($ciclo_id)
    {
        $query = "SELECT p.*, c.nome as categoria_nome, c.cor as categoria_cor
                  FROM pagamentos p
                  INNER JOIN pagamentos_ciclo pc ON p.id = pc.pagamento_id
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  WHERE pc.ciclo_id = :ciclo_id
                  ORDER BY p.data_vencimento";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':ciclo_id', $ciclo_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
