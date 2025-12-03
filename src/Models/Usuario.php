<?php
/**
 * Model: Usuario
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

class Usuario
{
    private $db;
    private $tabela = 'usuarios';

    public $id;
    public $nome;
    public $email;
    public $senha;
    public $cor;
    public $ativo;
    public $data_criacao;

    public function __construct()
    {
        $this->db = Database::getInstancia()->getConexao();
    }

    /**
     * Criar novo usuário
     */
    public function criar()
    {
        $query = "INSERT INTO {$this->tabela} (nome, email, senha, cor) VALUES (:nome, :email, :senha, :cor)";
        $stmt = $this->db->prepare($query);

        $senha_hash = password_hash($this->senha, PASSWORD_DEFAULT);

        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':senha', $senha_hash);
        $stmt->bindParam(':cor', $this->cor);

        return $stmt->execute();
    }

    /**
     * Buscar todos os usuários ativos
     */
    public function buscarTodos()
    {
        $query = "SELECT id, nome, email, cor, ativo FROM {$this->tabela} WHERE ativo = 1 ORDER BY nome";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Buscar usuário por ID
     */
    public function buscarPorId($id)
    {
        $query = "SELECT id, nome, email, cor, ativo, data_criacao FROM {$this->tabela} WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Buscar usuário por email
     */
    public function buscarPorEmail($email)
    {
        $query = "SELECT * FROM {$this->tabela} WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Atualizar usuário
     */
    public function atualizar()
    {
        $query = "UPDATE {$this->tabela} SET nome = :nome, email = :email, cor = :cor WHERE id = :id";
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':cor', $this->cor);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    /**
     * Atualizar senha
     */
    public function atualizarSenha($id, $novaSenha)
    {
        $query = "UPDATE {$this->tabela} SET senha = :senha WHERE id = :id";
        $stmt = $this->db->prepare($query);

        $senha_hash = password_hash($novaSenha, PASSWORD_DEFAULT);

        $stmt->bindParam(':senha', $senha_hash);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    /**
     * Verificar login
     */
    public function login($email, $senha)
    {
        $usuario = $this->buscarPorEmail($email);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            return $usuario;
        }

        return false;
    }
}
