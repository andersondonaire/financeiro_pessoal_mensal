<?php
/**
 * Model: Pagamento
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

class Pagamento
{
    private $db;
    private $tabela = 'pagamentos';

    public $id;
    public $usuario_criador_id;
    public $categoria_id;
    public $descricao;
    public $valor_total;
    public $data_vencimento;
    public $parcela_atual;
    public $total_parcelas;
    public $cartao_credito;
    public $recorrente;
    public $compartilhado;
    public $usuarios_compartilhados;
    public $percentuais_divisao;
    public $confirmado;
    public $data_criacao;

    public function __construct()
    {
        $this->db = Database::getInstancia()->getConexao();
    }

    /**
     * Criar pagamento
     */
    public function criar()
    {
        $query = "INSERT INTO {$this->tabela} 
                  (usuario_criador_id, categoria_id, descricao, valor_total, data_vencimento, 
                   parcela_atual, total_parcelas, cartao_credito, recorrente, compartilhado, 
                   usuarios_compartilhados, percentuais_divisao, confirmado) 
                  VALUES (:usuario_criador_id, :categoria_id, :descricao, :valor_total, :data_vencimento,
                          :parcela_atual, :total_parcelas, :cartao_credito, :recorrente, :compartilhado,
                          :usuarios_compartilhados, :percentuais_divisao, :confirmado)";
        
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':usuario_criador_id', $this->usuario_criador_id);
        $stmt->bindParam(':categoria_id', $this->categoria_id);
        $stmt->bindParam(':descricao', $this->descricao);
        $stmt->bindParam(':valor_total', $this->valor_total);
        $stmt->bindParam(':data_vencimento', $this->data_vencimento);
        $stmt->bindParam(':parcela_atual', $this->parcela_atual);
        $stmt->bindParam(':total_parcelas', $this->total_parcelas);
        $stmt->bindParam(':cartao_credito', $this->cartao_credito);
        $stmt->bindParam(':recorrente', $this->recorrente);
        $stmt->bindParam(':compartilhado', $this->compartilhado);
        $stmt->bindParam(':usuarios_compartilhados', $this->usuarios_compartilhados);
        $stmt->bindParam(':percentuais_divisao', $this->percentuais_divisao);
        $stmt->bindParam(':confirmado', $this->confirmado);

        return $stmt->execute();
    }

    /**
     * Buscar pagamentos por usuário (criador ou compartilhado) e período
     */
    public function buscarPorUsuario($usuario_id, $data_inicio = null, $data_fim = null)
    {
        $query = "SELECT p.*, c.nome as categoria_nome, c.icone as categoria_icone, c.cor as categoria_cor,
                  pc.id as pagamento_ciclo_id
                  FROM {$this->tabela} p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  LEFT JOIN pagamentos_ciclo pc ON p.id = pc.pagamento_id
                  WHERE (p.usuario_criador_id = :usuario_id 
                         OR (p.compartilhado = 1 AND (
                             p.usuarios_compartilhados LIKE :usuario_like1 
                             OR p.usuarios_compartilhados LIKE :usuario_like2
                             OR p.usuarios_compartilhados LIKE :usuario_like3
                         )))";
        
        if ($data_inicio && $data_fim) {
            $query .= " AND p.data_vencimento BETWEEN :data_inicio AND :data_fim";
        }
        
        $query .= " ORDER BY p.data_vencimento DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        
        // Buscar com e sem aspas (JSON pode armazenar como número ou string)
        $usuario_like1 = '%[' . $usuario_id . ']%';      // [2]
        $usuario_like2 = '%[' . $usuario_id . ',%';       // [2,3]
        $usuario_like3 = '%,' . $usuario_id . ']%';       // [1,2]
        
        $stmt->bindParam(':usuario_like1', $usuario_like1);
        $stmt->bindParam(':usuario_like2', $usuario_like2);
        $stmt->bindParam(':usuario_like3', $usuario_like3);
        
        if ($data_inicio && $data_fim) {
            $stmt->bindParam(':data_inicio', $data_inicio);
            $stmt->bindParam(':data_fim', $data_fim);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Buscar pagamentos compartilhados não fechados
     */
    public function buscarCompartilhadosAbertos($usuario_id)
    {
        $query = "SELECT p.*, c.nome as categoria_nome 
                  FROM {$this->tabela} p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  LEFT JOIN pagamentos_ciclo pc ON p.id = pc.pagamento_id
                  WHERE p.compartilhado = 1 
                  AND (p.usuario_criador_id = :usuario_id OR (
                      p.usuarios_compartilhados LIKE :usuario_like1 
                      OR p.usuarios_compartilhados LIKE :usuario_like2
                      OR p.usuarios_compartilhados LIKE :usuario_like3
                  ))
                  AND pc.id IS NULL
                  ORDER BY p.data_vencimento";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        
        $usuario_like1 = '%[' . $usuario_id . ']%';
        $usuario_like2 = '%[' . $usuario_id . ',%';
        $usuario_like3 = '%,' . $usuario_id . ']%';
        
        $stmt->bindParam(':usuario_like1', $usuario_like1);
        $stmt->bindParam(':usuario_like2', $usuario_like2);
        $stmt->bindParam(':usuario_like3', $usuario_like3);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Buscar por ID
     */
    public function buscarPorId($id)
    {
        $query = "SELECT p.*, c.nome as categoria_nome, c.icone as categoria_icone 
                  FROM {$this->tabela} p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  WHERE p.id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Atualizar pagamento
     */
    public function atualizar()
    {
        $query = "UPDATE {$this->tabela} 
                  SET categoria_id = :categoria_id,
                      descricao = :descricao, 
                      valor_total = :valor_total, 
                      data_vencimento = :data_vencimento,
                      parcela_atual = :parcela_atual,
                      total_parcelas = :total_parcelas,
                      cartao_credito = :cartao_credito,
                      recorrente = :recorrente,
                      compartilhado = :compartilhado,
                      usuarios_compartilhados = :usuarios_compartilhados,
                      percentuais_divisao = :percentuais_divisao,
                      confirmado = :confirmado
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':categoria_id', $this->categoria_id);
        $stmt->bindParam(':descricao', $this->descricao);
        $stmt->bindParam(':valor_total', $this->valor_total);
        $stmt->bindParam(':data_vencimento', $this->data_vencimento);
        $stmt->bindParam(':parcela_atual', $this->parcela_atual);
        $stmt->bindParam(':total_parcelas', $this->total_parcelas);
        $stmt->bindParam(':cartao_credito', $this->cartao_credito);
        $stmt->bindParam(':recorrente', $this->recorrente);
        $stmt->bindParam(':compartilhado', $this->compartilhado);
        $stmt->bindParam(':usuarios_compartilhados', $this->usuarios_compartilhados);
        $stmt->bindParam(':percentuais_divisao', $this->percentuais_divisao);
        $stmt->bindParam(':confirmado', $this->confirmado);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    /**
     * Confirmar pagamento
     */
    public function confirmar($id)
    {
        $query = "UPDATE {$this->tabela} SET confirmado = 1 WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Deletar pagamento
     */
    public function deletar($id)
    {
        $query = "DELETE FROM {$this->tabela} WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Calcular total de pagamentos por período
     */
    public function calcularTotal($usuario_id, $data_inicio, $data_fim, $opcoes = [])
    {
        // Opções: apenas_confirmados, excluir_cartao, apenas_compartilhados
        $query = "SELECT SUM(
                    CASE 
                        WHEN p.usuario_criador_id = :usuario_id THEN p.valor_total
                        WHEN p.compartilhado = 1 THEN p.valor_total * (
                            JSON_EXTRACT(p.percentuais_divisao, CONCAT('$.\"', :usuario_id2, '\"')) / 100
                        )
                        ELSE 0
                    END
                  ) as total 
                  FROM {$this->tabela} p
                  LEFT JOIN pagamentos_ciclo pc ON p.id = pc.pagamento_id
                  LEFT JOIN ciclos ciclo ON pc.ciclo_id = ciclo.id AND ciclo.fechado = 1
                  WHERE (p.usuario_criador_id = :usuario_id3 
                         OR (p.compartilhado = 1 AND (
                             p.usuarios_compartilhados LIKE :usuario_like1
                             OR p.usuarios_compartilhados LIKE :usuario_like2
                             OR p.usuarios_compartilhados LIKE :usuario_like3
                         )))
                  AND p.data_vencimento BETWEEN :data_inicio AND :data_fim
                  AND (p.acerto_ciclo_id IS NULL)";
        
        if (!empty($opcoes['apenas_confirmados'])) {
            $query .= " AND p.confirmado = 1";
        }
        
        if (!empty($opcoes['excluir_cartao'])) {
            $query .= " AND p.cartao_credito = 0";
        }
        
        if (!empty($opcoes['apenas_compartilhados'])) {
            $query .= " AND p.compartilhado = 1";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':usuario_id2', $usuario_id);
        $stmt->bindParam(':usuario_id3', $usuario_id);
        
        $usuario_like1 = '%[' . $usuario_id . ']%';
        $usuario_like2 = '%[' . $usuario_id . ',%';
        $usuario_like3 = '%,' . $usuario_id . ']%';
        
        $stmt->bindParam(':usuario_like1', $usuario_like1);
        $stmt->bindParam(':usuario_like2', $usuario_like2);
        $stmt->bindParam(':usuario_like3', $usuario_like3);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado['total'] ?? 0;
    }

    /**
     * Buscar pagamentos por categoria e período
     */
    public function buscarPorCategoria($usuario_id, $data_inicio, $data_fim)
    {
        $query = "SELECT c.nome, c.cor, c.icone,
                  SUM(
                    CASE 
                        WHEN p.compartilhado = 0 THEN p.valor_total
                        ELSE p.valor_total * (
                            JSON_EXTRACT(p.percentuais_divisao, CONCAT('$.\"', :usuario_id, '\"')) / 100
                        )
                    END
                  ) as total
                  FROM {$this->tabela} p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  LEFT JOIN pagamentos_ciclo pc ON p.id = pc.pagamento_id
                  LEFT JOIN ciclos ciclo ON pc.ciclo_id = ciclo.id AND ciclo.fechado = 1
                  WHERE (p.usuario_criador_id = :usuario_id2 
                         OR (p.compartilhado = 1 AND (
                             p.usuarios_compartilhados LIKE :usuario_like1
                             OR p.usuarios_compartilhados LIKE :usuario_like2
                             OR p.usuarios_compartilhados LIKE :usuario_like3
                         )))
                  AND p.data_vencimento BETWEEN :data_inicio AND :data_fim
                  AND (p.acerto_ciclo_id IS NULL)
                  GROUP BY c.id, c.nome, c.cor, c.icone
                  ORDER BY total DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':usuario_id2', $usuario_id);
        
        $usuario_like1 = '%[' . $usuario_id . ']%';
        $usuario_like2 = '%[' . $usuario_id . ',%';
        $usuario_like3 = '%,' . $usuario_id . ']%';
        
        $stmt->bindParam(':usuario_like1', $usuario_like1);
        $stmt->bindParam(':usuario_like2', $usuario_like2);
        $stmt->bindParam(':usuario_like3', $usuario_like3);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
