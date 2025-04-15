<!-- Sidebar / Menu -->
<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <h2>DEXUS</h2>
            <p>Sistema de Gestão</p>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="/">
                    <i class="fas fa-home me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'clientes' ? 'active' : ''; ?>" href="/clientes">
                    <i class="fas fa-users me-2"></i>
                    Clientes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'servicos' ? 'active' : ''; ?>" href="/servicos">
                    <i class="fas fa-cogs me-2"></i>
                    Serviços
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'modalidades' ? 'active' : ''; ?>" href="/modalidades">
                    <i class="fas fa-list-alt me-2"></i>
                    Modalidades
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'consultores' ? 'active' : ''; ?>" href="/consultores">
                    <i class="fas fa-user-tie me-2"></i>
                    Consultores
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'os' ? 'active' : ''; ?>" href="/os">
                    <i class="fas fa-file-alt me-2"></i>
                    Ordens de Serviço
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'relacao' ? 'active' : ''; ?>" href="/relacao">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Relação de OS
                </a>
            </li>
        </ul>
        
        <hr>
        
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Relatórios</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'relatorios-os' ? 'active' : ''; ?>" href="/relatorios/os">
                    <i class="fas fa-chart-bar me-2"></i>
                    Ordens de Serviço
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'relatorios-faturamento' ? 'active' : ''; ?>" href="/relatorios/faturamento">
                    <i class="fas fa-dollar-sign me-2"></i>
                    Faturamento
                </a>
            </li>
        </ul>
        
        <hr>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'configuracoes' ? 'active' : ''; ?>" href="/configuracoes">
                    <i class="fas fa-cog me-2"></i>
                    Configurações
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/logout">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Sair
                </a>
            </li>
        </ul>
    </div>
</div>