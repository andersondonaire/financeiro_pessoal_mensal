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
        
        // Verificar se está visualizando mês futuro
        $mes_atual = date('Y-m');
        $mes_visualizado = date('Y-m', strtotime($data_inicio));
        $saldo_anterior = 0;
        
        if ($mes_visualizado > $mes_atual) {
            // Está visualizando mês futuro - buscar saldo do mês anterior
            $mes_anterior_inicio = date('Y-m-01', strtotime($data_inicio . ' -1 month'));
            $mes_anterior_fim = date('Y-m-t', strtotime($data_inicio . ' -1 month'));
            
            $rec_anterior = $this->recebimentoModel->calcularTotal($usuario_id, $mes_anterior_inicio, $mes_anterior_fim, true);
            $pag_anterior = $this->pagamentoModel->calcularTotal($usuario_id, $mes_anterior_inicio, $mes_anterior_fim, ['apenas_confirmados' => true]);
            
            $saldo_anterior = $rec_anterior - $pag_anterior;
        }
        
        // Saldo confirmado (real - apenas pagos e recebidos)
        $saldo_confirmado = $saldo_anterior + $total_recebimentos_confirmados - $total_pagamentos_confirmados;
        
        // Saldo projetado (simulando tudo pago e tudo recebido)
        $saldo_projetado = $saldo_anterior + $total_recebimentos - $total_pagamentos;
        
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
                'projetado' => (float) $saldo_projetado,                   // Saldo se tudo for pago/recebido
                'anterior' => (float) $saldo_anterior                      // Saldo do mês anterior
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
