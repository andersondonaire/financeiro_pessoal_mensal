/**
 * Recebimentos - Interações com API via Fetch
 */

let tabela;
const API_URL = window.location.origin + '/api/recebimentos.php';

// Carregar recebimentos
async function carregarRecebimentos() {
    try {
        const response = await fetch(API_URL);
        const result = await response.json();

        if (result.success) {
            atualizarTabela(result.data);
        }
    } catch (error) {
        App.showToast('Erro ao carregar recebimentos', 'danger');
    }
}

// Atualizar DataTable
function atualizarTabela(dados) {
    if (tabela) {
        tabela.clear().destroy();
    }

    const tbody = document.querySelector('#tabelaRecebimentos tbody');
    tbody.innerHTML = '';

    dados.forEach(rec => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${App.formatDate(rec.data_recebimento)}</td>
            <td>
                ${rec.descricao}
                ${rec.recorrente == 1 ? '<span class="badge bg-info ms-2">Recorrente</span>' : ''}
            </td>
            <td>${rec.categoria_nome ? rec.categoria_nome : '-'}</td>
            <td class="text-success fw-bold">R$ ${parseFloat(rec.valor).toFixed(2).replace('.', ',')}</td>
            <td>
                ${rec.confirmado == 1 
                    ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Recebido</span>' 
                    : '<button class="btn btn-sm btn-outline-warning" onclick="confirmarRecebimento(' + rec.id + ')" title="Confirmar recebimento"><i class="fas fa-clock"></i> A Receber</button>'}
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editarRecebimento(${rec.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deletarRecebimento(${rec.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    tabela = jQuery('#tabelaRecebimentos').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' },
        order: [[0, 'desc']],
        pageLength: 25,
        scrollX: true
    });
}

// Salvar recebimento
async function salvarRecebimento() {
    const id = document.getElementById('recebimento_id').value;
    const dados = {
        descricao: document.getElementById('descricao').value,
        valor: App.unmaskMoney(document.getElementById('valor').value),
        data_recebimento: document.getElementById('data_recebimento').value,
        categoria_id: document.getElementById('categoria_id').value,
        recorrente: document.getElementById('recorrente').checked ? 1 : 0,
        confirmado: document.getElementById('confirmado').checked ? 1 : 0
    };

    try {
        const metodo = id ? 'PUT' : 'POST';
        if (id) dados.id = id;

        const response = await fetch(API_URL, {
            method: metodo,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });

        const result = await response.json();

        if (result.success) {
            App.showToast(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalRecebimento')).hide();
            carregarRecebimentos();
            limparForm();
        }
    } catch (error) {
        App.showToast('Erro ao salvar', 'danger');
    }
}

// Editar recebimento
async function editarRecebimento(id) {
    try {
        const response = await fetch(API_URL);
        const result = await response.json();

        if (result.success) {
            const recebimento = result.data.find(r => r.id == id);

            if (recebimento) {
                document.getElementById('recebimento_id').value = recebimento.id;
                document.getElementById('descricao').value = recebimento.descricao;
                document.getElementById('valor').value = parseFloat(recebimento.valor).toFixed(2).replace('.', ',');
                document.getElementById('data_recebimento').value = recebimento.data_recebimento;
                if (document.getElementById('categoria_id')) {
                    document.getElementById('categoria_id').value = recebimento.categoria_id || '';
                }
                document.getElementById('recorrente').checked = recebimento.recorrente == 1;
                document.getElementById('confirmado').checked = recebimento.confirmado == 1;
                document.getElementById('modalTitulo').textContent = 'Editar Recebimento';

                new bootstrap.Modal(document.getElementById('modalRecebimento')).show();
            }
        }
    } catch (error) {
        App.showToast('Erro ao carregar dados', 'danger');
    }
}

// Confirmar recebimento
async function confirmarRecebimento(id) {
    try {
        const response = await fetch(API_URL, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        const result = await response.json();

        if (result.success) {
            App.showToast(result.message, 'success');
            carregarRecebimentos();
        }
    } catch (error) {
        App.showToast('Erro ao confirmar', 'danger');
    }
}

// Deletar recebimento
async function deletarRecebimento(id) {
    if (!confirm('Excluir este recebimento?')) return;

    try {
        const response = await fetch(API_URL + '?id=' + id, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (result.success) {
            App.showToast(result.message, 'success');
            carregarRecebimentos();
        }
    } catch (error) {
        App.showToast('Erro ao excluir', 'danger');
    }
}

// Limpar formulário
function limparForm() {
    document.getElementById('formRecebimento').reset();
    document.getElementById('recebimento_id').value = '';
    document.getElementById('modalTitulo').textContent = 'Novo Recebimento';
}

// Ao abrir modal, limpar form
document.getElementById('modalRecebimento').addEventListener('show.bs.modal', function () {
    if (!document.getElementById('recebimento_id').value) {
        limparForm();
        document.getElementById('data_recebimento').value = new Date().toISOString().split('T')[0];
    }
});

// Máscara monetária no campo valor
document.getElementById('valor').addEventListener('input', function() {
    App.maskMoney(this);
});

// Carregar ao iniciar
document.addEventListener('DOMContentLoaded', carregarRecebimentos);
