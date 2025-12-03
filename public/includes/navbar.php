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
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'recebimentos.php' ? 'active' : '' ?>" href="recebimentos.php">
                        <i class="fas fa-arrow-down text-success"></i> Recebimentos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pagamentos.php' ? 'active' : '' ?>" href="pagamentos.php">
                        <i class="fas fa-arrow-up text-danger"></i> Pagamentos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'ciclos.php' ? 'active' : '' ?>" href="ciclos.php">
                        <i class="fas fa-calendar-check text-info"></i> Fechamento
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <!-- Notificações -->
                <div class="dropdown me-3">
                    <button class="btn btn-dark position-relative" type="button" id="dropdownNotificacoes" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="badgeNotificacoes" style="display: none;">
                            0
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownNotificacoes" id="listaNotificacoes" style="min-width: 350px; max-height: 400px; overflow-y: auto;">
                        <li><h6 class="dropdown-header">Notificações</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="text-center p-3 text-muted" id="semNotificacoes">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p class="mb-0">Nenhuma notificação</p>
                        </li>
                    </ul>
                </div>
                
                <span class="badge rounded-pill me-2" style="background-color: <?= $usuario['cor'] ?>">
                    <?= strtoupper(substr($usuario['nome'], 0, 2)) ?>
                </span>
                <span class="text-light me-3 d-none d-md-inline"><?= $usuario['nome'] ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </div>
</nav>
