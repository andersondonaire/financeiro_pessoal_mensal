<?php
    /**
     * Gerenciamento de Despesas/Contas
     * Sistema de Divisão de Contas
     */

    require_once __DIR__ . '/includes/auth.php';

    $pageTitle = 'Gerenciar Contas';
    $db = getDB();

    // Parâmetros de ação
    $action = $_GET['action'] ?? '';
    $expenseId = (int)($_GET['id'] ?? 0);
    $monthRef = $_GET['month'] ?? getCurrentMonthRef();
    $isMonthLocked = isMonthClosed($monthRef);

    // Adicionar despesa
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
        $categoryId = (int)$_POST['category_id'];
        $description = sanitize($_POST['description']);
        $amountStr = str_replace(['.', ','], ['', '.'], $_POST['amount']);
        $amount = (float)$amountStr;
        $expenseTypeInput = strtolower(trim($_POST['expense_type'] ?? ''));
        $allowedTypes = ['unica','recorrente','parcelada'];
        $expenseType = in_array($expenseTypeInput, $allowedTypes, true) ? $expenseTypeInput : 'unica';
        $dueDate = dateToDb($_POST['due_date']);
        $isCreditCard = isset($_POST['is_credit_card']) ? 1 : 0;
        $purchaseDate = $isCreditCard ? dateToDb($_POST['purchase_date'] ?? '') : null;
        $isPaid = isset($_POST['is_paid']) ? 1 : 0;
        $paymentDate = $isPaid ? dateToDb($_POST['payment_date']) : null;
        $notes = sanitize($_POST['notes'] ?? '');
        $totalInstallments = $expenseType === 'parcelada' ? (int)($_POST['total_installments'] ?? 0) : null;
        $firstInstallment = $expenseType === 'parcelada' ? (int)($_POST['first_installment'] ?? 1) : 1;

        if (empty($description) || $amount <= 0 || empty($dueDate)) {
            setFlashMessage('Preencha todos os campos obrigatórios.', 'error');
        } elseif ($expenseType === 'parcelada' && $totalInstallments < 2) {
            setFlashMessage('Uma conta parcelada deve ter pelo menos 2 parcelas.', 'error');
        } else {
            try {
                $db->beginTransaction();
                if ($expenseType === 'parcelada') {
                    $date = new DateTime($dueDate);
                    for ($i = $firstInstallment; $i <= $totalInstallments; $i++) {
                        $installmentDate = clone $date;
                        $installmentDate->modify('+' . ($i - $firstInstallment) . ' month');
                        $installmentMonthRef = $installmentDate->format('Y-m');
                        $installmentLocked = isMonthClosed($installmentMonthRef) ? 1 : 0;
                        $installmentPaid = ($i < $firstInstallment) ? 1 : $isPaid;
                        $installmentPaymentDate = $installmentPaid ? $installmentDate->format('Y-m-d') : null;
                        $stmt = $db->prepare("INSERT INTO expenses (user_id, category_id, description, amount, expense_type, due_date, is_paid, payment_date, installment_number, total_installments, month_ref, is_locked, notes, is_credit_card, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $_SESSION['user_id'],
                            $categoryId,
                            $description . " ({$i}/{$totalInstallments})",
                            $amount,
                            $expenseType,
                            $installmentDate->format('Y-m-d'),
                            $installmentPaid,
                            $installmentPaymentDate,
                            $i,
                            $totalInstallments,
                            $installmentMonthRef,
                            $installmentLocked,
                            $notes,
                            $isCreditCard,
                            $purchaseDate
                        ]);
                    }
                } else {
                    $monthRefInsert = date('Y-m', strtotime($dueDate));
                    $locked = isMonthClosed($monthRefInsert) ? 1 : 0;
                    $stmt = $db->prepare("INSERT INTO expenses (user_id, category_id, description, amount, expense_type, due_date, is_paid, payment_date, month_ref, is_locked, notes, is_credit_card, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $categoryId,
                        $description,
                        $amount,
                        $expenseType,
                        $dueDate,
                        $isPaid,
                        $paymentDate,
                        $monthRefInsert,
                        $locked,
                        $notes,
                        $isCreditCard,
                        $purchaseDate
                    ]);
                }
                $db->commit();
                $redirectMonth = date('Y-m', strtotime($dueDate));
                setFlashMessage('Conta adicionada com sucesso!', 'success');
                redirect('expenses.php?month=' . $redirectMonth);
            } catch (Exception $e) {
                $db->rollBack();
                setFlashMessage('Erro ao adicionar conta: ' . $e->getMessage(), 'error');
            }
        }
    }

    
    // Excluir despesa
    if ($action === 'delete' && $expenseId > 0) {
        $stmt = $db->prepare("SELECT user_id, is_locked FROM expenses WHERE id = ?");
        $stmt->execute([$expenseId]);
        $exp = $stmt->fetch();
        if (!$exp || ($exp['user_id'] != $_SESSION['user_id'] && !isAdmin())) {
            setFlashMessage('Você não tem permissão para excluir esta conta.', 'error');
        } elseif ($exp['is_locked']) {
            setFlashMessage('Esta conta pertence a um mês fechado e não pode ser excluída.', 'error');
        } else {
            try {
                $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
                $stmt->execute([$expenseId]);
                setFlashMessage('Conta excluída com sucesso!', 'success');
            } catch (Exception $e) {
                setFlashMessage('Erro ao excluir conta.', 'error');
            }
        }
        redirect('expenses.php?month=' . $monthRef);
    }

    // Marcar como pago
    if ($action === 'toggle_paid' && $expenseId > 0) {
        $stmt = $db->prepare("SELECT user_id, is_locked, is_paid FROM expenses WHERE id = ?");
        $stmt->execute([$expenseId]);
        $exp = $stmt->fetch();
        if ($exp && !$exp['is_locked']) {
            $newStatus = $exp['is_paid'] ? 0 : 1;
            $paymentDate = $newStatus ? date('Y-m-d') : null;
            $stmt = $db->prepare("UPDATE expenses SET is_paid = ?, payment_date = ? WHERE id = ?");
            $stmt->execute([$newStatus, $paymentDate, $expenseId]);
            setFlashMessage($newStatus ? 'Conta marcada como paga!' : 'Conta marcada como não paga!', 'success');
        }
        redirect('expenses.php?month=' . $monthRef);
    }

