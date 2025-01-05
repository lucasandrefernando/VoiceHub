<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<!-- Inclusão de estilos e scripts necessários -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/user_profile.css">
<script>
    var BASE_URL = '<?php echo BASE_URL; ?>';
</script>

<main class="main-container">
    <div class="glass-container">
        <div class="profile-content">
            <!-- Sidebar com foto do perfil -->
            <div class="profile-sidebar">
                <div class="profile-picture-container">
                    <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Foto de Perfil" class="profile-picture" id="profilePicture">
                    <div class="profile-picture-overlay">
                        <button id="editProfilePicture" class="btn btn-light btn-sm rounded-circle" title="Editar foto">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button id="removeProfilePicture" class="btn btn-light btn-sm rounded-circle" title="Remover foto">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <input type="file" id="profilePictureInput" style="display: none;" accept="image/*">
                <p class="photo-hint">
                    <i class="fas fa-camera"></i>
                    <span>Clique na imagem para alterar a foto</span>
                </p>
            </div>

            <!-- Divisor entre sidebar e conteúdo principal -->
            <div class="profile-divider"></div>

            <!-- Conteúdo principal -->
            <div class="profile-main">
                <h1 class="profile-title">Perfil do Usuário</h1>

                <!-- Abas de navegação -->
                <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                            <i class="fas fa-info-circle"></i> Informações
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">
                            <i class="fas fa-key"></i> Alterar Senha
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions" type="button" role="tab" aria-controls="permissions" aria-selected="false">
                            <i class="fas fa-user-shield"></i> Permissões
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="profileTabsContent">
                    <!-- Aba de Informações -->
                    <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                        <form id="updateProfileForm">
                            <div class="form-group">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user"></i>
                                    <span>Nome Completo</span>
                                </label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    <span>E-mail</span>
                                </label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="cpf" class="form-label">
                                    <i class="fas fa-id-card"></i>
                                    <span>CPF</span>
                                </label>
                                <input type="text" class="form-control" id="cpf" name="cpf" value="<?php echo htmlspecialchars($user['cpf'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-venus-mars"></i>
                                    <span>Gênero</span>
                                </label>
                                <div class="gender-options">
                                    <label class="gender-option">
                                        <input type="radio" name="gender" value="male" <?php echo (isset($user['gender']) && $user['gender'] === 'male') ? 'checked' : ''; ?>>
                                        <span><i class="fas fa-mars"></i> Masc.</span>
                                    </label>
                                    <label class="gender-option">
                                        <input type="radio" name="gender" value="female" <?php echo (isset($user['gender']) && $user['gender'] === 'female') ? 'checked' : ''; ?>>
                                        <span><i class="fas fa-venus"></i> Fem.</span>
                                    </label>
                                    <label class="gender-option">
                                        <input type="radio" name="gender" value="other" <?php echo (isset($user['gender']) && $user['gender'] === 'other') ? 'checked' : ''; ?>>
                                        <span><i class="fas fa-genderless"></i> Outro</span>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group update-button">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Atualizar Perfil</button>
                            </div>
                        </form>
                    </div>

                    <!-- Aba de Alteração de Senha -->
                    <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                        <form id="changePasswordForm">
                            <div class="form-group password-field">
                                <label for="currentPassword" class="form-label">
                                    <i class="fas fa-lock"></i>
                                    <span>Senha Atual</span>
                                </label>
                                <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                                <i class="fas fa-eye password-toggle" id="currentPasswordToggle"></i>
                            </div>
                            <div class="form-group password-field">
                                <label for="newPassword" class="form-label">
                                    <i class="fas fa-key"></i>
                                    <span>Nova Senha</span>
                                </label>
                                <input type="password" class="form-control" id="newPassword" name="new_password" required>
                                <i class="fas fa-eye password-toggle" id="newPasswordToggle"></i>
                            </div>
                            <div id="passwordStrength"></div>
                            <ul id="passwordCriteria">
                                <li id="lengthCriteria">Mínimo de 8 caracteres</li>
                                <li id="upperCriteria">Pelo menos uma letra maiúscula</li>
                                <li id="lowerCriteria">Pelo menos uma letra minúscula</li>
                                <li id="numberCriteria">Pelo menos um número</li>
                                <li id="specialCriteria">Pelo menos um caractere especial</li>
                            </ul>
                            <div class="form-group password-field">
                                <label for="confirmPassword" class="form-label">
                                    <i class="fas fa-check-double"></i>
                                    <span>Confirmar Nova Senha</span>
                                </label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                                <i class="fas fa-eye password-toggle" id="confirmPasswordToggle"></i>
                            </div>
                            <div class="form-group update-button">
                                <button type="submit" class="btn btn-primary" id="changePasswordBtn" disabled><i class="fas fa-save"></i> Alterar Senha</button>
                            </div>
                        </form>
                    </div>

                    <!-- Aba de Permissões -->
                    <div class="tab-pane fade" id="permissions" role="tabpanel" aria-labelledby="permissions-tab">
                        <?php if (empty($permissions)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Você não possui permissões específicas atribuídas.
                            </div>
                        <?php else: ?>
                            <ul class="permissions-list">
                                <?php
                                $permissionDescriptions = [
                                    'create_user' => 'Permite criar novos usuários no sistema.',
                                    'edit_user' => 'Permite editar informações de usuários existentes.',
                                    'delete_user' => 'Permite excluir usuários do sistema.',
                                    'view_reports' => 'Permite visualizar relatórios do sistema.',
                                    'manage_settings' => 'Permite gerenciar configurações do sistema.'
                                ];
                                foreach ($permissions as $key => $value):
                                    $description = $permissionDescriptions[$key] ?? 'Descrição não disponível.';
                                ?>
                                    <li class="permission-item <?php echo $value ? 'active' : 'inactive'; ?>">
                                        <div class="permission-info">
                                            <span class="permission-name"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?></span>
                                            <span class="permission-description"><?php echo htmlspecialchars($description); ?></span>
                                        </div>
                                        <span class="permission-status">
                                            <?php if ($value): ?>
                                                <i class="fas fa-check-circle"></i> Ativo
                                            <?php else: ?>
                                                <i class="fas fa-times-circle"></i> Inativo
                                            <?php endif; ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal para alertas -->
<div id="alertModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"></h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <ul id="modalMessageList"></ul>
        </div>
        <div class="modal-footer">
            <button id="modalCloseBtn" class="modal-close-btn">Entendi</button>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/user_profile.js"></script>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>