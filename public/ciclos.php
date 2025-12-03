<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';

Auth::verificarLogin();
$usuario = Auth::getUsuario();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fechamento de Ciclos - <?= SITE_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="/public/favicon.svg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/style.css">
    <link rel="stylesheet" href="/public/assets/css/responsive.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <!-- Cabeçalho para impressão -->
        <div class="print-header">
            <h1><?= SITE_NAME ?> - Ciclos Fechados</h1>
            <p>Gerado em: <?= date('d/m/Y H:i:s') ?></p>
        </div>

        <div class="row mb-3 no-print">
            <div class="col-md-6">
                <h3><i class="fas fa-calendar-check text-info"></i> Fechamento de Ciclos</h3>
                <p class="text-muted">Acerte as contas compartilhadas entre os usuários</p>
            </div>
            <div class="col-md-6 text-end">

            </div>
        </div>

        <div class="card mb-4 no-print">
            <div class="card-header">
                <h5><i class="fas fa-calculator"></i> Calcular Acertos</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="data_inicio" class="form-label">Data Início</label>
                        <input type="date" class="form-control" id="data_inicio" value="<?= date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="data_fim" class="form-label">Data Fim</label>
                        <input type="date" class="form-control" id="data_fim" value="<?= date('Y-m-t') ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="descricao_ciclo" class="form-label">Descrição</label>
                        <input type="text" class="form-control" id="descricao_ciclo" placeholder="Ex: Dezembro 2025">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary w-100" onclick="calcularAcertos()">
                            <i class="fas fa-calculator"></i> Calcular
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="resultadoCalculo" style="display: none;">
            <!-- Saldos por Usuário -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-users"></i> Saldos por Usuário</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Total Pago</th>
                                    <th>Total Deve</th>
                                    <th>Saldo</th>
                                </tr>
                            </thead>
                            <tbody id="tabelaSaldos"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Acertos -->
            <div class="card mb-4">
                <div class="card-header bg-success">
                    <h5><i class="fas fa-exchange-alt"></i> Acertos a Realizar</h5>
                </div>
                <div class="card-body">
                    <div id="listaAcertos"></div>
                </div>
            </div>

            <!-- Pagamentos Incluídos -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Pagamentos Compartilhados do Período</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-dark">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Criador</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody id="tabelaPagamentos"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="text-end mb-4">
                <button class="btn btn-success btn-lg" onclick="fecharCiclo()">
                    <i class="fas fa-check-circle"></i> Fechar Ciclo e Registrar
                </button>
            </div>
        </div>

        <!-- Ciclos Anteriores -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Ciclos Fechados</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Descrição</th>
                                <th>Período</th>
                                <th>Data Fechamento</th>
                                <th>Pagamentos</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaCiclos"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="/public/assets/js/app.js"></script>
    <script src="/public/assets/js/ciclos.js"></script>
</body>
</html>
