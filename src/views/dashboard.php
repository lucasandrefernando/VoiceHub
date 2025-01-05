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

<!-- Canvas para o efeito de fundo -->
<canvas id="backgroundCanvas"></canvas>

<!-- Corpo principal do dashboard -->
<div class="page-wrapper">
    <div class="dashboard-container">
        <!-- Cabeçalho do dashboard -->
        <div class="dashboard-header">
            <div class="user-info">
                <div class="user-avatar">
                    <!-- Exibe a foto do usuário ou uma imagem padrão -->
                    <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="User Photo" class="user-photo" onerror="this.src='<?php echo BASE_URL; ?>/assets/images/profile.png'">
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
        </div>
    </div>
</div>

<!-- Inclui os scripts necessários -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js"></script>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>