<?php
/**
 * Controller: DashboardController
 * Gerencia dashboard e estatísticas
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Models/Recebimento.php';
require_once __DIR__ . '/../Models/Pagamento.php';
require_once __DIR__ . '/../Services/CalculoService.php';
require_once __DIR__ . '/../Auth.php';

class DashboardController
{
    private $recebimentoModel;
    private $pagamentoModel;

    public function __construct()
    {
        $this->recebimentoModel = new Recebimento();
        $this->pagamentoModel = new Pagamento();
    }

    /**
     * Obter dados do dashboard
     */
    public function getDados($usuario_id, $data_inicio, $data_fim)
    {
        // Calcular totais de recebimentos
        $total_recebimentos_confirmados = $this->recebimentoModel->calcularTotal($usuario_id, $data_inicio, $data_fim, true);
        $total_recebimentos_pendentes = $this->recebimentoModel->calcularTotal($usuario_id, $data_inicio, $data_fim, false) - $total_recebimentos_confirmados;
        $total_recebimentos = $total_recebimentos_confirmados + $total_recebimentos_pendentes;
        
        // Calcular totais de pagamentos
        $total_pagamentos_confirmados = $this->pagamentoModel->calcularTotal($usuario_id, $data_inicio, $data_fim, ['apenas_confirmados' => true]);
        $total_pagamentos_pendentes = $this->pagamentoModel->calcularTotal($usuario_id, $data_inicio, $data_fim, []) - $total_pagamentos_confirmados;
        $total_pagamentos = $total_pagamentos_confirmados + $total_pagamentos_pendentes;
        
        // Saldo confirmado (real - apenas pagos e recebidos)
        $saldo_confirmado = $total_recebimentos_confirmados - $total_pagamentos_confirmados;
        
        // Saldo projetado (simulando tudo pago e tudo recebido)
        $saldo_projetado = $total_recebimentos - $total_pagamentos;
        
        // Pagamentos por categoria
        $categorias = $this->pagamentoModel->buscarPorCategoria($usuario_id, $data_inicio, $data_fim);
        
        return [
            'recebimentos' => [
                'confirmados' => (float) $total_recebimentos_confirmados,  // Recebidos
                'pendentes' => (float) $total_recebimentos_pendentes,      // A receber
                'total' => (float) $total_recebimentos
            ],
            'pagamentos' => [
                'confirmados' => (float) $total_pagamentos_confirmados,    // Pagos
                'pendentes' => (float) $total_pagamentos_pendentes,        // A pagar
                'total' => (float) $total_pagamentos
            ],
            'saldo' => [
                'confirmado' => (float) $saldo_confirmado,                 // Saldo real atual
                'projetado' => (float) $saldo_projetado                    // Saldo se tudo for pago/recebido
            ],
            'categorias' => $categorias
        ];
    }

    /**
     * Obter período atual (mês corrente)
     */
    public static function getPeriodoAtual()
    {
        $data_inicio = date('Y-m-01');
        $data_fim = date('Y-m-t');
        
        return [
            'inicio' => $data_inicio,
            'fim' => $data_fim,
            'label' => date('F/Y')
        ];
    }
}
