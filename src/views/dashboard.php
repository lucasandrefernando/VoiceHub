<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<script>
    var BASE_URL = "<?php echo BASE_URL; ?>";
</script>

<?php
if (isset($_GET['debug'])) {
    echo "<pre>";
    print_r($userPermissions);
    echo "</pre>";
}
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="user-info">
                <div class="user-avatar">
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
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js"></script>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>