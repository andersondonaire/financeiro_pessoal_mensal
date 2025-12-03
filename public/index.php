<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Controllers/DashboardController.php';

Auth::verificarLogin();

$usuario = Auth::getUsuario();
$controller = new DashboardController();

// Período selecionado
$periodo = $_GET['periodo'] ?? 'mes_atual';

switch ($periodo) {
    case 'mes_passado':
        $data_inicio = date('Y-m-01', strtotime('-1 month'));
        $data_fim = date('Y-m-t', strtotime('-1 month'));
        $label_periodo = 'Mês Passado';
        break;
    case 'proximo_mes':
        $data_inicio = date('Y-m-01', strtotime('+1 month'));
        $data_fim = date('Y-m-t', strtotime('+1 month'));
        $label_periodo = 'Próximo Mês';
        break;
    case 'mes_atual':
    default:
        $data_inicio = date('Y-m-01');
        $data_fim = date('Y-m-t');
        $label_periodo = 'Mês Atual';
        break;
}

$dados = $controller->getDados($usuario['id'], $data_inicio, $data_fim);
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="/public/favicon.svg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/public/assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-wallet me-2"></i><?= SITE_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="recebimentos.php">
                            <i class="fas fa-arrow-down text-success"></i> Recebimentos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pagamentos.php">
                            <i class="fas fa-arrow-up text-danger"></i> Pagamentos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ciclos.php">
                            <i class="fas fa-calendar-check text-info"></i> Fechamento
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <button id="toggleVisibilidade" class="btn btn-sm btn-outline-light me-3" title="Mostrar/ocultar valores">
                        <i class="fa-solid fa-eye-slash"></i>
                    </button>
                    <span class="badge rounded-pill me-2" style="background-color: <?= $usuario['cor'] ?>">
                        <?= substr($usuario['nome'], 0, 2) ?>
                    </span>
                    <span class="text-light me-3"><?= $usuario['nome'] ?></span>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Cabeçalho para impressão -->
        <div class="print-header">
            <h1><?= SITE_NAME ?> - Relatório Financeiro</h1>
            <p><?= $label_periodo ?> - <?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></p>
            <p>Gerado em: <?= date('d/m/Y H:i:s') ?></p>
        </div>

        <!-- Filtro de Período -->
        <div class="row mb-3 no-print">
            <div class="col-md-4">
                <select id="periodo" class="form-select">
                    <option value="mes_passado" <?= $periodo === 'mes_passado' ? 'selected' : '' ?>>Mês Passado</option>
                    <option value="mes_atual" <?= $periodo === 'mes_atual' ? 'selected' : '' ?>>Mês Atual</option>
                    <option value="proximo_mes" <?= $periodo === 'proximo_mes' ? 'selected' : '' ?>>Próximo Mês</option>
                </select>
            </div>
            <div class="col-md-8 text-end">
                <?php if ($periodo === 'proximo_mes'): ?>
                    <button class="btn btn-success" onclick="gerarRecorrencias()">
                        <i class="fas fa-sync-alt"></i> Gerar Recorrências do Próximo Mês
                    </button>
                <?php endif; ?>
                <h5 class="mt-2 d-inline-block ms-3"><?= $label_periodo ?> - <?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></h5>
            </div>
        </div>

        <!-- Cards de Resumo -->
        <div class="row g-3 mb-4">
            <?php if ($periodo === 'proximo_mes' && $dados['saldo']['anterior'] != 0): ?>
            <!-- Saldo do Mês Anterior (apenas no próximo mês) -->
            <div class="col-md-2">
                <div class="card saldo-card border-light">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted small">
                            <i class="fas fa-history me-1"></i>Saldo Anterior
                        </h6>
                        <h4 class="mb-0 <?= $dados['saldo']['anterior'] >= 0 ? 'text-success' : 'text-danger' ?>"><span class="valor-sigiloso">
                            <?= number_format($dados['saldo']['anterior'], 2, ',', '.') ?>
                        </span></h4>
                        <small class="text-muted" style="font-size: 0.7rem;">Mês passado</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Recebimentos Confirmados -->
            <div class="col-md-2">
                <div class="card saldo-card border-success">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted small">
                            <i class="fas fa-check-circle me-1"></i>Recebidos
                        </h6>
                        <h4 class="mb-0 text-success"><span class="valor-sigiloso">
                            <?= number_format($dados['recebimentos']['confirmados'], 2, ',', '.') ?>
                        </span></h4>
                        <a href="recebimentos.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>

            <!-- Pagamentos Confirmados -->
            <div class="col-md-2">
                <div class="card saldo-card border-danger">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted small">
                            <i class="fas fa-check-circle me-1"></i>Pagos
                        </h6>
                        <h4 class="mb-0 text-danger"><span class="valor-sigiloso">
                            <?= number_format($dados['pagamentos']['confirmados'], 2, ',', '.') ?>
                        </span></h4>
                        <a href="pagamentos.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>

            <!-- Falta Receber -->
            <div class="col-md-2">
                <div class="card saldo-card border-info">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted small">
                            <i class="fas fa-clock me-1"></i>Falta Receber
                        </h6>
                        <h4 class="mb-0 text-info"><span class="valor-sigiloso">
                            <?= number_format($dados['recebimentos']['pendentes'], 2, ',', '.') ?>
                        </span></h4>
                        <a href="recebimentos.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>

            <!-- Falta Pagar -->
            <div class="col-md-2">
                <div class="card saldo-card border-warning">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted small">
                            <i class="fas fa-clock me-1"></i>Falta Pagar
                        </h6>
                        <h4 class="mb-0 text-warning"><span class="valor-sigiloso">
                            <?= number_format($dados['pagamentos']['pendentes'], 2, ',', '.') ?>
                        </span></h4>
                        <a href="pagamentos.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>

            <!-- Saldo Atual -->
            <div class="col-md-2">
                <div class="card saldo-card border-primary">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted small">
                            <i class="fas fa-wallet me-1"></i>Saldo Atual
                        </h6>
                        <h4 class="mb-0 <?= $dados['saldo']['confirmado'] >= 0 ? 'text-success' : 'text-danger' ?>" id="saldoCalculado"><span class="valor-sigiloso">
                            <?= number_format($dados['saldo']['confirmado'], 2, ',', '.') ?>
                        </span></h4>
                    </div>
                </div>
            </div>

            <!-- Saldo Projetado -->
            <div class="col-md-2">
                <div class="card saldo-card border-secondary">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted small">
                            <i class="fas fa-chart-line me-1"></i>Projetado
                        </h6>
                        <h4 class="mb-0 <?= $dados['saldo']['projetado'] >= 0 ? 'text-success' : 'text-danger' ?>"><span class="valor-sigiloso">
                            <?= number_format($dados['saldo']['projetado'], 2, ',', '.') ?>
                        </span></h4>
                        <small class="text-muted" style="font-size: 0.7rem;">Se tudo pago</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conciliação Bancária -->
        <div class="row mb-4 no-print">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <i class="fas fa-search-dollar me-2"></i>Conciliação Bancária
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label for="saldoReal" class="form-label">
                                    <i class="fas fa-wallet me-1"></i>Saldo Real da Conta
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="text" class="form-control valor-sigiloso" id="saldoReal" placeholder="0,00" onkeyup="formatarMoeda(this)">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100" onclick="conciliar()">
                                    <i class="fas fa-calculator me-2"></i>Conciliar
                                </button>
                            </div>
                            <div class="col-md-6" id="resultadoConciliacao" style="display: none;">
                                <div class="alert mb-0" id="alertaConciliacao">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong id="textoDiferenca"></strong>
                                            <p class="mb-0 small" id="textoExplicacao"></p>
                                        </div>
                                        <div class="text-end">
                                            <h4 class="mb-0" id="valorDiferenca"></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="fas fa-info-circle me-1"></i>
                            Compare o saldo calculado pelo sistema com o saldo real da sua conta. 
                            Se houver diferença, pode indicar lançamentos não registrados.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Categorias -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2"></i>Pagamentos por Categoria
                    </div>
                    <div class="card-body">
                        <canvas id="categoriasChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Lista de Categorias -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list me-2"></i>Detalhamento por Categoria
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-dark">
                                <thead>
                                    <tr>
                                        <th>Categoria</th>
                                        <th class="text-end">Valor</th>
                                        <th class="text-end">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_geral = array_sum(array_column($dados['categorias'], 'total'));
                                    foreach ($dados['categorias'] as $cat): 
                                        $percentual = $total_geral > 0 ? ($cat['total'] / $total_geral) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <i class="fas <?= $cat['icone'] ?> me-2" style="color: <?= $cat['cor'] ?>"></i>
                                            <?= $cat['nome'] ?>
                                        </td>
                                        <td class="text-end valor-sigiloso">R$ <?= number_format($cat['total'], 2, ',', '.') ?></td>
                                        <td class="text-end"><?= number_format($percentual, 1) ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script src="/public/assets/js/app.js"></script>
    <script>
        // Controle de visibilidade de valores
        const btnToggle = document.getElementById('toggleVisibilidade');
        const classeMascara = 'valor-mascarado';

        function aplicarVisibilidade() {
            const estado = localStorage.getItem('visibilidade_saldos') || 'oculto';
            const elementos = document.querySelectorAll('.valor-sigiloso');
            elementos.forEach(el => {
                if (estado === 'oculto') {
                    el.classList.add(classeMascara);
                } else {
                    el.classList.remove(classeMascara);
                }
            });
            
            if (btnToggle) {
                const icone = btnToggle.querySelector('i');
                if (estado === 'oculto') {
                    icone.classList.remove('fa-eye');
                    icone.classList.add('fa-eye-slash');
                } else {
                    icone.classList.remove('fa-eye-slash');
                    icone.classList.add('fa-eye');
                }
            }
        }

        // Aplicar imediatamente ao carregar
        aplicarVisibilidade();

        // Adicionar evento de clique quando o botão estiver disponível
        document.addEventListener('DOMContentLoaded', function() {
            if (btnToggle) {
                btnToggle.addEventListener('click', () => {
                    const atual = localStorage.getItem('visibilidade_saldos') || 'oculto';
                    localStorage.setItem('visibilidade_saldos', atual === 'oculto' ? 'visivel' : 'oculto');
                    aplicarVisibilidade();
                });
            }
        });

        // Saldo calculado pelo sistema
        const SALDO_CALCULADO = <?= $dados['saldo']['confirmado'] ?>;

        // Gráfico de categorias
        const ctx = document.getElementById('categoriasChart').getContext('2d');
        const categorias = <?= json_encode($dados['categorias']) ?>;
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categorias.map(c => c.nome),
                datasets: [{
                    data: categorias.map(c => c.total),
                    backgroundColor: categorias.map(c => c.cor),
                    borderWidth: 2,
                    borderColor: '#161b22'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#e6edf3' }
                    }
                }
            }
        });

        // Formatar campo de moeda
        function formatarMoeda(campo) {
            let valor = campo.value.replace(/\D/g, '');
            valor = (parseInt(valor) || 0).toString();
            
            if (valor.length === 0) {
                campo.value = '';
                return;
            }
            
            // Preencher com zeros à esquerda se necessário
            while (valor.length < 3) {
                valor = '0' + valor;
            }
            
            const inteiros = valor.slice(0, -2);
            const decimais = valor.slice(-2);
            
            // Adicionar separador de milhares
            const inteiroFormatado = inteiros.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            
            campo.value = inteiroFormatado + ',' + decimais;
        }

        // Conciliação Bancária
        function conciliar() {
            const inputSaldoReal = document.getElementById('saldoReal');
            const valorTexto = inputSaldoReal.value.replace(/\./g, '').replace(',', '.');
            const saldoReal = parseFloat(valorTexto);

            if (isNaN(saldoReal)) {
                App.showToast('Informe um valor válido', 'warning');
                return;
            }

            const diferenca = saldoReal - SALDO_CALCULADO;
            const diferencaAbs = Math.abs(diferenca);

            // Salvar no localStorage
            localStorage.setItem('ultimo_saldo_real', saldoReal);
            localStorage.setItem('ultima_conciliacao', new Date().toISOString());

            // Mostrar resultado
            const resultado = document.getElementById('resultadoConciliacao');
            const alerta = document.getElementById('alertaConciliacao');
            const textoDiferenca = document.getElementById('textoDiferenca');
            const textoExplicacao = document.getElementById('textoExplicacao');
            const valorDiferenca = document.getElementById('valorDiferenca');

            resultado.style.display = 'block';

            if (diferencaAbs < 0.01) {
                // Saldos batem
                alerta.className = 'alert alert-success mb-0';
                textoDiferenca.innerHTML = '<i class="fas fa-check-circle me-2"></i>Contas conferem!';
                textoExplicacao.textContent = 'O saldo real está de acordo com o sistema.';
                valorDiferenca.textContent = 'R$ 0,00';
            } else if (diferenca > 0) {
                // Saldo real maior = falta lançar recebimentos
                alerta.className = 'alert alert-warning mb-0';
                textoDiferenca.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Diferença detectada';
                textoExplicacao.textContent = 'Você tem R$ ' + diferencaAbs.toFixed(2).replace('.', ',') + ' a mais na conta. Pode ter recebimentos não lançados.';
                valorDiferenca.innerHTML = '<span class="text-success">+ R$ ' + diferencaAbs.toFixed(2).replace('.', ',') + '</span>';
            } else {
                // Saldo real menor = falta lançar pagamentos
                alerta.className = 'alert alert-danger mb-0';
                textoDiferenca.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Diferença detectada';
                textoExplicacao.textContent = 'Você tem R$ ' + diferencaAbs.toFixed(2).replace('.', ',') + ' a menos na conta. Pode ter pagamentos não lançados.';
                valorDiferenca.innerHTML = '<span class="text-danger">- R$ ' + diferencaAbs.toFixed(2).replace('.', ',') + '</span>';
            }
        }

        // Restaurar último saldo informado
        document.addEventListener('DOMContentLoaded', function() {
            const ultimoSaldo = localStorage.getItem('ultimo_saldo_real');
            if (ultimoSaldo) {
                const campo = document.getElementById('saldoReal');
                const valor = parseFloat(ultimoSaldo).toFixed(2).replace('.', ',');
                campo.value = valor;
                formatarMoeda(campo);
            }
        });

        // Permitir conciliar ao pressionar Enter
        document.getElementById('saldoReal').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                conciliar();
            }
        });

        // Mudança de período
        document.getElementById('periodo').addEventListener('change', function() {
            window.location.href = '?periodo=' + this.value;
        });

        // Gerar recorrências para próximo mês
        async function gerarRecorrencias() {
            if (!confirm('Gerar lançamentos recorrentes para o próximo mês?')) return;

            try {
                const response = await fetch('/api/gerar_recorrencias.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                });

                const result = await response.json();

                if (result.success) {
                    App.showToast(result.message, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    App.showToast(result.message || 'Erro ao gerar recorrências', 'danger');
                }
            } catch (error) {
                App.showToast('Erro ao processar requisição', 'danger');
            }
        }
    </script>
</body>
</html>
