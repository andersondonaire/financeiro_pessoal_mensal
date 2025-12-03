<?php
/**
 * Service: CalculoService
 * Lógica de cálculos, divisões e validações
 */

class CalculoService
{
    /**
     * Calcular divisão de valor entre usuários
     * 
     * @param float $valor_total
     * @param array $usuarios_ids [1, 2, 3]
     * @param array $percentuais ["1" => 50, "2" => 30, "3" => 20] ou null para divisão igual
     * @return array ["1" => 50.0, "2" => 30.0, "3" => 20.0]
     */
    public static function calcularDivisao($valor_total, $usuarios_ids, $percentuais = null)
    {
        $resultado = [];
        
        // Se não houver percentuais definidos, dividir igualmente
        if ($percentuais === null || empty($percentuais)) {
            $quantidade_usuarios = count($usuarios_ids);
            $percentual_igual = 100 / $quantidade_usuarios;
            
            foreach ($usuarios_ids as $usuario_id) {
                $percentuais[$usuario_id] = $percentual_igual;
            }
        }
        
        // Calcular valores por usuário
        foreach ($usuarios_ids as $usuario_id) {
            $percentual = $percentuais[$usuario_id] ?? 0;
            $resultado[$usuario_id] = round(($valor_total * $percentual) / 100, 2);
        }
        
        return $resultado;
    }

    /**
     * Validar se percentuais somam 100%
     */
    public static function validarPercentuais($percentuais)
    {
        $total = array_sum($percentuais);
        return abs($total - 100) < 0.01; // Tolerância para arredondamento
    }

    /**
     * Calcular saldo do usuário por período
     * 
     * @param int $usuario_id
     * @param string $data_inicio
     * @param string $data_fim
     * @return array ['recebimentos' => float, 'pagamentos' => float, 'saldo' => float]
     */
    public static function calcularSaldo($usuario_id, $data_inicio, $data_fim)
    {
        require_once __DIR__ . '/../Models/Recebimento.php';
        require_once __DIR__ . '/../Models/Pagamento.php';
        
        $recebimentoModel = new Recebimento();
        $pagamentoModel = new Pagamento();
        
        $total_recebimentos = $recebimentoModel->calcularTotal($usuario_id, $data_inicio, $data_fim, true);
        $total_pagamentos = $pagamentoModel->calcularTotal($usuario_id, $data_inicio, $data_fim, ['apenas_confirmados' => true]);
        
        return [
            'recebimentos' => (float) $total_recebimentos,
            'pagamentos' => (float) $total_pagamentos,
            'saldo' => (float) ($total_recebimentos - $total_pagamentos)
        ];
    }

    /**
     * Calcular diferença entre saldo real e saldo sistema
     */
    public static function calcularDiferenca($saldo_real, $saldo_sistema)
    {
        return round($saldo_real - $saldo_sistema, 2);
    }

    /**
     * Formatar valor para exibição (R$ 1.234,56)
     */
    public static function formatarMoeda($valor)
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }

    /**
     * Converter valor brasileiro para float (1.234,56 => 1234.56)
     */
    public static function converterParaFloat($valor_br)
    {
        $valor = str_replace('.', '', $valor_br);
        $valor = str_replace(',', '.', $valor);
        return (float) $valor;
    }

    /**
     * Calcular valor da parcela
     */
    public static function calcularParcela($valor_total, $numero_parcelas)
    {
        return round($valor_total / $numero_parcelas, 2);
    }

    /**
     * Gerar array de percentuais iguais
     */
    public static function gerarPercentuaisIguais($usuarios_ids)
    {
        $percentuais = [];
        $quantidade = count($usuarios_ids);
        $percentual_igual = round(100 / $quantidade, 2);
        
        foreach ($usuarios_ids as $usuario_id) {
            $percentuais[$usuario_id] = $percentual_igual;
        }
        
        // Ajustar último percentual para garantir 100%
        $total = array_sum($percentuais);
        if ($total != 100) {
            $ultimo_id = end($usuarios_ids);
            $percentuais[$ultimo_id] += (100 - $total);
        }
        
        return $percentuais;
    }
}
