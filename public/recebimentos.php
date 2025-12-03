<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Models/Categoria.php';

Auth::verificarLogin();
$usuario = Auth::getUsuario();
$categoriaModel = new Categoria();
$categorias = $categoriaModel->buscarTodas();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recebimentos - <?= SITE_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="/public/favicon.svg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.6/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/style.css">
    <link rel="stylesheet" href="/public/assets/css/responsive.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <!-- Cabeçalho para impressão -->
        <div class="print-header">
            <h1><?= SITE_NAME ?> - Relatório de Recebimentos</h1>
            <p>Gerado em: <?= date('d/m/Y H:i:s') ?></p>
        </div>

        <div class="row mb-3 no-print">
            <div class="col-md-6">
                <h3><i class="fas fa-arrow-down text-success"></i> Recebimentos</h3>
            </div>
            <div class="col-md-6 text-end">
 
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalRecebimento">
                    <i class="fas fa-plus"></i> Novo Recebimento
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabelaRecebimentos" class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Recebimento -->
    <div class="modal fade" id="modalRecebimento" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-arrow-down text-success"></i> <span id="modalTitulo">Novo Recebimento</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formRecebimento">
                        <input type="hidden" id="recebimento_id">
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <input type="text" class="form-control" id="descricao" required>
                        </div>

                        <div class="mb-3">
                            <label for="categoria_id" class="form-label">Categoria</label>
                            <select class="form-select" id="categoria_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= $cat['nome'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="valor" class="form-label">Valor (R$)</label>
                                <input type="text" class="form-control" id="valor" placeholder="0,00" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="data_recebimento" class="form-label">Data</label>
                                <input type="date" class="form-control" id="data_recebimento" required>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="recorrente">
                            <label class="form-check-label" for="recorrente">
                                Recorrente (mensal)
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmado">
                            <label class="form-check-label" for="confirmado">
                                Já recebido
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="salvarRecebimento()">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net/1.13.6/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.6/dataTables.bootstrap5.min.js"></script>
    <script src="/public/assets/js/app.js"></script>
    <script src="/public/assets/js/recebimentos.js"></script>
</body>
</html>
