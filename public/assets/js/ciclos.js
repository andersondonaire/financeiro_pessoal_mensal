/**
 * Ciclos - Fechamento de Contas Compartilhadas
 */

let dadosCalculo = null;

/**
 * Inicialização
 */
function init() {
    carregarCiclos();
}

/**
 * Calcular acertos entre usuários
 */
async function calcularAcertos() {
    const dataInicio = document.getElementById('data_inicio').value;
    const dataFim = document.getElementById('data_fim').value;
    
    if (!dataInicio || !dataFim) {
        App.mostrarToast('Informe o período', 'warning');
        return;
    }
    
    try {
        const response = await fetch(`/api/ciclos.php?action=calcular&data_inicio=${dataInicio}&data_fim=${dataFim}`);
        const resultado = await response.json();
        
        if (resultado.success) {
            dadosCalculo = resultado.data;
            renderizarResultado();
            document.getElementById('resultadoCalculo').style.display = 'block';
        } else {
            App.mostrarToast(resultado.message || 'Erro ao calcular', 'danger');
        }
    } catch (error) {
        console.error('Erro:', error);
        App.mostrarToast('Erro ao calcular acertos', 'danger');
    }
}

/**
 * Renderizar resultado do cálculo
 */
function renderizarResultado() {
    // Saldos
    const tabelaSaldos = document.getElementById('tabelaSaldos');
    tabelaSaldos.innerHTML = '';
    
    dadosCalculo.saldos.forEach(saldo => {
        const tr = document.createElement('tr');
        const saldoClass = saldo.saldo > 0 ? 'text-success' : saldo.saldo < 0 ? 'text-danger' : 'text-muted';
        const saldoTexto = saldo.saldo > 0 ? 'A receber' : saldo.saldo < 0 ? 'A pagar' : 'Acertado';
        
        tr.innerHTML = `
            <td><strong>${saldo.usuario_nome}</strong></td>
            <td>R$ ${saldo.total_pagou.toFixed(2).replace('.', ',')}</td>
            <td>R$ ${saldo.total_deve.toFixed(2).replace('.', ',')}</td>
            <td class="${saldoClass}">
                <strong>R$ ${Math.abs(saldo.saldo).toFixed(2).replace('.', ',')}</strong>
                <small class="ms-2">${saldoTexto}</small>
            </td>
        `;
        tabelaSaldos.appendChild(tr);
    });
    
    // Acertos
    const listaAcertos = document.getElementById('listaAcertos');
    listaAcertos.innerHTML = '';
    
    if (dadosCalculo.acertos.length === 0) {
        listaAcertos.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Todas as contas estão acertadas!</div>';
    } else {
        dadosCalculo.acertos.forEach(acerto => {
            const div = document.createElement('div');
            div.className = 'alert alert-warning d-flex align-items-center justify-content-between mb-2';
            div.innerHTML = `
                <div>
                    <i class="fas fa-user"></i> <strong>${acerto.de_usuario_nome}</strong> 
                    deve pagar 
                    <i class="fas fa-arrow-right mx-2"></i> 
                    <strong>${acerto.para_usuario_nome}</strong>
                </div>
                <div>
                    <strong class="fs-5">R$ ${acerto.valor.toFixed(2).replace('.', ',')}</strong>
                </div>
            `;
            listaAcertos.appendChild(div);
        });
    }
    
    // Pagamentos
    const tabelaPagamentos = document.getElementById('tabelaPagamentos');
    tabelaPagamentos.innerHTML = '';
    
    dadosCalculo.pagamentos.forEach(pag => {
        const tr = document.createElement('tr');
        
        // Para faturas de cartão, mostrar apenas o valor compartilhado
        let valorExibir = parseFloat(pag.valor_total);
        
        // Se for compartilhado e tiver percentuais, calcular o total compartilhado
        if (pag.compartilhado == 1 && pag.percentuais_divisao) {
            try {
                const percentuais = JSON.parse(pag.percentuais_divisao);
                // Somar todos os percentuais para obter o valor total compartilhado
                const totalPercentual = Object.values(percentuais).reduce((sum, perc) => sum + perc, 0);
                // Se a soma dos percentuais for menor que 100%, significa que só parte foi compartilhada
                if (totalPercentual < 100) {
                    // Calcular o valor total que foi compartilhado
                    valorExibir = (parseFloat(pag.valor_total) * totalPercentual) / 100;
                }
            } catch (e) {
                console.error('Erro ao processar percentuais:', e);
            }
        }
        
        tr.innerHTML = `
            <td>${new Date(pag.data_vencimento).toLocaleDateString('pt-BR')}</td>
            <td>${pag.descricao}</td>
            <td>${pag.criador_nome}</td>
            <td>R$ ${valorExibir.toFixed(2).replace('.', ',')}</td>
        `;
        tabelaPagamentos.appendChild(tr);
    });
}

