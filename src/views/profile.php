<?php
// src/views/profile.php
$pageTitle = 'Seu Perfil';
ob_start();
?>

<div class="profile-header">
    <div class="profile-photo">
        <img src="<?php echo !empty($user['profile_picture']) ? BASE_URL . '/uploads/profile_pictures/' . $user['profile_picture'] : BASE_URL . '/assets/images/default-profile.png'; ?>" alt="Foto de perfil">
        <label for="profile-photo-input" class="profile-photo-edit">
            <i class="fas fa-camera"></i>
        </label>
    </div>
    <div class="profile-info">
        <h1><?php echo htmlspecialchars($user['name']); ?></h1>
        <p><?php echo htmlspecialchars($user['email']); ?></p>
    </div>
</div>
<div class="profile-body">
    <form action="<?php echo BASE_URL; ?>/profile/update" method="POST" class="profile-form">
        <div class="form-group">
            <label for="name">Nome</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <div class="form-group">
            <label for="current-password">Senha Atual</label>
            <input type="password" id="current-password" name="current_password">
        </div>
        <div class="form-group">
            <label for="new-password">Nova Senha</label>
            <input type="password" id="new-password" name="new_password">
        </div>
        <div class="form-group">
            <label for="confirm-password">Confirmar Nova Senha</label>
            <input type="password" id="confirm-password" name="confirm_password">
        </div>
        <button type="submit" class="btn-update">Atualizar Perfil</button>
    </form>
</div>

<input type="file" id="profile-photo-input" name="profile_picture" style="display: none;">

<script src="<?php echo BASE_URL; ?>/assets/js/profile.js"></script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/src/views/layouts/footer.php';
?>