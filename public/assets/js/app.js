/**
 * App.js - Gerenciador Financeiro
 * Fetch API + Interações sem confirmações desnecessárias
 */

// Configuração global
const API_BASE = window.location.origin + '/api';

// Utilitários
const App = {
    // Formatar moeda
    formatMoney(valor) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    },

    // Formatar data
    formatDate(data) {
        return new Date(data).toLocaleDateString('pt-BR');
    },

    // Aplicar máscara monetária (ex: 8990 -> 89,90)
    maskMoney(input) {
        let value = input.value.replace(/\D/g, '');
        if (value === '') {
            input.value = '';
            return;
        }
        value = (parseInt(value) / 100).toFixed(2);
        input.value = value.replace('.', ',');
    },

    // Converter valor formatado para float (ex: 89,90 -> 89.90)
    unmaskMoney(value) {
        if (!value) return 0;
        return parseFloat(value.replace(',', '.'));
    },

    // Mostrar toast/alerta
    showToast(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        toast.style.zIndex = '9999';
        toast.style.maxWidth = '600px';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, duration);
    },

    // Alias em português
    mostrarToast(message, type = 'success', duration = 3000) {
        return this.showToast(message, type, duration);
    },

    // Fetch com tratamento de erro
    async fetch(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Erro na requisição');
            }

            return data;
        } catch (error) {
            this.showToast(error.message, 'danger');
            throw error;
        }
    }
};

// Inicialização do DataTables (se jQuery disponível)
document.addEventListener('DOMContentLoaded', function() {
    // DataTables
    if (window.jQuery && jQuery.fn.DataTable) {
        jQuery('.datatable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
            },
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']],
            scrollX: true
        });
    }

    // Auto-submit em mudanças de período
    const periodoSelect = document.getElementById('periodo');
    if (periodoSelect) {
        periodoSelect.addEventListener('change', function() {
            window.location.href = `?periodo=${this.value}`;
        });
    }

    // Carregar notificações
    carregarNotificacoes();
    
    // Atualizar notificações a cada 2 minutos
    setInterval(carregarNotificacoes, 120000);
});

// Sistema de Notificações
async function carregarNotificacoes() {
    try {
        const response = await fetch('/api/notificacoes.php');
        const resultado = await response.json();
        
        if (resultado.success) {
            const notificacoes = resultado.data;
            const total = resultado.total;
            
            const badge = document.getElementById('badgeNotificacoes');
            const lista = document.getElementById('listaNotificacoes');
            const semNotif = document.getElementById('semNotificacoes');
            
            if (total > 0) {
                badge.textContent = total;
                badge.style.display = 'inline-block';
                semNotif.style.display = 'none';
                
                // Limpar lista (manter header)
                const items = lista.querySelectorAll('li:not(.dropdown-header):not(:has(hr)):not(#semNotificacoes)');
                items.forEach(item => item.remove());
                
                // Adicionar notificações
                notificacoes.forEach(notif => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <a class="dropdown-item" href="${notif.link}">
                            <div class="d-flex align-items-start">
                                <i class="fas ${notif.icone} text-${notif.cor} me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <strong class="d-block">${notif.titulo}</strong>
                                    <small class="text-muted">${notif.mensagem}</small>
                                </div>
                                ${notif.badge ? `<span class="badge bg-${notif.cor} ms-2">${notif.badge}</span>` : ''}
                            </div>
                        </a>
                    `;
                    lista.appendChild(li);
                });
                
            } else {
                badge.style.display = 'none';
                semNotif.style.display = 'block';
            }
        }
    } catch (error) {
        console.error('Erro ao carregar notificações:', error);
    }
}

// Exportar para uso global
window.App = App;
