<?php
/**
 * Model: Categoria
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

class Categoria
{
    private $db;
    private $tabela = 'categorias';

    public $id;
    public $nome;
    public $icone;
    public $cor;
    public $ordem;

    public function __construct()
    {
        $this->db = Database::getInstancia()->getConexao();
    }

    /**
     * Buscar todas as categorias
     */
    public function buscarTodas()
    {
        $query = "SELECT * FROM {$this->tabela} ORDER BY ordem, nome";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Buscar categoria por ID
     */
    public function buscarPorId($id)
    {
        $query = "SELECT * FROM {$this->tabela} WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }
}
