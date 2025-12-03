<?php
/**
 * Classe de Conexão com Banco de Dados
 * PDO - MySQL 5.7
 */

class Database
{
    private static $instancia = null;
    private $conexao;

    private function __construct()
    {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            
            $opcoes = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->conexao = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
        } catch (PDOException $e) {
            die('Erro na conexão: ' . $e->getMessage());
        }
    }

    public static function getInstancia()
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function getConexao()
    {
        return $this->conexao;
    }

    // Previne clonagem
    private function __clone() {}

    // Previne unserialize
    public function __wakeup()
    {
        throw new Exception("Não é permitido unserialize desta classe.");
    }
}