// Buscar despesa para edição
$editExpense = null;
if ($action === 'edit' && $expenseId > 0) {
    $stmt = $db->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$expenseId]);
    $editExpense = $stmt->fetch();
}

// Buscar categorias e usuários
$categories = getActiveCategories();
$users = getActiveUsers();

// Auto-gerar contas recorrentes do mês anterior (se não existirem)
try {
    $previousMonth = date('Y-m', strtotime($monthRef . '-01 -1 month'));
    
    // Busca contas recorrentes do mês anterior que ainda não foram copiadas para o mês atual
    $stmt = $db->prepare("
        SELECT e.* 
        FROM expenses e
        WHERE e.month_ref = ? 
        AND e.expense_type = 'recorrente'
        AND NOT EXISTS (
            SELECT 1 FROM expenses e2 
            WHERE e2.month_ref = ? 
            AND e2.expense_type = 'recorrente'
            AND e2.category_id = e.category_id
            AND e2.description = e.description
            AND e2.user_id = e.user_id
        )
    ");
    $stmt->execute([$previousMonth, $monthRef]);
    $recurringExpenses = $stmt->fetchAll();
    
    if (count($recurringExpenses) > 0 && DEBUG_MODE) {
        error_log("Encontradas " . count($recurringExpenses) . " contas recorrentes para copiar de {$previousMonth} para {$monthRef}");
    }
    
    // Copia cada conta recorrente para o mês atual
    foreach ($recurringExpenses as $expense) {
        $dueDate = date('Y-m-d', strtotime($monthRef . '-' . date('d', strtotime($expense['due_date']))));
        $isLocked = isMonthClosed($monthRef) ? 1 : 0;
        
        $stmt = $db->prepare("
            INSERT INTO expenses 
            (user_id, category_id, description, amount, expense_type, due_date, 
             is_paid, payment_date, month_ref, is_locked, notes)
            VALUES (?, ?, ?, ?, 'recorrente', ?, 0, NULL, ?, ?, ?)
        ");
        
        $stmt->execute([
            $expense['user_id'],
            $expense['category_id'],
            $expense['description'],
            $expense['amount'], // Usa o mesmo valor do mês anterior
            $dueDate,
            $monthRef,
            $isLocked,
            $expense['notes']
        ]);
        
        if (DEBUG_MODE) {
            error_log("Conta recorrente copiada: {$expense['description']} - R$ {$expense['amount']}");
        }
    }
} catch (Exception $e) {
    if (DEBUG_MODE) {
        error_log("Erro ao copiar contas recorrentes: " . $e->getMessage());
    }
}

// Listar despesas considerando mês anterior se não estiver fechado
$previousMonth = date('Y-m', strtotime($monthRef . '-01 -1 month'));
$includePrev = !isMonthClosed($previousMonth);
$listSql = "
    SELECT e.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
           u.username, u.color as user_color,
           CASE 
               WHEN e.expense_type = 'parcelada' THEN (
                   SELECT SUM(e2.amount)
                   FROM expenses e2
                   WHERE e2.parent_expense_id = COALESCE(e.parent_expense_id, e.id)
                      OR e2.id = COALESCE(e.parent_expense_id, e.id)
               )
               ELSE e.amount
           END as total_installment_value
    FROM expenses e
    JOIN categories c ON e.category_id = c.id
    JOIN users u ON e.user_id = u.id
    WHERE " . ($includePrev ? "e.month_ref IN (?, ?)" : "e.month_ref = ?") . "
    ORDER BY e.due_date ASC, e.created_at DESC
";
$stmt = $db->prepare($listSql);
if ($includePrev) {
    $stmt->execute([$previousMonth, $monthRef]);
} else {
    $stmt->execute([$monthRef]);
}
$expenses = $stmt->fetchAll();

// Estatísticas considerando mês anterior se não estiver fechado
$statsSql = "
    SELECT 
        COUNT(*) as total_count,
        SUM(amount) as total_amount,
        SUM(CASE WHEN is_paid = 1 THEN amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN is_paid = 0 THEN amount ELSE 0 END) as pending_amount
    FROM expenses 
    WHERE " . ($includePrev ? "month_ref IN (?, ?)" : "month_ref = ?") . "
";
$stmt = $db->prepare($statsSql);
if ($includePrev) {
    $stmt->execute([$previousMonth, $monthRef]);
} else {
    $stmt->execute([$monthRef]);
}
$stats = $stmt->fetch();

// Cálculo da divisão
$activeUsersCount = count($users);
$amountPerUser = $activeUsersCount > 0 ? calculateDivision($stats['total_amount'], $activeUsersCount) : 0;

include __DIR__ . '/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-file-invoice-dollar"></i> Contas de <?php echo getMonthName($monthRef); ?></h2>
            <div>
                <?php if (!$editExpense): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expenseModal">
                    <i class="fas fa-plus"></i> Nova Conta
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h6><i class="fas fa-calculator"></i> Total do Mês</h6>
                <h3><?php echo formatMoney($stats['total_amount']); ?></h3>
                <small><?php echo $stats['total_count']; ?> conta(s)</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6><i class="fas fa-check-circle"></i> Pagas</h6>
                <h3><?php echo formatMoney($stats['paid_amount']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6><i class="fas fa-clock"></i> Pendentes</h6>
                <h3><?php echo formatMoney($stats['pending_amount']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6><i class="fas fa-users"></i> Por Pessoa</h6>
                <h3><?php echo formatMoney($amountPerUser); ?></h3>
                <small><?php echo $activeUsersCount; ?> usuário(s)</small>
            </div>
        </div>
    </div>
</div>

<!-- Filtro de mês -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="month" class="form-label">Selecionar Mês:</label>
                        <input type="month" class="form-control" id="month" name="month" 
                               value="<?php echo $monthRef; ?>" onchange="this.form.submit()">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Lista de despesas -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-list"></i> Lista de Contas
                <?php if ($isMonthLocked): ?>
                <span class="badge bg-danger float-end">Mês Fechado</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="expensesTable">
                        <thead>
                            <tr>
                                <th>Vencimento</th>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th>Valor</th>
                                <th>Tipo</th>
                                <th>Usuário</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $exp): ?>
                            <tr class="<?php echo $exp['is_paid'] ? 'table-success' : ($exp['due_date'] < date('Y-m-d') ? 'table-danger' : ''); ?>">
                                <td><?php echo formatDate($exp['due_date']); ?></td>
                                <td>
                                    <?php echo sanitize($exp['description']); ?>
                                    <?php if ($exp['expense_type'] === 'parcelada' && $exp['total_installments']): ?>
                                    <br><small class="text-primary">
                                        <i class="fas fa-calculator"></i> 
                                        Parcela <?php echo $exp['installment_number']; ?> de <?php echo $exp['total_installments']; ?> 
                                        - Total: <?php echo formatMoney($exp['total_installment_value']); ?>
                                    </small>
                                    <?php endif; ?>
                                    <?php if (!empty($exp['is_credit_card'])): ?>
                                    <br><small class="text-dark">
                                        <span class="badge bg-dark"><i class="fas fa-credit-card"></i> Cartão</span>
                                        <?php if ($exp['purchase_date']): ?> Compra: <?php echo formatDate($exp['purchase_date']); ?><?php endif; ?>
                                        (Pagamento: <?php echo formatDate($exp['due_date']); ?>)
                                    </small>
                                    <?php endif; ?>
                                    <?php if ($exp['notes']): ?>
                                    <br><small class="text-muted"><i class="fas fa-sticky-note"></i> <?php echo sanitize($exp['notes']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fas <?php echo $exp['category_icon']; ?>" 
                                       style="color: <?php echo $exp['category_color']; ?>"></i>
                                    <?php echo sanitize($exp['category_name']); ?>
                                </td>
                                <td>
                                    <strong><?php echo formatMoney($exp['amount']); ?></strong>
                                    <?php if ($exp['expense_type'] === 'parcelada' && $exp['total_installments']): ?>
                                    <br><small class="text-muted" 
                                             data-bs-toggle="tooltip" 
                                             data-bs-placement="top" 
                                             title="Valor mensal da parcela. Total parcelado: <?php echo formatMoney($exp['total_installment_value']); ?>">
                                        <i class="fas fa-info-circle"></i> Valor da parcela
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $typeLabels = [
                                        'unica' => '<span class="badge bg-secondary">Única</span>',
                                        'recorrente' => '<span class="badge bg-primary">Recorrente</span>',
                                        'parcelada' => '<span class="badge bg-info">Parcelada</span>'
                                    ];
                                    echo $typeLabels[$exp['expense_type']];
                                    ?>
                                </td>
                                <td>
                                    <span class="user-badge" style="background-color: <?php echo $exp['user_color']; ?>">
                                        <?php echo strtoupper(substr($exp['username'], 0, 2)); ?>
                                    </span>
                                    <?php echo sanitize($exp['username']); ?>
                                </td>
                                <td>
                                    <?php if ($exp['is_paid']): ?>
                                        <span class="badge bg-success">Paga</span>
                                        <?php if ($exp['payment_date']): ?>
                                        <br><small><?php echo formatDate($exp['payment_date']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$exp['is_locked']): ?>
                                        <a href="expenses.php?action=toggle_paid&id=<?php echo $exp['id']; ?>&month=<?php echo $monthRef; ?>" 
                                           class="btn btn-sm btn-<?php echo $exp['is_paid'] ? 'warning' : 'success'; ?>" 
                                           title="<?php echo $exp['is_paid'] ? 'Marcar como não paga' : 'Marcar como paga'; ?>">
                                            <i class="fas fa-<?php echo $exp['is_paid'] ? 'times' : 'check'; ?>"></i>
                                        </a>
                                        
                                        <?php if ($exp['user_id'] == $_SESSION['user_id'] || isAdmin()): ?>
                                        <a href="expenses.php?action=edit&id=<?php echo $exp['id']; ?>&month=<?php echo $exp['month_ref']; ?>" 
                                           class="btn btn-sm btn-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="expenses.php?action=delete&id=<?php echo $exp['id']; ?>" 
                                           class="btn btn-sm btn-danger" title="Excluir"
                                           onclick="return confirm('Tem certeza que deseja excluir esta conta?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Bloqueado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Nova/Editar Conta -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i>
                    <?php echo $editExpense ? 'Editar Conta' : 'Nova Conta'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="expenseForm">
                <div class="modal-body">
                    <?php if ($editExpense): ?>
                        <input type="hidden" name="expense_id" value="<?php echo $editExpense['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Categoria *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo ($editExpense && $editExpense['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="expense_type" class="form-label">Tipo de Conta *</label>
                            <select class="form-select" id="expense_type" name="expense_type" required 
                                    <?php echo $editExpense ? 'disabled' : ''; ?>>
                                <option value="unica" <?php echo ($editExpense && $editExpense['expense_type'] == 'unica') ? 'selected' : ''; ?>>Única</option>
                                <option value="recorrente" <?php echo ($editExpense && $editExpense['expense_type'] == 'recorrente') ? 'selected' : ''; ?>>Recorrente</option>
                                <option value="parcelada" <?php echo ($editExpense && $editExpense['expense_type'] == 'parcelada') ? 'selected' : ''; ?>>Parcelada</option>
                            </select>
                            <?php if ($editExpense && $editExpense['expense_type'] === 'recorrente'): ?>
                            <small class="text-info">
                                <i class="fas fa-info-circle"></i> Contas recorrentes podem ter valores diferentes a cada mês
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição *</label>
                        <input type="text" class="form-control" id="description" name="description" 
                               value="<?php echo $editExpense['description'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="amount" class="form-label">Valor *</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="amount" name="amount" 
                                       value="<?php echo $editExpense ? number_format($editExpense['amount'], 2, ',', '') : ''; ?>" 
                                       required placeholder="0,00">
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="due_date" class="form-label">Vencimento *</label>
                            <input type="text" class="form-control datepicker" id="due_date" name="due_date" 
                                   value="<?php echo $editExpense ? formatDate($editExpense['due_date']) : ''; ?>" 
                                   required placeholder="dd/mm/aaaa">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_paid" name="is_paid" 
                                       <?php echo ($editExpense && $editExpense['is_paid']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_paid">Já está paga</label>
                            </div>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="is_credit_card" name="is_credit_card" 
                                       <?php echo ($editExpense && isset($editExpense['is_credit_card']) && $editExpense['is_credit_card']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_credit_card">Compra no cartão</label>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="purchase_date_row" style="<?php echo ($editExpense && !empty($editExpense['is_credit_card'])) ? '' : 'display:none;'; ?>">
                        <div class="col-md-4 mb-3">
                            <label for="purchase_date" class="form-label">Data da Compra</label>
                            <input type="text" class="form-control datepicker" id="purchase_date" name="purchase_date" 
                                   value="<?php echo ($editExpense && $editExpense['purchase_date']) ? formatDate($editExpense['purchase_date']) : ''; ?>" 
                                   placeholder="dd/mm/aaaa">
                            <small class="text-muted">Data em que foi efetuada a compra (cartão).</small>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="payment_date_field" style="<?php echo ($editExpense && !empty($editExpense['is_paid'])) ? '' : 'display: none;'; ?>">
                        <label for="payment_date" class="form-label">Data do Pagamento</label>
                        <input type="text" class="form-control datepicker" id="payment_date" name="payment_date" 
                               value="<?php echo $editExpense && $editExpense['payment_date'] ? formatDate($editExpense['payment_date']) : ''; ?>" 
                               placeholder="dd/mm/aaaa">
                    </div>
                    
                    <!-- Campos para parceladas -->
                    <div id="installment_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="total_installments" class="form-label">Número de Parcelas *</label>
                                <input type="number" class="form-control" id="total_installments" name="total_installments" 
                                       min="2" max="120" value="12">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="first_installment" class="form-label">Primeira Parcela a Lançar *</label>
                                <input type="number" class="form-control" id="first_installment" name="first_installment" 
                                       min="1" value="1">
                                <small class="text-muted">Se já pagou parcelas anteriores, indique qual será a primeira a lançar</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Observações</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo $editExpense['notes'] ?? ''; ?></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="<?php echo $editExpense ? 'edit_expense' : 'add_expense'; ?>" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editExpense): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    try {
        var modalEl = document.getElementById('expenseModal');
        if (modalEl && window.bootstrap) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    } catch (e) {
        console.error('Erro ao exibir modal:', e);
    }
});
</script>
<?php endif; ?>

<!-- Scripts da página movidos para assets/js/main.js para garantir jQuery disponível -->

<?php include __DIR__ . '/includes/footer.php'; ?>