/**
 * Fechar ciclo
 */
async function fecharCiclo() {
    if (!dadosCalculo || dadosCalculo.pagamentos.length === 0) {
        App.mostrarToast('Nenhum pagamento para fechar', 'warning');
        return;
    }
    
    const descricao = document.getElementById('descricao_ciclo').value;
    const dataInicio = document.getElementById('data_inicio').value;
    const dataFim = document.getElementById('data_fim').value;
    
    if (!descricao) {
        App.mostrarToast('Informe uma descrição para o ciclo', 'warning');
        return;
    }
    
    const msgAcertos = dadosCalculo.acertos.length > 0 
        ? `\n\nSerão criados ${dadosCalculo.acertos.length} lançamento(s) de acerto automático.`
        : '';
    
    if (!confirm(`Fechar ciclo "${descricao}"?\n\nIsso registrará ${dadosCalculo.pagamentos.length} pagamento(s) como acertados.${msgAcertos}`)) {
        return;
    }
    
    try {
        const pagamentosIds = dadosCalculo.pagamentos.map(p => p.id);
        
        const response = await fetch('/api/ciclos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                descricao,
                data_inicio: dataInicio,
                data_fim: dataFim,
                pagamentos_ids: pagamentosIds,
                acertos: dadosCalculo.acertos
            })
        });
        
        const resultado = await response.json();
        
        if (resultado.success) {
            App.mostrarToast(resultado.message, 'success', 5000);
            document.getElementById('resultadoCalculo').style.display = 'none';
            document.getElementById('descricao_ciclo').value = '';
            dadosCalculo = null;
            carregarCiclos();
        } else {
            App.mostrarToast(resultado.message || 'Erro ao fechar ciclo', 'danger');
        }
    } catch (error) {
        console.error('Erro:', error);
        App.mostrarToast('Erro ao fechar ciclo', 'danger');
    }
}

/**
 * Carregar ciclos fechados
 */
async function carregarCiclos() {
    try {
        const response = await fetch('/api/ciclos.php?action=listar');
        const resultado = await response.json();
        
        if (resultado.success) {
            renderizarCiclos(resultado.data);
        }
    } catch (error) {
        console.error('Erro ao carregar ciclos:', error);
    }
}

/**
 * Renderizar lista de ciclos
 */
function renderizarCiclos(ciclos) {
    const tbody = document.getElementById('tabelaCiclos');
    tbody.innerHTML = '';
    
    if (ciclos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Nenhum ciclo fechado</td></tr>';
        return;
    }
    
    ciclos.forEach(ciclo => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${ciclo.descricao}</strong></td>
            <td>
                ${new Date(ciclo.data_inicio).toLocaleDateString('pt-BR')} 
                até 
                ${new Date(ciclo.data_fim).toLocaleDateString('pt-BR')}
            </td>
            <td>${new Date(ciclo.data_criacao).toLocaleString('pt-BR')}</td>
            <td><span class="badge bg-info">${ciclo.total_pagamentos} pagamento(s)</span></td>
        `;
        tbody.appendChild(tr);
    });
}

// Inicializar ao carregar
document.addEventListener('DOMContentLoaded', init);
