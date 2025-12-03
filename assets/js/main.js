/**
 * JavaScript Principal
 * Sistema de Divisão de Contas
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicialização específica da página de despesas (se a tabela existir)
    var expensesTableEl = document.getElementById('expensesTable');
    if (expensesTableEl) {
        // DataTable para despesas
        if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
            jQuery('#expensesTable').DataTable({
                responsive: true,
                order: [[0, 'asc']],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' }
            });
        }

        // Debug: monitorar envio do formulário
        var expenseForm = document.getElementById('expenseForm');
        if (expenseForm) {
            expenseForm.addEventListener('submit', function(e) {
                console.log('Formulário sendo enviado...');
            });
        }

        // Máscara de data para inputs desta página
        document.querySelectorAll('.datepicker').forEach(function(el) {
            el.addEventListener('input', function() {
                var value = el.value.replace(/\D/g, '');
                if (value.length >= 2) value = value.slice(0,2) + '/' + value.slice(2);
                if (value.length >= 5) value = value.slice(0,5) + '/' + value.slice(5,9);
                el.value = value;
            });
        });

        // Máscara/normalização de valor (entrada livre, normaliza no blur)
        var amountEl = document.getElementById('amount');
        if (amountEl) {
            amountEl.addEventListener('blur', function() {
                var value = amountEl.value.replace(',', '.');
                if (value && !isNaN(parseFloat(value))) {
                    value = parseFloat(value).toFixed(2).replace('.', ',');
                    amountEl.value = value;
                }
            });
        }

        // Toggle campos de parcelamento e alerta informativo de recorrentes
        var expenseTypeEl = document.getElementById('expense_type');
        if (expenseTypeEl) {
            var onExpenseTypeChange = function() {
                var type = expenseTypeEl.value;

            // Remove alertas anteriores
                document.querySelectorAll('.recurring-info-alert').forEach(function(a){
                    a.parentNode.removeChild(a);
                });

                var instFields = document.getElementById('installment_fields');
                var totalInst = document.getElementById('total_installments');
                if (type === 'parcelada') {
                    if (instFields) instFields.style.display = '';
                    if (totalInst) totalInst.setAttribute('required', 'true');
                } else {
                    if (instFields) instFields.style.display = 'none';
                    if (totalInst) totalInst.removeAttribute('required');
                }

            // Mostra informação sobre contas recorrentes na criação
                var hasExpenseId = document.querySelector('#expenseForm input[name="expense_id"]') !== null;
                if (type === 'recorrente' && !hasExpenseId) {
                    var row = expenseTypeEl.closest('.row');
                    if (row) {
                        var div = document.createElement('div');
                        div.className = 'alert alert-info alert-dismissible fade show recurring-info-alert';
                        div.setAttribute('role','alert');
                        div.innerHTML = '<i class="fas fa-info-circle"></i> <strong>Contas Recorrentes:</strong> Será criada apenas no mês atual. O sistema copiará automaticamente para os próximos meses, permitindo ajustar o valor individualmente (ex: conta de água/luz que varia todo mês). <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        row.parentNode.insertBefore(div, row.nextSibling);
                    }
                }
            };
            expenseTypeEl.addEventListener('change', onExpenseTypeChange);
            onExpenseTypeChange();
        }

        // Toggle campo de data de pagamento
        var isPaidEl = document.getElementById('is_paid');
        var paymentDateField = document.getElementById('payment_date_field');
        var paymentDateInput = document.getElementById('payment_date');
        if (isPaidEl && paymentDateField && paymentDateInput) {
            var togglePaid = function() {
                if (isPaidEl.checked) {
                    paymentDateField.style.display = '';
                    paymentDateInput.setAttribute('required','true');
                    if (!paymentDateInput.value) {
                        var today = new Date();
                        var dd = String(today.getDate()).padStart(2, '0');
                        var mm = String(today.getMonth() + 1).padStart(2, '0');
                        var yyyy = today.getFullYear();
                        paymentDateInput.value = dd + '/' + mm + '/' + yyyy;
                    }
                } else {
                    paymentDateField.style.display = 'none';
                    paymentDateInput.removeAttribute('required');
                }
            };
            isPaidEl.addEventListener('change', togglePaid);
            togglePaid();
        }

        // Toggle campo Data da Compra (cartão de crédito)
        var isCardEl = document.getElementById('is_credit_card');
        var purchaseRow = document.getElementById('purchase_date_row');
        var purchaseInput = document.getElementById('purchase_date');
        if (isCardEl && purchaseRow && purchaseInput) {
            var toggleCard = function() {
                if (isCardEl.checked) {
                    purchaseRow.style.display = '';
                    if (!purchaseInput.value) {
                        var today = new Date();
                        var dd = String(today.getDate()).padStart(2, '0');
                        var mm = String(today.getMonth() + 1).padStart(2, '0');
                        var yyyy = today.getFullYear();
                        purchaseInput.value = dd + '/' + mm + '/' + yyyy;
                    }
                } else {
                    purchaseRow.style.display = 'none';
                    purchaseInput.value = '';
                }
            };
            isCardEl.addEventListener('change', toggleCard);
            toggleCard();
        }
    }
    
    // Configuração padrão do DataTables
    if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
        jQuery.extend(true, jQuery.fn.dataTable.defaults, {
            responsive: true,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' },
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }
    
    // Auto-hide alerts após 5 segundos
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(a){
            if (!a.classList.contains('alert-permanent')) {
                a.style.transition = 'opacity 0.5s';
                a.style.opacity = '0';
                setTimeout(function(){ a.style.display = 'none'; }, 500);
            }
        });
    }, 5000);
    
    // Confirmação de exclusão
    document.querySelectorAll('a[href*="delete"], button[data-action="delete"]').forEach(function(el){
        el.addEventListener('click', function(e){
            if (!el.dataset.confirmed) {
                e.preventDefault();
                if (confirm('Tem certeza que deseja excluir este item?')) {
                    el.dataset.confirmed = 'true';
                    el.click();
                }
            }
        });
    });
    
    // Máscara para valores monetários
    document.querySelectorAll('.money-input, input[name="amount"]').forEach(function(el){
        el.addEventListener('input', function(){
            var value = el.value.replace(/\D/g, '');
            if (value === '') value = '0';
            value = (parseFloat(value) / 100).toFixed(2);
            el.value = value.replace('.', ',');
        });
    });
    
    // Máscara para data (dd/mm/aaaa)
    document.querySelectorAll('.date-input, .datepicker').forEach(function(el){
        el.addEventListener('input', function(){
            var value = el.value.replace(/\D/g, '');
            if (value.length >= 2) value = value.slice(0, 2) + '/' + value.slice(2);
            if (value.length >= 5) value = value.slice(0, 5) + '/' + value.slice(5, 9);
            el.value = value;
        });
    });
    
    // Validação de data
    function isValidDate(dateString) {
        const parts = dateString.split('/');
        if (parts.length !== 3) return false;
        
        const day = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10);
        const year = parseInt(parts[2], 10);
        
        if (year < 1900 || year > 2100 || month < 1 || month > 12) return false;
        
        const daysInMonth = new Date(year, month, 0).getDate();
        return day >= 1 && day <= daysInMonth;
    }
    
    // Validação de formulários
    document.querySelectorAll('form').forEach(function(form){
        form.addEventListener('submit', function(e){
            var isValid = true;
            form.querySelectorAll('.datepicker, .date-input').forEach(function(el){
                var value = el.value;
                if (value && value.length > 0 && !isValidDate(value)) {
                    alert('Data inválida: ' + value);
                    el.focus();
                    isValid = false;
                }
            });
            form.querySelectorAll('.money-input, input[name="amount"]').forEach(function(el){
                if (!el.required) return;
                var valueStr = el.value.replace(',', '.');
                var value = parseFloat(valueStr);
                if (isNaN(value) || value <= 0) {
                    alert('Valor inválido: ' + el.value + '\nDigite um valor válido, exemplo: 150,50');
                    el.focus();
                    isValid = false;
                }
            });
            if (!isValid) e.preventDefault();
        });
    });
    
    // Tooltip do Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Popover do Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Adicionar classe de animação aos cards
    document.querySelectorAll('.card').forEach(function(el){ el.classList.add('fade-in'); });
    
    // Função para formatar moeda
    window.formatMoney = function(value) {
        return 'R$ ' + parseFloat(value).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    };
    
    // Função para calcular divisão
    window.calculateDivision = function(total, users) {
        if (users <= 0) return 0;
        return (total / users).toFixed(2);
    };
    
    // Atualização automática de valores calculados
    document.querySelectorAll('input[name="amount"], select[name="users_count"]').forEach(function(el){
        el.addEventListener('change', function(){
            var form = el.closest('form');
            if (!form) return;
            var amountInput = form.querySelector('input[name="amount"]');
            var usersSel = form.querySelector('select[name="users_count"]');
            var amount = amountInput ? parseFloat((amountInput.value || '').replace(',', '.')) || 0 : 0;
            var users = usersSel ? parseInt(usersSel.value) || 1 : 1;
            var perUser = calculateDivision(amount, users);
            var target = form.querySelector('.amount-per-user');
            if (target) target.textContent = formatMoney(perUser);
        });
    });
    
    // Confirmação antes de sair de páginas com formulários modificados
    let formModified = false;
    
    document.querySelectorAll('form input, form textarea, form select').forEach(function(el){
        el.addEventListener('change', function(){ formModified = true; });
    });
    document.querySelectorAll('form').forEach(function(form){
        form.addEventListener('submit', function(){ formModified = false; });
    });
    window.addEventListener('beforeunload', function(e){
        if (formModified) {
            var msg = 'Você tem alterações não salvas. Deseja realmente sair?';
            e.returnValue = msg;
            return msg;
        }
    });
    
    // Função para copiar texto para clipboard
    window.copyToClipboard = function(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function(){
                showToast('Copiado para a área de transferência!');
            }).catch(function(){ fallbackCopy(text); });
        } else {
            fallbackCopy(text);
        }
    };
    function fallbackCopy(text) {
        var temp = document.createElement('input');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(temp);
        showToast('Copiado para a área de transferência!');
    }
    function showToast(message) {
        var alert = document.createElement('div');
        alert.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3';
        alert.style.zIndex = '9999';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(function(){
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function(){ document.body.removeChild(alert); }, 500);
        }, 2000);
    }
    
    // Atalhos de teclado
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S para salvar formulários
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            var forms = Array.prototype.slice.call(document.querySelectorAll('form'));
            for (var i = 0; i < forms.length; i++) {
                var style = window.getComputedStyle(forms[i]);
                if (style.display !== 'none' && style.visibility !== 'hidden') {
                    forms[i].submit();
                    break;
                }
            }
        }
        
        // ESC para fechar modals
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(function(m){
                if (window.bootstrap && bootstrap.Modal) {
                    var modal = bootstrap.Modal.getInstance(m) || new bootstrap.Modal(m);
                    modal.hide();
                }
            });
        }
    });
    
    // Lazy loading de imagens (se houver)
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img.lazy').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // Debug mode
    if (window.location.search.includes('debug=1')) {
        console.log('Debug mode enabled');
        document.body.classList.add('debug-mode');
    }
    
    // Impressão
    document.querySelectorAll('.btn-print, button[data-action="print"]').forEach(function(el){
        el.addEventListener('click', function(e){ e.preventDefault(); window.print(); });
    });
    
    // Exportar para CSV (se necessário)
    window.exportTableToCSV = function(tableId, filename) {
        const table = document.getElementById(tableId);
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [];
            const cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                row.push(cols[j].innerText);
            }
            
            csv.push(row.join(','));
        }
        
        const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
        const downloadLink = document.createElement('a');
        downloadLink.download = filename || 'export.csv';
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    };
    
    console.log('Sistema de Divisão de Contas - Inicializado com sucesso!');
});

// Função para atualizar página automaticamente (opcional)
function autoRefresh(seconds) {
    setTimeout(function() {
        location.reload();
    }, seconds * 1000);
}

// Verificar se há atualizações pendentes
function checkForUpdates() {
    // Implementar se necessário
}

// Exibir modal de edição sem depender de jQuery
document.addEventListener('DOMContentLoaded', function () {
    try {
        var editIdInput = document.querySelector('#expenseForm input[name="expense_id"]');
        var modalEl = document.getElementById('expenseModal');
        if (editIdInput && modalEl && window.bootstrap && bootstrap.Modal) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    } catch (e) {
        console.error('Erro ao exibir modal (main.js):', e);
    }
});
