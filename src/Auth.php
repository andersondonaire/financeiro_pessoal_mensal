<?php
/**
 * Helper: Autenticação e Sessão
 */

require_once __DIR__ . '/../config/config.php';

class Auth
{
    /**
     * Verificar se usuário está logado
     */
    public static function verificarLogin()
    {
        if (!isset($_SESSION['usuario_id'])) {
            if (!headers_sent()) {
                header('Location: ' . SITE_URL . '/login.php');
                exit;
            }
            die('Sessão expirada. <a href="' . SITE_URL . '/login.php">Fazer login</a>');
        }
    }

    /**
     * Fazer login
     */
    public static function login($usuario)
    {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_cor'] = $usuario['cor'];
    }

    /**
     * Fazer logout
     */
    public static function logout()
    {
        session_unset();
        session_destroy();
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }

    /**
     * Obter ID do usuário logado
     */
    public static function getUsuarioId()
    {
        return $_SESSION['usuario_id'] ?? null;
    }

    /**
     * Obter dados do usuário logado
     */
    public static function getUsuario()
    {
        return [
            'id' => $_SESSION['usuario_id'] ?? null,
            'nome' => $_SESSION['usuario_nome'] ?? null,
            'email' => $_SESSION['usuario_email'] ?? null,
            'cor' => $_SESSION['usuario_cor'] ?? '#007bff'
        ];
    }
}
