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
    <title>Pagamentos - <?= SITE_NAME ?></title>
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
            <h1><?= SITE_NAME ?> - Relatório de Pagamentos</h1>
            <p>Gerado em: <?= date('d/m/Y H:i:s') ?></p>
        </div>

        <div class="row mb-3 no-print">
            <div class="col-md-6">
                <h3><i class="fas fa-arrow-up text-danger"></i> Pagamentos</h3>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#modalImportarFatura">
                    <i class="fas fa-file-import"></i> Importar Fatura
                </button>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalPagamento">
                    <i class="fas fa-plus"></i> Novo Pagamento
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabelaPagamentos" class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Compartilhado</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Pagamento -->
    <div class="modal fade" id="modalPagamento" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-arrow-up text-danger"></i> <span id="modalTitulo">Novo Pagamento</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formPagamento">
                        <input type="hidden" id="pagamento_id">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <input type="text" class="form-control" id="descricao" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="categoria_id" class="form-label">Categoria</label>
                                <select class="form-select" id="categoria_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>">
                                            <?= $cat['nome'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="valor_total" class="form-label">Valor Total (R$)</label>
                                <input type="text" class="form-control" id="valor_total" placeholder="0,00" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="data_vencimento" class="form-label">Data Vencimento</label>
                                <input type="date" class="form-control" id="data_vencimento" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="total_parcelas" class="form-label">Parcelas</label>
                                <input type="number" class="form-control" id="total_parcelas" min="1" value="1">
                                <small class="text-muted">1 = à vista</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cartao_credito">
                                    <label class="form-check-label" for="cartao_credito">
                                        <i class="fas fa-credit-card"></i> Cartão de Crédito
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="recorrente">
                                    <label class="form-check-label" for="recorrente">
                                        <i class="fas fa-redo"></i> Recorrente
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirmado">
                                    <label class="form-check-label" for="confirmado">
                                        Já pago
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="compartilhado">
                            <label class="form-check-label" for="compartilhado">
                                <i class="fas fa-users"></i> Compartilhar com outros usuários
                            </label>
                        </div>

                        <div id="divCompartilhamento" style="display: none;">
                            <label class="form-label">Selecione os usuários:</label>
                            <div id="listaUsuarios" class="mb-3"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="salvarPagamento()">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Importar Fatura -->
    <div class="modal fade" id="modalImportarFatura" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-import text-info"></i> Importar Fatura de Cartão
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Formatos Suportados:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Nubank:</strong> CSV exportado direto do app/site (date,title,amount)</li>
                            <li><strong>Itaú:</strong> CSV da fatura (Data,Descrição,Valor)</li>
                            <li><strong>Manual:</strong> Cole dados no formato Data;Descrição;Valor</li>
                        </ul>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="categoria_fatura" class="form-label">Categoria Padrão</label>
                            <select class="form-select" id="categoria_fatura">
                                <option value="">Selecione...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= $cat['nome'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="data_vencimento_fatura" class="form-label">Data de Vencimento da Fatura</label>
                            <input type="date" class="form-control" id="data_vencimento_fatura">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Escolha o método de importação:</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="metodo_importacao" id="metodo_arquivo" value="arquivo" checked>
                            <label class="btn btn-outline-primary" for="metodo_arquivo">
                                <i class="fas fa-file-upload"></i> Carregar Arquivo CSV
                            </label>
                            
                            <input type="radio" class="btn-check" name="metodo_importacao" id="metodo_manual" value="manual">
                            <label class="btn btn-outline-primary" for="metodo_manual">
                                <i class="fas fa-keyboard"></i> Colar Manualmente
                            </label>
                        </div>
                    </div>
                    
                    <div id="area_arquivo" class="mb-3">
                        <label for="arquivo_fatura" class="form-label">Selecione o arquivo CSV</label>
                        <input type="file" class="form-control" id="arquivo_fatura" accept=".csv">
                        <small class="text-muted">Arquivos suportados: Nubank (.csv), Itaú (.csv)</small>
                    </div>
                    
                    <div id="area_manual" class="mb-3" style="display: none;">
                        <label for="dados_fatura" class="form-label">Cole os dados da Fatura</label>
                        <textarea class="form-control" id="dados_fatura" rows="10" placeholder="15/11/2025;SUPERMERCADO XYZ;150,50
16/11/2025;FARMÁCIA ABC;45,30
17/11/2025;RESTAURANTE;89,90"></textarea>
                    </div>
                    
                    <div id="preview_fatura" style="display: none;">
                        <h6>Preview dos Lançamentos:</h6>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Dica:</strong> Marque os itens que deseja compartilhar. Os controles de divisão aparecerão automaticamente.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-dark">
                                <thead>
                                    <tr>
                                        <th width="30">
                                            <input type="checkbox" id="selectAllItens" title="Selecionar todos">
                                        </th>
                                        <th>Data</th>
                                        <th>Descrição</th>
                                        <th>Valor</th>
                                        <th>Compartilhar</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_preview"></tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <th colspan="4" class="text-end">TOTAL DA FATURA:</th>
                                        <th id="total_fatura">R$ 0,00</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnImportarFatura" style="display: none;" onclick="importarFatura()">
                        <i class="fas fa-save"></i> Importar Todos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net/1.13.6/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs5/1.13.6/dataTables.bootstrap5.min.js"></script>
    <script>const USUARIO_ATUAL_ID = <?= $usuario['id'] ?>;</script>
    <script src="/public/assets/js/app.js"></script>
    <script src="/public/assets/js/pagamentos.js"></script>
</body>
</html>
