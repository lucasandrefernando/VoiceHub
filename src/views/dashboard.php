<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">

<div class="page-wrapper">
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="user-info">
                <div class="user-avatar">
                    <img src="<?php echo !empty($photoPath) ? htmlspecialchars($photoPath) : BASE_URL . '/assets/images/profile.png'; ?>" alt="User Photo" class="user-photo">
                </div>
                <div class="user-details">
                    <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            <div class="company-info">
                <h3>Empresa: <?php echo htmlspecialchars($companyName); ?></h3>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="stats-container">
                <div class="stat-card" data-stat="totalRecordings">
                    <div class="stat-icon"><i class="fas fa-microphone-alt"></i></div>
                    <div class="stat-details">
                        <h4>Total de Gravações</h4>
                        <p class="stat-number"><?php echo $totalRecordings; ?></p>
                    </div>
                </div>
                <div class="stat-card" data-stat="todayRecordings">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-details">
                        <h4>Gravações Hoje</h4>
                        <p class="stat-number"><?php echo $todayRecordings; ?></p>
                    </div>
                </div>
                <div class="stat-card" data-stat="activeUsers">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-details">
                        <h4>Usuários Ativos</h4>
                        <p class="stat-number"><?php echo $activeUsers; ?></p>
                    </div>
                </div>
            </div>

            <?php if ($is_admin): ?>
                <div class="admin-section">
                    <h3>Área do Administrador</h3>
                    <div class="admin-grid">
                        <div class="admin-card">
                            <div class="admin-icon"><i class="fas fa-user-cog"></i></div>
                            <h4>Controle de Permissões</h4>
                            <p>Gerencie as permissões dos usuários</p>
                            <a href="<?php echo BASE_URL; ?>/admin/user-permissions" class="admin-btn">Acessar</a>
                        </div>
                        <div class="admin-card">
                            <div class="admin-icon"><i class="fas fa-building"></i></div>
                            <h4>Gerenciar Empresas</h4>
                            <p>Administre as empresas cadastradas</p>
                            <a href="<?php echo BASE_URL; ?>/admin/companies" class="admin-btn">Acessar</a>
                        </div>
                        <div class="admin-card">
                            <div class="admin-icon"><i class="fas fa-key"></i></div>
                            <h4>Gerenciar Licenças</h4>
                            <p>Controle as licenças ativas</p>
                            <a href="<?php echo BASE_URL; ?>/admin/licenses" class="admin-btn">Acessar</a>
                        </div>
                    </div>
                    <div class="admin-stats">
                        <div class="stat-card" data-stat="totalCompanies">
                            <div class="stat-icon"><i class="fas fa-building"></i></div>
                            <div class="stat-details">
                                <h4>Total de Empresas</h4>
                                <p class="stat-number"><?php echo $totalCompanies; ?></p>
                            </div>
                        </div>
                        <div class="stat-card" data-stat="totalUsers">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-details">
                                <h4>Total de Usuários</h4>
                                <p class="stat-number"><?php echo $totalUsers; ?></p>
                            </div>
                        </div>
                        <div class="stat-card" data-stat="activeLicenses">
                            <div class="stat-icon"><i class="fas fa-key"></i></div>
                            <div class="stat-details">
                                <h4>Licenças Ativas</h4>
                                <p class="stat-number"><?php echo $activeLicenses; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<br /><br />

<script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js"></script>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>