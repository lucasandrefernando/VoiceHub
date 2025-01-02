<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<!-- Inclui o CSS específico do dashboard -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">

<!-- Define a BASE_URL para uso no JavaScript -->
<script>
    var BASE_URL = "<?php echo BASE_URL; ?>";
</script>

<!-- Adicionar verificação de debug -->
<?php
if (isset($_GET['debug'])) {
    echo "<pre>";
    print_r($userPermissions);
    echo "</pre>";
}
?>

<!-- Corpo principal do dashboard -->
<div class="page-wrapper">
    <div class="dashboard-container">
        <!-- Cabeçalho do dashboard -->
        <div class="dashboard-header">
            <div class="user-info">
                <div class="user-avatar">
                    <!-- Exibe a foto do usuário ou uma imagem padrão -->
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

        <!-- Conteúdo principal do dashboard -->
        <div class="dashboard-content">
            <!-- Container de estatísticas -->
            <div class="stats-container">
                <!-- Card de estatística: Total de Gravações -->
                <div class="stat-card" data-stat="totalRecordings">
                    <div class="stat-icon"><i class="fas fa-microphone-alt"></i></div>
                    <div class="stat-details">
                        <h4>Total de Gravações</h4>
                        <p class="stat-number"><?php echo $totalRecordings; ?></p>
                    </div>
                </div>
                <!-- Card de estatística: Gravações de Hoje -->
                <div class="stat-card" data-stat="todayRecordings">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-details">
                        <h4>Gravações Hoje</h4>
                        <p class="stat-number"><?php echo $todayRecordings; ?></p>
                    </div>
                </div>
                <!-- Card de estatística: Usuários Ativos -->
                <div class="stat-card" data-stat="activeUsers">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-details">
                        <h4>Usuários Ativos</h4>
                        <p class="stat-number"><?php echo $activeUsers; ?></p>
                    </div>
                </div>
            </div>

            <!-- Seção de Administrador (visível apenas para administradores) -->
            <?php if ($is_admin): ?>
                <div class="admin-section">
                    <h3 class="admin-section-title">Área do Administrador</h3>
                    <div class="admin-grid">
                        <!-- Card: Controle de Permissões -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <div class="admin-icon"><i class="fas fa-user-cog"></i></div>
                                <h4>Controle de Permissões</h4>
                            </div>
                            <p>Gerencie as permissões dos usuários</p>
                            <?php if (isset($_SESSION['user_permissions']['administrador_sistema']) && $_SESSION['user_permissions']['administrador_sistema']): ?>
                                <a href="<?php echo BASE_URL; ?>/admin/user-permissions" class="admin-btn">Acessar</a>
                            <?php else: ?>
                                <button class="admin-btn disabled" onclick="showPermissionDeniedModal('Controle de Permissões')">Acessar</button>
                            <?php endif; ?>
                        </div>
                        <!-- Card: Gerenciar Empresas -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <div class="admin-icon"><i class="fas fa-building"></i></div>
                                <h4>Gerenciar Empresas</h4>
                            </div>
                            <p>Administre as empresas cadastradas</p>
                            <?php if (isset($_SESSION['user_permissions']['gerenciar_empresas']) && $_SESSION['user_permissions']['gerenciar_empresas'] == 1): ?>
                                <a href="<?php echo BASE_URL; ?>/companies" class="admin-btn">Acessar</a>
                            <?php else: ?>
                                <button class="admin-btn disabled" onclick="showPermissionDeniedModal('Gerenciar Empresas')">Acessar</button>
                            <?php endif; ?>
                        </div>
                        <!-- Card: Gerenciar Licenças -->
                        <div class="admin-card">
                            <div class="admin-card-header">
                                <div class="admin-icon"><i class="fas fa-key"></i></div>
                                <h4>Gerenciar Licenças</h4>
                            </div>
                            <p>Controle as licenças ativas</p>
                            <?php if (isset($userPermissions['gerenciar_licencas']) && $userPermissions['gerenciar_licencas']): ?>
                                <a href="<?php echo BASE_URL; ?>/licenses" class="admin-btn">Acessar</a>
                            <?php else: ?>
                                <button class="admin-btn disabled" onclick="showPermissionDeniedModal('Gerenciar Licenças')">Acessar</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Estatísticas do administrador -->
                    <div class="admin-stats">
                        <!-- Total de Empresas -->
                        <div class="stat-card" data-stat="totalCompanies">
                            <div class="stat-icon"><i class="fas fa-building"></i></div>
                            <div class="stat-details">
                                <h4>Total de Empresas</h4>
                                <p class="stat-number"><?php echo $totalCompanies; ?></p>
                            </div>
                        </div>
                        <!-- Total de Usuários -->
                        <div class="stat-card" data-stat="totalUsers">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-details">
                                <h4>Total de Usuários</h4>
                                <p class="stat-number"><?php echo $totalUsers; ?></p>
                            </div>
                        </div>
                        <!-- Licenças Ativas -->
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

<!-- Modal de Permissão Negada -->
<div class="modal fade" id="permissionDeniedModal" tabindex="-1" aria-labelledby="permissionDeniedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="permissionDeniedModalLabel">Acesso Negado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-exclamation-triangle fa-4x text-warning"></i>
                </div>
                <p class="text-center" id="permissionDeniedMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Inclui os scripts necessários -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js"></script>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>