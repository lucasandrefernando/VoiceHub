<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<!-- Inclusão dos estilos CSS e bibliotecas externas -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- Canvas para o efeito de fundo animado -->
<canvas id="backgroundCanvas"></canvas>

<!-- Container principal do painel de administração -->
<div class="admin-dashboard">
    <h1 class="dashboard-title">Painel do Administrador</h1>

    <!-- Container dos cards de administração -->
    <div class="admin-cards">
        <!-- Card de Controle de Permissões -->
        <div class="admin-card">
            <div class="card-icon">
                <i class="fas fa-user-cog"></i>
            </div>
            <h3>Controle de Permissões</h3>
            <p class="stat-number"><span id="totalUsers"><?php echo $totalUsers; ?></span></p>
            <p class="stat-label">Usuários</p>
            <p class="card-description">Gerencie as permissões de acesso dos usuários ao sistema.</p>
            <a href="<?php echo BASE_URL; ?>/admin/user-permissions" class="btn-action">
                Gerenciar Permissões
            </a>
        </div>

        <!-- Card de Gerenciamento de Empresas -->
        <div class="admin-card">
            <div class="card-icon">
                <i class="fas fa-building"></i>
            </div>
            <h3>Gerenciar Empresas</h3>
            <p class="stat-number"><span id="totalCompanies"><?php echo $totalCompanies; ?></span></p>
            <p class="stat-label">Empresas</p>
            <p class="card-description">Adicione, edite ou remova empresas do sistema.</p>
            <a href="<?php echo BASE_URL; ?>/companies" class="btn-action">
                Gerenciar Empresas
            </a>
        </div>

        <!-- Card de Gerenciamento de Licenças -->
        <div class="admin-card">
            <div class="card-icon">
                <i class="fas fa-key"></i>
            </div>
            <h3>Gerenciar Licenças</h3>
            <p class="stat-number"><span id="activeLicenses"><?php echo $activeLicenses; ?></span></p>
            <p class="stat-label">Licenças Ativas</p>
            <p class="card-description">Controle e atualize as licenças das empresas cadastradas.</p>
            <a href="<?php echo BASE_URL; ?>/licenses" class="btn-action">
                Gerenciar Licenças
            </a>
        </div>
    </div>
</div>

<!-- Inclusão de scripts JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/admin.js"></script>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>