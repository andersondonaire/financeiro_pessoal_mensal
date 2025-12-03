/**
 * Pagamentos - CRUD com Compartilhamento e Parcelamento
 */

let tabela;
let usuarios = [];
const API_URL = window.location.origin + '/api/pagamentos.php';

// Carregar usuários da API
async function carregarUsuarios() {
    try {
        const resp = await fetch(API_URL + '?action=usuarios');
        const json = await resp.json();
        if (json.success) {
            usuarios = json.data || [];
        }
    } catch (e) {
        App.showToast('Falha ao carregar usuários', 'danger');
    }
}

// Carregar pagamentos e preencher tabela
async function carregarPagamentos() {
    try {
        const resp = await fetch(API_URL);
        const json = await resp.json();
        if (json.success) {
            const dados = json.data || [];
            const tbody = document.querySelector('#tabelaPagamentos tbody');
            if (tabela) { jQuery('#tabelaPagamentos').DataTable().destroy(); }
            tbody.innerHTML = '';
            dados.forEach(pag => {
                let valorExibir = pag.valor_total; // Por padrão, mostra o total
                let valorTotal = pag.valor_total;
                let percentualUsuario = 100;
                let isCompartilhadoComUsuario = false;
                let eUsuarioCriador = pag.usuario_criador_id == USUARIO_ATUAL_ID;
                let valorUsuario = pag.valor_total;
                let valorTotalItensCompartilhados = 0; // Total dos itens compartilhados com o usuário
                
                if (pag.compartilhado == 1) {
                    try {
                        const percentuais = JSON.parse(pag.percentuais_divisao || '{}');
                        const perc = percentuais[USUARIO_ATUAL_ID] ?? 0;
                        percentualUsuario = perc;
                        if (perc > 0) {
                            // Para fatura de cartão, o percentual é sobre o total, mas representa apenas itens compartilhados
                            // Calcular o total dos itens compartilhados (reverso do percentual)
                            if (pag.cartao_credito == 1 && !eUsuarioCriador) {
                                // O percentual representa a proporção dos itens compartilhados no total
                                // Se Patrícia tem 5,28% de uma fatura de 4250, significa que os itens compartilhados somam:
                                // valorUsuario = 224,43 (sua parte)
                                // valorTotalItensCompartilhados = 224,43 / 50% = 448,86
                                valorUsuario = (pag.valor_total * perc) / 100;
                                valorTotalItensCompartilhados = valorUsuario * 2; // Assumindo divisão 50/50
                                valorExibir = valorTotalItensCompartilhados;
                            } else {
                                valorUsuario = (pag.valor_total * perc) / 100;
                                isCompartilhadoComUsuario = true;
                                
                                // Se NÃO for o criador e não for cartão, mostra só a parte dele
                                if (!eUsuarioCriador) {
                                    valorExibir = valorUsuario * 2; // Total que foi compartilhado
                                }
                            }
                            isCompartilhadoComUsuario = true;
                        }
                    } catch (e) {}
                }
                
                const usuariosComp = pag.compartilhado == 1 ? (JSON.parse(pag.usuarios_compartilhados || '[]')||[]) : [];
                const badgeCompartilhado = pag.compartilhado == 1 ? `<span class="badge bg-primary ms-2"><i class="fas fa-users"></i> ${usuariosComp.length} usuário${usuariosComp.length > 1 ? 's' : ''}</span>` : '';
                
                // Verificar se o pagamento está em um ciclo fechado
                const emCicloFechado = pag.pagamento_ciclo_id != null;

                // Ocultar pagamento compartilhado já acertado para não-criador
                if (pag.compartilhado == 1 && !eUsuarioCriador && emCicloFechado) {
                    return; // pula renderização deste pagamento para o usuário não criador
                }
                
                // Mostrar valor total e valor individual quando for compartilhado
                let infoValor = '';
                if (pag.compartilhado == 1 && isCompartilhadoComUsuario) {
                    const valorFormatado = Number(valorUsuario).toFixed(2).replace('.', ',');
                    const totalFormatado = Number(valorTotal).toFixed(2).replace('.', ',');
                    
                    if (emCicloFechado) {
                        // Se já foi fechado, mostrar info de ciclo fechado
                        infoValor = `<br><small class="text-success"><i class="fas fa-check-circle"></i> Ciclo fechado</small>`;
                    } else if (eUsuarioCriador) {
                        infoValor = `<br><small class="text-success">(Aguarda receber R$ ${valorFormatado} no fechamento)</small>`;
                    } else {
                        // Para não-criador, mostrar valor que ele deve (sua parte dos itens compartilhados)
                        infoValor = `<br><small class="text-muted">(Sua parte: R$ ${valorFormatado} - Será acertado no ciclo)</small>`;
                    }
                }
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${App.formatDate(pag.data_vencimento)}</td>
                    <td>
                        ${pag.descricao}
                        ${pag.cartao_credito == 1 ? '<i class="fas fa-credit-card text-warning ms-2" title="Cartão"></i>' : ''}
                        ${badgeCompartilhado}
                    </td>
                    <td>
                        <i class="fas ${pag.categoria_icone}" style="color: ${pag.categoria_cor}"></i>
                        ${pag.categoria_nome}
                    </td>
                    <td class="text-danger fw-bold">
                        R$ ${Number(valorExibir).toFixed(2).replace('.', ',')}
                        ${infoValor}
                    </td>
                    <td>${pag.compartilhado == 1 ? '<span class="badge bg-primary">Sim</span>' : '<span class="badge bg-secondary">Não</span>'}</td>
                    <td>
                        ${eUsuarioCriador ? 
                            (pag.confirmado == 1 ? 
                                '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Pago</span>' 
                                : `<button class="btn btn-sm btn-outline-warning" onclick="confirmarPagamento(${pag.id})" title="Confirmar pagamento"><i class="fas fa-clock"></i> A Pagar</button>`)
                            : (pag.confirmado == 1 ? 
                                '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Pago</span>' 
                                : (emCicloFechado ? 
                                    `<button class="btn btn-sm btn-outline-warning" onclick="confirmarPagamento(${pag.id})" title="Confirmar pagamento"><i class="fas fa-clock"></i> A Pagar</button>` 
                                    : '<span class="badge bg-info"><i class="fas fa-hourglass-half"></i> Aguardando ciclo</span>'))
                        }
                    </td>
                    <td>
                        ${eUsuarioCriador ? `
                            <button class="btn btn-sm btn-primary" onclick="editarPagamento(${pag.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="deletarPagamento(${pag.id})"><i class="fas fa-trash"></i></button>
                            ${pag.cartao_credito == 1 ? `<button class="btn btn-sm btn-info" onclick="verDetalhesFatura(${pag.id})" title="Ver itens da fatura"><i class="fas fa-list"></i></button>` : ''}
                        ` : `
                            <button class="btn btn-sm btn-info" onclick="verDetalhes(${pag.id})" title="Ver detalhes"><i class="fas fa-eye"></i></button>
                        `}
                    </td>
                `;
                tbody.appendChild(tr);
            });
            tabela = jQuery('#tabelaPagamentos').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' },
                pageLength: 25,
                order: [[0, 'desc']],
                scrollX: true
            });
        }
    } catch (e) {
        App.showToast('Falha ao carregar pagamentos', 'danger');
    }
}

// Inicialização
async function init() {
    await carregarUsuarios();
    await carregarPagamentos();
}

// Salvar pagamento (escopo global)
async function salvarPagamento() {
    const id = document.getElementById('pagamento_id').value;
    const compartilhado = document.getElementById('compartilhado').checked;

    const dados = {
        descricao: document.getElementById('descricao').value,
        categoria_id: document.getElementById('categoria_id').value,
        valor_total: App.unmaskMoney(document.getElementById('valor_total').value),
        data_vencimento: document.getElementById('data_vencimento').value,
        total_parcelas: document.getElementById('total_parcelas').value,
        cartao_credito: document.getElementById('cartao_credito').checked ? 1 : 0,
        recorrente: document.getElementById('recorrente').checked ? 1 : 0,
        confirmado: document.getElementById('confirmado').checked ? 1 : 0,
        compartilhado: compartilhado ? 1 : 0
    };

    if (compartilhado) {
        const checkboxes = document.querySelectorAll('.usuario-checkbox:checked');
        const usuariosSelecionados = Array.from(checkboxes).map(cb => parseInt(cb.value));
        if (usuariosSelecionados.length === 0) {
            App.showToast('Selecione ao menos um usuário para compartilhar', 'warning');
            return;
        }
        dados.usuarios = usuariosSelecionados;
    }

    try {
        const metodo = id ? 'PUT' : 'POST';
        if (id) dados.id = id;
        const response = await fetch(API_URL, {
            method: metodo,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });
        const result = await response.json();
        if (!response.ok) {
            App.showToast(result.message || 'Erro ao salvar pagamento', 'danger');
            return;
        }
        if (result.success) {
            App.showToast(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalPagamento')).hide();
            carregarPagamentos();
            limparForm();
        } else {
            App.showToast(result.message || 'Erro ao salvar', 'danger');
        }
    } catch (error) {
        App.showToast('Erro de rede ao salvar', 'danger');
    }
}

// Editar pagamento
async function editarPagamento(id) {
    try {
        const response = await fetch(API_URL);
        const result = await response.json();

        if (result.success) {
            const pagamento = result.data.find(p => p.id == id);

            if (pagamento) {
                document.getElementById('pagamento_id').value = pagamento.id;
                document.getElementById('descricao').value = pagamento.descricao;
                document.getElementById('categoria_id').value = pagamento.categoria_id;
                document.getElementById('valor_total').value = parseFloat(pagamento.valor_total).toFixed(2).replace('.', ',');
                document.getElementById('data_vencimento').value = pagamento.data_vencimento;
                document.getElementById('total_parcelas').value = pagamento.total_parcelas || 1;
                document.getElementById('cartao_credito').checked = pagamento.cartao_credito == 1;
                document.getElementById('recorrente').checked = pagamento.recorrente == 1;
                document.getElementById('confirmado').checked = pagamento.confirmado == 1;
                document.getElementById('compartilhado').checked = pagamento.compartilhado == 1;
                
                if (pagamento.compartilhado == 1) {
                    toggleCompartilhamento();
                    
                    // Marcar usuários compartilhados
                    try {
                        const usuariosComp = JSON.parse(pagamento.usuarios_compartilhados || '[]');
                        document.querySelectorAll('.usuario-checkbox').forEach(cb => {
                            cb.checked = usuariosComp.includes(parseInt(cb.value));
                        });
                    } catch (e) {}
                }
                
                document.getElementById('modalTitulo').textContent = 'Editar Pagamento';
                new bootstrap.Modal(document.getElementById('modalPagamento')).show();
            }
        }
    } catch (error) {
        App.showToast('Erro ao carregar dados', 'danger');
    }
}

// Confirmar pagamento
async function confirmarPagamento(id) {
    try {
        const response = await fetch(API_URL, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        const result = await response.json();

        if (result.success) {
            App.showToast(result.message, 'success');
            carregarPagamentos();
        }
    } catch (error) {
        App.showToast('Erro ao confirmar', 'danger');
    }
}

// Deletar pagamento
async function deletarPagamento(id) {
    if (!confirm('Excluir este pagamento?')) return;

    try {
        const response = await fetch(API_URL + '?id=' + id, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (result.success) {
            App.showToast(result.message, 'success');
            carregarPagamentos();
        }
    } catch (error) {
        App.showToast('Erro ao excluir', 'danger');
    }
}

// Ver detalhes do pagamento compartilhado
async function verDetalhes(id) {
    try {
        const response = await fetch(API_URL);
        const result = await response.json();

        if (result.success) {
            const pagamento = result.data.find(p => p.id == id);

            if (pagamento) {
                let detalhes = `
                    <strong>Descrição:</strong> ${pagamento.descricao}<br>
                    <strong>Categoria:</strong> ${pagamento.categoria_nome}<br>
                    <strong>Valor Total:</strong> R$ ${parseFloat(pagamento.valor_total).toFixed(2).replace('.', ',')}<br>
                    <strong>Data Vencimento:</strong> ${App.formatDate(pagamento.data_vencimento)}<br>
                    <strong>Status:</strong> ${pagamento.confirmado == 1 ? 'Pago' : 'Pendente'}<br><br>
                `;
                
                if (pagamento.compartilhado == 1) {
                    try {
                        const percentuais = JSON.parse(pagamento.percentuais_divisao || '{}');
                        const usuariosComp = JSON.parse(pagamento.usuarios_compartilhados || '[]');
                        
                        detalhes += '<strong>Divisão:</strong><br>';
                        for (const uid of usuariosComp) {
                            const usuario = usuarios.find(u => u.id == uid);
                            const perc = percentuais[uid] || 0;
                            const valor = (pagamento.valor_total * perc) / 100;
                            const nome = usuario ? usuario.nome : `Usuário ${uid}`;
                            detalhes += `• ${nome}: ${perc}% = R$ ${valor.toFixed(2).replace('.', ',')}<br>`;
                        }
                    } catch (e) {}
                }
                
                App.showToast(detalhes, 'info', 8000);
            }
        }
    } catch (error) {
        App.showToast('Erro ao carregar dados', 'danger');
    }
}

// Toggle compartilhamento
function toggleCompartilhamento() {
    const compartilhado = document.getElementById('compartilhado').checked;
    const div = document.getElementById('divCompartilhamento');
    
    if (compartilhado) {
        div.style.display = 'block';
        renderizarUsuarios();
    } else {
        div.style.display = 'none';
    }
}

// Renderizar lista de usuários
function renderizarUsuarios() {
    const container = document.getElementById('listaUsuarios');
    container.innerHTML = '';
    
    usuarios.forEach(user => {
        if (user.id != USUARIO_ATUAL_ID) {
            const div = document.createElement('div');
            div.className = 'form-check';
            div.innerHTML = `
                <input class="form-check-input usuario-checkbox" type="checkbox" value="${user.id}" id="user_${user.id}">
                <label class="form-check-label" for="user_${user.id}">
                    <span class="badge rounded-pill" style="background-color: ${user.cor}">${user.nome.substring(0, 2).toUpperCase()}</span>
                    ${user.nome}
                </label>
            `;
            container.appendChild(div);
        }
    });
}

// Limpar formulário
function limparForm() {
    document.getElementById('formPagamento').reset();
    document.getElementById('pagamento_id').value = '';
    document.getElementById('modalTitulo').textContent = 'Novo Pagamento';
    document.getElementById('divCompartilhamento').style.display = 'none';
    document.getElementById('total_parcelas').value = 1;
}

// Event listeners
document.getElementById('compartilhado').addEventListener('change', toggleCompartilhamento);

document.getElementById('modalPagamento').addEventListener('show.bs.modal', function () {
    if (!document.getElementById('pagamento_id').value) {
        limparForm();
        document.getElementById('data_vencimento').value = new Date().toISOString().split('T')[0];
    }
});

// ====================================
// IMPORTAÇÃO DE FATURA DE CARTÃO
// ====================================

let lancamentosImportar = [];

// Alternar entre arquivo e manual
document.querySelectorAll('input[name="metodo_importacao"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const areaArquivo = document.getElementById('area_arquivo');
        const areaManual = document.getElementById('area_manual');
        
        if (this.value === 'arquivo') {
            areaArquivo.style.display = 'block';
            areaManual.style.display = 'none';
        } else {
            areaArquivo.style.display = 'none';
            areaManual.style.display = 'block';
        }
    });
});

// Processar arquivo quando selecionado
document.getElementById('arquivo_fatura').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(event) {
        const conteudo = event.target.result;
        processarCSV(conteudo);
    };
    reader.readAsText(file);
});

// Processar automaticamente quando colar no campo manual
document.getElementById('dados_fatura').addEventListener('input', function() {
    const dados = this.value.trim();
    if (dados) {
        clearTimeout(this.processarTimeout);
        this.processarTimeout = setTimeout(() => {
            processarCSV(dados);
        }, 500); // Aguarda 500ms após parar de digitar
    }
});

// Processar automaticamente quando colar no campo manual
document.getElementById('dados_fatura').addEventListener('input', function() {
    const dados = this.value.trim();
    if (dados) {
        clearTimeout(this.processarTimeout);
        this.processarTimeout = setTimeout(() => {
            processarCSV(dados);
        }, 500); // Aguarda 500ms após parar de digitar
    }
});

function detectarFormato(linhas) {
    if (linhas.length < 2) return null;
    
    const header = linhas[0].toLowerCase();
    
    // Nubank: date,title,amount
    if (header.includes('date') && header.includes('title') && header.includes('amount')) {
        return 'nubank';
    }
    
    // Itaú: geralmente tem "data" ou começa direto com datas
    if (header.includes('data') || header.match(/^\d{2}\/\d{2}/)) {
        return 'itau';
    }
    
    return 'manual';
}

function parseNubank(linhas) {
    const lancamentos = [];
    
    // Pular header (primeira linha)
    for (let i = 1; i < linhas.length; i++) {
        const linha = linhas[i].trim();
        if (!linha) continue;
        
        // Nubank: date,title,amount
        const partes = linha.split(',');
        if (partes.length < 3) continue;
        
        const data = partes[0].trim(); // 2025-11-30
        const descricao = partes[1].trim().replace(/^"|"$/g, ''); // Remove aspas se houver
        const valorTexto = partes[2].trim();
        const valor = Math.abs(parseFloat(valorTexto));
        
        // Ignorar pagamentos recebidos (valores negativos no Nubank)
        if (parseFloat(valorTexto) < 0) continue;
        
        // Ignorar IOF (será somado ao valor principal se necessário)
        if (descricao.toLowerCase().includes('iof')) continue;
        
        if (isNaN(valor) || valor <= 0) continue;
        
        lancamentos.push({
            data: data,
            descricao: descricao.toUpperCase(),
            valor: valor
        });
    }
    
    return lancamentos;
}

function parseItau(linhas) {
    const lancamentos = [];
    
    // Itaú geralmente: Data,Descrição,Valor ou Data;Descrição;Valor
    const primeiraLinha = linhas[0].toLowerCase();
    const temHeader = primeiraLinha.includes('data') || primeiraLinha.includes('descri');
    const startIndex = temHeader ? 1 : 0;
    
    for (let i = startIndex; i < linhas.length; i++) {
        const linha = linhas[i].trim();
        if (!linha) continue;
        
        // Tentar vírgula primeiro, depois ponto e vírgula
        let separador = ',';
        if (linha.split(';').length > linha.split(',').length) {
            separador = ';';
        }
        
        const partes = linha.split(separador);
        if (partes.length < 3) continue;
        
        const data = partes[0].trim(); // DD/MM/YYYY
        const descricao = partes[1].trim().replace(/^"|"$/g, '');
        const valorTexto = partes[2].trim().replace(/[^\d,.-]/g, '');
        const valor = Math.abs(parseFloat(valorTexto.replace('.', '').replace(',', '.')));
        
        if (isNaN(valor) || valor <= 0) continue;
        
        lancamentos.push({
            data: data,
            descricao: descricao.toUpperCase(),
            valor: valor
        });
    }
    
    return lancamentos;
}

function parseManual(linhas) {
    const lancamentos = [];
    
    for (let i = 0; i < linhas.length; i++) {
        const linha = linhas[i].trim();
        if (!linha) continue;
        
        const partes = linha.split(';');
        if (partes.length < 3) continue;
        
        const data = partes[0].trim();
        const descricao = partes[1].trim();
        const valorTexto = partes[2].trim().replace('.', '').replace(',', '.');
        const valor = parseFloat(valorTexto);
        
        if (isNaN(valor) || valor <= 0) continue;
        
        lancamentos.push({
            data: data,
            descricao: descricao.toUpperCase(),
            valor: valor
        });
    }
    
    return lancamentos;
}

function processarCSV(conteudo) {
    const linhas = conteudo.split('\n');
    const formato = detectarFormato(linhas);
    
    let lancamentos = [];
    
    switch (formato) {
        case 'nubank':
            lancamentos = parseNubank(linhas);
            App.showToast('Formato Nubank detectado!', 'info');
            break;
        case 'itau':
            lancamentos = parseItau(linhas);
            App.showToast('Formato Itaú detectado!', 'info');
            break;
        default:
            lancamentos = parseManual(linhas);
            App.showToast('Formato manual detectado', 'info');
    }
    
    if (lancamentos.length > 0) {
        exibirPreview(lancamentos);
    } else {
        App.showToast('Nenhum lançamento válido encontrado', 'danger');
    }
}

function exibirPreview(lancamentos) {
    const categoria_id = document.getElementById('categoria_fatura').value;
    const data_vencimento = document.getElementById('data_vencimento_fatura').value;
    
    if (!categoria_id) {
        App.showToast('Selecione uma categoria padrão primeiro', 'warning');
        return;
    }
    
    if (!data_vencimento) {
        App.showToast('Informe a data de vencimento da fatura', 'warning');
        return;
    }
    
    lancamentosImportar = lancamentos.map((lanc, index) => ({
        id: index,
        descricao: lanc.descricao,
        valor: lanc.valor,
        data_compra: lanc.data,
        categoria_id: categoria_id,
        data_vencimento: data_vencimento,
        compartilhado: false,
        usuarios_compartilhados: null,
        percentuais_divisao: null
    }));
    
    const tbody = document.getElementById('tbody_preview');
    tbody.innerHTML = '';
    
    let totalGeral = 0;
    
    lancamentos.forEach((lanc, index) => {
        totalGeral += lanc.valor;
        
        tbody.innerHTML += `
        <tr id="row_${index}">
            <td>
                <input type="checkbox" class="item-checkbox" data-index="${index}">
            </td>
            <td>${lanc.data}</td>
            <td>${lanc.descricao}</td>
            <td>R$ ${lanc.valor.toFixed(2).replace('.', ',')}</td>
            <td>
                <span class="badge bg-secondary" id="badge_comp_${index}">Não compartilhado</span>
            </td>
        </tr>
        <tr id="row_comp_${index}" style="display: none;">
            <td colspan="5">
                <div id="compartilhamento_${index}" class="p-2 bg-dark border border-secondary rounded"></div>
            </td>
        </tr>`;
    });
    
    document.getElementById('total_fatura').textContent = 'R$ ' + totalGeral.toFixed(2).replace('.', ',');
    
    // Event listener para checkboxes
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const index = this.dataset.index;
            const badge = document.getElementById(`badge_comp_${index}`);
            const rowComp = document.getElementById(`row_comp_${index}`);
            const divComp = document.getElementById(`compartilhamento_${index}`);
            
            if (this.checked) {
                badge.style.display = 'none';
                rowComp.style.display = 'table-row';
                exibirCompartilhamentoInline(index);
            } else {
                badge.style.display = 'inline-block';
                badge.className = 'badge bg-secondary';
                badge.textContent = 'Não compartilhado';
                rowComp.style.display = 'none';
                // Resetar compartilhamento
                lancamentosImportar[index].compartilhado = false;
                lancamentosImportar[index].usuarios_compartilhados = null;
                lancamentosImportar[index].percentuais_divisao = null;
            }
        });
    });
    
    // Select all
    document.getElementById('selectAllItens').addEventListener('change', function() {
        document.querySelectorAll('.item-checkbox').forEach(cb => {
            cb.checked = this.checked;
            cb.dispatchEvent(new Event('change'));
        });
    });
    
    document.getElementById('preview_fatura').style.display = 'block';
    document.getElementById('btnImportarFatura').style.display = 'inline-block';
}

function exibirCompartilhamentoInline(index) {
    const item = lancamentosImportar[index];
    const divComp = document.getElementById(`compartilhamento_${index}`);
    const badge = document.getElementById(`badge_comp_${index}`);
    
    // Inicializar se não existe
    if (!item.usuarios_compartilhados) {
        item.usuarios_compartilhados = usuarios.map(u => u.id);
        item.percentuais_divisao = {};
        
        // Divisão inicial igual
        const numUsuarios = usuarios.length;
        const percentualBase = Math.floor(100 / numUsuarios);
        const resto = 100 - (percentualBase * numUsuarios);
        
        usuarios.forEach((u, idx) => {
            item.percentuais_divisao[u.id] = idx === 0 ? percentualBase + resto : percentualBase;
        });
    }
    
    // Criar controles inline
    let htmlUsuarios = '<div class="mt-2">';
    usuarios.forEach(u => {
        const checked = item.usuarios_compartilhados.includes(u.id);
        const perc = item.percentuais_divisao[u.id] || 0;
        
        htmlUsuarios += `
            <div class="d-inline-block me-3 mb-2">
                <label class="d-flex align-items-center" style="font-size: 0.9rem;">
                    <input type="checkbox" class="form-check-input me-2 user-check-inline" 
                           data-index="${index}" data-user-id="${u.id}" ${checked ? 'checked' : ''}
                           onchange="atualizarCompartilhamentoInline(${index})">
                    <span class="badge rounded-pill me-1" style="background-color: ${u.cor}">${u.nome.substring(0,2).toUpperCase()}</span>
                    <span class="me-1">${u.nome}</span>
                    <input type="number" class="form-control form-control-sm user-perc-inline" 
                           data-index="${index}" data-user-id="${u.id}" 
                           value="${perc}" min="0" max="100" style="width: 60px;" ${checked ? '' : 'disabled'}
                           onchange="atualizarPercentualInline(${index}, ${u.id}, this.value)">
                    <span class="ms-1">%</span>
                </label>
            </div>
        `;
    });
    htmlUsuarios += '</div>';
    
    divComp.innerHTML = htmlUsuarios;
    
    // Atualizar badge
    const numCompartilhados = item.usuarios_compartilhados.length;
    badge.className = 'badge bg-success';
    badge.textContent = `Compartilhado (${numCompartilhados})`;
    badge.style.display = 'inline-block';
    
    item.compartilhado = true;
}

function atualizarCompartilhamentoInline(index) {
    const checkboxes = document.querySelectorAll(`.user-check-inline[data-index="${index}"]:checked`);
    const usuarios_ids = Array.from(checkboxes).map(cb => parseInt(cb.dataset.userId));
    
    if (usuarios_ids.length === 0) {
        lancamentosImportar[index].compartilhado = false;
        lancamentosImportar[index].usuarios_compartilhados = [];
        
        const badge = document.getElementById(`badge_comp_${index}`);
        badge.className = 'badge bg-secondary';
        badge.textContent = 'Nenhum usuário selecionado';
        return;
    }
    
    lancamentosImportar[index].compartilhado = true;
    lancamentosImportar[index].usuarios_compartilhados = usuarios_ids;
    
    // Recalcular percentuais
    const numUsuarios = usuarios_ids.length;
    const percentualBase = Math.floor(100 / numUsuarios);
    const resto = 100 - (percentualBase * numUsuarios);
    
    let primeiro = true;
    checkboxes.forEach(cb => {
        const uid = parseInt(cb.dataset.userId);
        const perc = primeiro ? percentualBase + resto : percentualBase;
        lancamentosImportar[index].percentuais_divisao[uid] = perc;
        
        const input = document.querySelector(`.user-perc-inline[data-index="${index}"][data-user-id="${uid}"]`);
        if (input) {
            input.value = perc;
            input.disabled = false;
        }
        
        primeiro = false;
    });
    
    // Desabilitar e zerar percentuais dos não marcados
    document.querySelectorAll(`.user-check-inline[data-index="${index}"]:not(:checked)`).forEach(cb => {
        const uid = parseInt(cb.dataset.userId);
        lancamentosImportar[index].percentuais_divisao[uid] = 0;
        const input = document.querySelector(`.user-perc-inline[data-index="${index}"][data-user-id="${uid}"]`);
        if (input) {
            input.value = 0;
            input.disabled = true;
        }
    });
    
    // Atualizar badge
    const badge = document.getElementById(`badge_comp_${index}`);
    badge.className = 'badge bg-success';
    badge.textContent = `Compartilhado (${usuarios_ids.length})`;
}

function atualizarPercentualInline(index, userId, valor) {
    if (!lancamentosImportar[index].percentuais_divisao) {
        lancamentosImportar[index].percentuais_divisao = {};
    }
    lancamentosImportar[index].percentuais_divisao[userId] = parseFloat(valor) || 0;
}

async function configurarCompartilhamento(index) {
    const item = lancamentosImportar[index];
    
    // Buscar usuários
    if (usuarios.length === 0) {
        await carregarUsuarios();
    }
    
    // Criar modal dinâmico
    const modalHtml = `
        <div class="modal fade" id="modalCompartilharItem" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-users"></i> Compartilhar: ${item.descricao}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Valor:</strong> R$ ${item.valor.toFixed(2).replace('.', ',')}</p>
                        <div id="listaUsuariosItem"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="salvarCompartilhamentoItem(${index})">Salvar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior se existir
    const modalAntigo = document.getElementById('modalCompartilharItem');
    if (modalAntigo) modalAntigo.remove();
    
    // Adicionar novo modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Renderizar lista de usuários
    const lista = document.getElementById('listaUsuariosItem');
    
    // Se o item ainda não tem configuração de compartilhamento, marcar todos por padrão
    const jaConfigurado = item.usuarios_compartilhados && item.usuarios_compartilhados.length > 0;
    
    usuarios.forEach(u => {
        const checked = jaConfigurado ? item.usuarios_compartilhados.includes(u.id) : true;
        const percentual = item.percentuais_divisao ? item.percentuais_divisao[u.id] || 0 : 0;
        
        lista.innerHTML += `
            <div class="card mb-2">
                <div class="card-body p-2">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <input type="checkbox" class="usuario-checkbox-item" data-usuario-id="${u.id}" ${checked ? 'checked' : ''}>
                        </div>
                        <div class="col">
                            <span class="badge rounded-pill" style="background-color: ${u.cor}">${u.nome.substring(0,2).toUpperCase()}</span>
                            ${u.nome}
                        </div>
                        <div class="col-auto">
                            <div class="input-group input-group-sm" style="width: 100px;">
                                <input type="number" class="form-control percentual-item" data-usuario-id="${u.id}" 
                                       value="${percentual}" min="0" max="100" placeholder="%" ${checked ? '' : 'disabled'}>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    // Event listeners
    document.querySelectorAll('.usuario-checkbox-item').forEach(cb => {
        cb.addEventListener('change', function() {
            recalcularPercentuaisItem();
        });
    });
    
    // Função para recalcular percentuais automaticamente
    function recalcularPercentuaisItem() {
        const checkboxesMarcados = document.querySelectorAll('.usuario-checkbox-item:checked');
        const totalParticipantes = checkboxesMarcados.length;
        
        if (totalParticipantes === 0) {
            // Desabilitar todos os inputs
            document.querySelectorAll('.percentual-item').forEach(input => {
                input.disabled = true;
                input.value = 0;
            });
            return;
        }
        
        // Dividir igualmente entre participantes
        const percentualPorPessoa = Math.floor(100 / totalParticipantes);
        const resto = 100 - (percentualPorPessoa * totalParticipantes);
        
        let primeiro = true;
        checkboxesMarcados.forEach(cb => {
            const input = document.querySelector(`.percentual-item[data-usuario-id="${cb.dataset.usuarioId}"]`);
            input.disabled = false;
            // Primeiro participante recebe o percentual base + resto (para garantir 100%)
            input.value = primeiro ? percentualPorPessoa + resto : percentualPorPessoa;
            primeiro = false;
        });
        
        // Desabilitar inputs não marcados
        document.querySelectorAll('.usuario-checkbox-item:not(:checked)').forEach(cb => {
            const input = document.querySelector(`.percentual-item[data-usuario-id="${cb.dataset.usuarioId}"]`);
            input.disabled = true;
            input.value = 0;
        });
    }
    
    // Calcular percentuais iniciais
    recalcularPercentuaisItem();
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalCompartilharItem'));
    modal.show();
}

function salvarCompartilhamentoItem(index) {
    const checkboxes = document.querySelectorAll('.usuario-checkbox-item:checked');
    
    if (checkboxes.length === 0) {
        App.showToast('Selecione pelo menos um usuário', 'warning');
        return;
    }
    
    const usuarios_ids = [];
    const percentuais = {};
    let totalPerc = 0;
    
    checkboxes.forEach(cb => {
        const uid = parseInt(cb.dataset.usuarioId);
        const perc = parseFloat(document.querySelector(`.percentual-item[data-usuario-id="${uid}"]`).value) || 0;
        usuarios_ids.push(uid);
        percentuais[uid] = perc;
        totalPerc += perc;
    });
    
    if (Math.abs(totalPerc - 100) > 0.01) {
        App.showToast('Os percentuais devem somar 100%', 'warning');
        return;
    }
    
    // Salvar no item
    lancamentosImportar[index].compartilhado = true;
    lancamentosImportar[index].usuarios_compartilhados = usuarios_ids;
    lancamentosImportar[index].percentuais_divisao = percentuais;
    
    // Atualizar badge
    const badge = document.getElementById(`badge_comp_${index}`);
    badge.className = 'badge bg-success';
    badge.textContent = `Compartilhado (${usuarios_ids.length} usuários)`;
    
    // Fechar modal
    bootstrap.Modal.getInstance(document.getElementById('modalCompartilharItem')).hide();
    App.showToast('Compartilhamento configurado!', 'success');
}



async function importarFatura() {
    if (lancamentosImportar.length === 0) {
        App.showToast('Nenhum lançamento para importar', 'warning');
        return;
    }
    
    const categoria_id = document.getElementById('categoria_fatura').value;
    const data_vencimento = document.getElementById('data_vencimento_fatura').value;
    
    // Calcular total da fatura
    const valorTotal = lancamentosImportar.reduce((sum, item) => sum + item.valor, 0);
    
    // Verificar se há itens compartilhados
    const temItensCompartilhados = lancamentosImportar.some(item => item.compartilhado);
    
    // Coletar todos os usuários envolvidos nos itens compartilhados
    const todosUsuarios = new Set();
    const valoresPorUsuario = {};
    
    if (temItensCompartilhados) {
        lancamentosImportar.forEach(item => {
            if (item.compartilhado && item.usuarios_compartilhados) {
                item.usuarios_compartilhados.forEach(uid => {
                    todosUsuarios.add(uid);
                    if (!valoresPorUsuario[uid]) {
                        valoresPorUsuario[uid] = 0;
                    }
                    // Calcular valor que este usuário deve pagar neste item
                    const percentualItem = item.percentuais_divisao[uid] || 0;
                    const valorUsuarioItem = (item.valor * percentualItem) / 100;
                    valoresPorUsuario[uid] += valorUsuarioItem;
                });
            }
        });
    }
    
    // Enviar valores absolutos e percentual médio
    const percentuaisPorUsuario = {};
    Object.keys(valoresPorUsuario).forEach(uid => {
        // Calcular percentual que representa do total da fatura
        const percentualTotal = (valoresPorUsuario[uid] / valorTotal) * 100;
        percentuaisPorUsuario[uid] = Math.round(percentualTotal * 100) / 100;
    });
    
    if (!confirm(`Importar fatura com ${lancamentosImportar.length} ite(ns) no valor total de R$ ${valorTotal.toFixed(2).replace('.', ',')}?`)) {
        return;
    }
    
    try {
        // 1. Criar o pagamento principal (fatura total)
        const descricaoFatura = `FATURA CARTÃO - ${new Date(data_vencimento).toLocaleDateString('pt-BR')}`;
        
        const dadosPagamento = {
            descricao: descricaoFatura,
            categoria_id: categoria_id,
            valor_total: valorTotal,
            data_vencimento: data_vencimento,
            cartao_credito: 1,
            recorrente: 0,
            compartilhado: temItensCompartilhados ? 1 : 0,
            confirmado: 0,
            total_parcelas: 1
        };
        
        // Se tem itens compartilhados, adicionar usuários e percentuais
        if (temItensCompartilhados) {
            dadosPagamento.usuarios = Array.from(todosUsuarios);
            dadosPagamento.percentuais = percentuaisPorUsuario;
        }
        
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dadosPagamento)
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Erro ao criar pagamento principal');
        }
        
        const pagamento_id = result.data.id;
        
        // 2. Salvar os itens detalhados
        const responseItens = await fetch('/api/pagamentos_itens.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pagamento_id: pagamento_id,
                itens: lancamentosImportar.map(item => ({
                    data_compra: item.data_compra,
                    descricao: item.descricao,
                    valor: item.valor,
                    compartilhado: item.compartilhado ? 1 : 0,
                    usuarios_compartilhados: item.usuarios_compartilhados || null,
                    percentuais_divisao: item.percentuais_divisao || null
                }))
            })
        });
        
        const resultItensText = await responseItens.text();
        let resultItens;
        
        try {
            resultItens = JSON.parse(resultItensText);
        } catch (e) {
            console.error('Resposta da API não é JSON:', resultItensText.substring(0, 500));
            throw new Error('Erro ao salvar itens: resposta inválida do servidor');
        }
        
        if (!resultItens.success) {
            throw new Error(resultItens.message || 'Erro ao salvar itens da fatura');
        }
        
        App.showToast(`Fatura importada com sucesso! ${lancamentosImportar.length} itens salvos.`, 'success', 5000);
        
        
        bootstrap.Modal.getInstance(document.getElementById('modalImportarFatura')).hide();
        carregarPagamentos();
        
        // Limpar
        document.getElementById('dados_fatura').value = '';
        document.getElementById('arquivo_fatura').value = '';
        document.getElementById('preview_fatura').style.display = 'none';
        document.getElementById('btnImportarFatura').style.display = 'none';
        lancamentosImportar = [];
        
    } catch (error) {
        console.error('Erro:', error);
        App.showToast(error.message || 'Erro ao importar fatura', 'danger');
    }
}

// Ver detalhes da fatura
async function verDetalhesFatura(pagamento_id) {
    try {
        const response = await fetch(`/api/pagamentos_itens.php?pagamento_id=${pagamento_id}`);
        const resultado = await response.json();
        
        if (!resultado.success) {
            App.showToast(resultado.message || 'Erro ao carregar itens', 'danger');
            return;
        }
        
        const itens = resultado.data;
        
        if (itens.length === 0) {
            App.showToast('Nenhum item detalhado encontrado', 'info');
            return;
        }
        
        // Buscar informações do pagamento
        exibirModalDetalhes(pagamento_id, itens, false);
        
    } catch (error) {
        console.error('Erro:', error);
        App.showToast('Erro ao carregar detalhes da fatura', 'danger');
    }
}

// Ver detalhes para usuário não-criador
async function verDetalhes(pagamento_id) {
    try {
        // Buscar informações do pagamento
        const respPagamento = await fetch(API_URL);
        const jsonPagamento = await respPagamento.json();
        
        if (!jsonPagamento.success) {
            App.showToast('Erro ao carregar pagamento', 'danger');
            return;
        }
        
        const pagamento = jsonPagamento.data.find(p => p.id == pagamento_id);
        
        if (!pagamento) {
            App.showToast('Pagamento não encontrado', 'danger');
            return;
        }
        
        // Se for fatura de cartão, buscar itens
        if (pagamento.cartao_credito == 1) {
            const response = await fetch(`/api/pagamentos_itens.php?pagamento_id=${pagamento_id}`);
            const resultado = await response.json();
            
            if (resultado.success && resultado.data.length > 0) {
                exibirModalDetalhes(pagamento_id, resultado.data, true, pagamento);
                return;
            }
        }
        
        // Se não for fatura ou não tiver itens, mostrar detalhes simples
        exibirDetalhesSimples(pagamento);
        
    } catch (error) {
        console.error('Erro:', error);
        App.showToast('Erro ao carregar detalhes', 'danger');
    }
}

// Exibir modal de detalhes da fatura
function exibirModalDetalhes(pagamento_id, itens, mostrarDono = false, pagamento = null) {
    // Calcular totais antes de criar o modal
    let totalItensCompartilhadosComUsuario = 0;
    let totalUsuarioPagar = 0;
    
    if (mostrarDono) {
        itens.forEach(item => {
            if (item.compartilhado == 1 && item.usuarios_compartilhados) {
                const usuariosArray = Array.isArray(item.usuarios_compartilhados) 
                    ? item.usuarios_compartilhados 
                    : JSON.parse(item.usuarios_compartilhados || '[]');
                
                if (usuariosArray.includes(USUARIO_ATUAL_ID)) {
                    totalItensCompartilhadosComUsuario += parseFloat(item.valor);
                    
                    if (item.percentuais_divisao) {
                        const percentuaisObj = typeof item.percentuais_divisao === 'object' 
                            ? item.percentuais_divisao 
                            : JSON.parse(item.percentuais_divisao || '{}');
                        const perc = percentuaisObj[USUARIO_ATUAL_ID] || 0;
                        totalUsuarioPagar += (parseFloat(item.valor) * perc) / 100;
                    }
                }
            }
        });
    }
    
    // Buscar informações do criador
    let infoCriador = '';
    if (mostrarDono && pagamento) {
        const criador = usuarios.find(u => u.id == pagamento.usuario_criador_id);
        const nomeCriador = criador ? criador.nome : 'Usuário';
        infoCriador = `
            <div class="alert alert-info mb-3">
                <i class="fas fa-credit-card"></i> <strong>Cartão de:</strong> ${nomeCriador}
                <br>
                <i class="fas fa-calendar"></i> <strong>Vencimento:</strong> ${App.formatDate(pagamento.data_vencimento)}
                <br>
                <i class="fas fa-dollar-sign"></i> <strong>Total das suas compras:</strong> R$ ${totalItensCompartilhadosComUsuario.toFixed(2).replace('.', ',')}
                <br>
                <i class="fas fa-receipt"></i> <strong>Você deve pagar:</strong> R$ ${totalUsuarioPagar.toFixed(2).replace('.', ',')}
                <br>
                <i class="fas fa-info-circle"></i> Este valor será acertado no fechamento do ciclo
            </div>
        `;
    }
    
    // Criar modal
    const modalHtml = `
        <div class="modal fade" id="modalDetalhesFatura" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-list text-info"></i> Detalhes da Fatura
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${infoCriador}
                        <div class="table-responsive">
                            <table class="table table-sm table-dark">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Descrição</th>
                                        <th>Valor</th>
                                        <th>Compartilhamento</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_detalhes_fatura"></tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <th colspan="2" class="text-end">TOTAL:</th>
                                        <th id="total_detalhes"></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior
    const modalAntigo = document.getElementById('modalDetalhesFatura');
    if (modalAntigo) modalAntigo.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Preencher tabela
    const tbody = document.getElementById('tbody_detalhes_fatura');
    let totalGeral = 0;
    let totalUsuario = 0;
    let totalItensCompartilhados = 0;
    
    itens.forEach(item => {
        totalGeral += parseFloat(item.valor);
        
        let badgeComp = '<span class="badge bg-secondary">Não compartilhado</span>';
        let valorUsuarioItem = 0;
        let itemCompartilhadoComUsuario = false;
        
        if (item.compartilhado == 1 && item.usuarios_compartilhados) {
            const usuariosArray = Array.isArray(item.usuarios_compartilhados) 
                ? item.usuarios_compartilhados 
                : JSON.parse(item.usuarios_compartilhados || '[]');
            
            itemCompartilhadoComUsuario = usuariosArray.includes(USUARIO_ATUAL_ID);
            
            if (itemCompartilhadoComUsuario) {
                const numUsuarios = usuariosArray.length;
                badgeComp = `<span class="badge bg-success"><i class="fas fa-users"></i> ${numUsuarios} usuário(s)</span>`;
                
                totalItensCompartilhados += parseFloat(item.valor);
                
                // Calcular valor do usuário atual neste item
                if (mostrarDono && item.percentuais_divisao) {
                    const percentuaisObj = typeof item.percentuais_divisao === 'object' 
                        ? item.percentuais_divisao 
                        : JSON.parse(item.percentuais_divisao || '{}');
                    const perc = percentuaisObj[USUARIO_ATUAL_ID] || 0;
                    valorUsuarioItem = (parseFloat(item.valor) * perc) / 100;
                    badgeComp += ` <small>(Você: ${perc.toFixed(0)}%)</small>`;
                }
            } else if (mostrarDono) {
                badgeComp = '<span class="badge bg-secondary">Não compartilhado com você</span>';
            }
        }
        
        totalUsuario += valorUsuarioItem;
        
        // Se for modo de visualização (não criador), mostrar apenas itens compartilhados com ele
        if (mostrarDono && !itemCompartilhadoComUsuario && item.compartilhado == 1) {
            return; // Pular este item
        }
        
        tbody.innerHTML += `
            <tr>
                <td>${new Date(item.data_compra).toLocaleDateString('pt-BR')}</td>
                <td>${item.descricao}</td>
                <td>R$ ${parseFloat(item.valor).toFixed(2).replace('.', ',')}</td>
                <td>${badgeComp}</td>
            </tr>
        `;
    });
    
    // Definir qual total mostrar
    let textoTotal;
    if (mostrarDono) {
        // Para não-criador, mostrar apenas total dos itens compartilhados com ele
        textoTotal = 'R$ ' + totalItensCompartilhados.toFixed(2).replace('.', ',');
        if (totalUsuario !== totalItensCompartilhados) {
            textoTotal += `<br><small class="text-warning">Sua parte: R$ ${totalUsuario.toFixed(2).replace('.', ',')}</small>`;
        }
    } else {
        // Para criador, mostrar total geral
        textoTotal = 'R$ ' + totalGeral.toFixed(2).replace('.', ',');
    }
    
    document.getElementById('total_detalhes').innerHTML = textoTotal;
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalDetalhesFatura'));
    modal.show();
}

// Detalhes simples para pagamentos sem itens
function exibirDetalhesSimples(pagamento) {
    const criador = usuarios.find(u => u.id == pagamento.usuario_criador_id);
    const nomeCriador = criador ? criador.nome : 'Usuário';
    
    let percentualUsuario = 0;
    let valorUsuario = 0;
    
    if (pagamento.compartilhado == 1 && pagamento.percentuais_divisao) {
        try {
            const percentuais = JSON.parse(pagamento.percentuais_divisao);
            percentualUsuario = percentuais[USUARIO_ATUAL_ID] || 0;
            valorUsuario = (pagamento.valor_total * percentualUsuario) / 100;
        } catch (e) {}
    }
    
    const modalHtml = `
        <div class="modal fade" id="modalDetalhesSimples" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-info-circle text-info"></i> Detalhes do Pagamento
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <p><strong><i class="fas fa-user"></i> Pago por:</strong> ${nomeCriador}</p>
                            <p><strong><i class="fas fa-file-alt"></i> Descrição:</strong> ${pagamento.descricao}</p>
                            <p><strong><i class="fas fa-calendar"></i> Vencimento:</strong> ${App.formatDate(pagamento.data_vencimento)}</p>
                            ${percentualUsuario > 0 ? `
                                <hr>
                                <p class="text-warning mb-0"><strong><i class="fas fa-receipt"></i> Total: ${(valorUsuario*2).toFixed(2).replace('.', ',')} |  Sua parte (50%):</strong> R$ ${valorUsuario.toFixed(2).replace('.', ',')}</p>
                                <small class="text-muted">Este valor será acertado no fechamento do ciclo</small>
                            ` : ''}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const modalAntigo = document.getElementById('modalDetalhesSimples');
    if (modalAntigo) modalAntigo.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('modalDetalhesSimples'));
    modal.show();
}


// Máscara monetária no campo valor
document.getElementById('valor_total').addEventListener('input', function() {
    App.maskMoney(this);
});

// Inicializar
document.addEventListener('DOMContentLoaded', init);

