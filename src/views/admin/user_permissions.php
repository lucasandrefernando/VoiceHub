<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<!-- Inclusão dos estilos CSS e bibliotecas externas -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/user_permissions.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- Definição da variável BASE_URL para uso no JavaScript -->
<script>
    var BASE_URL = '<?php echo BASE_URL; ?>';
</script>

<!-- Container principal da página de permissões -->
<div class="permissions-container">
    <h1><i class="fas fa-user-lock"></i> Gerenciamento de Permissões de Usuários</h1>

    <div class="content-wrapper">
        <!-- Painel de lista de usuários -->
        <div class="user-list-panel">
            <h2><i class="fas fa-users"></i> Usuários</h2>
            <!-- Campo de busca de usuários -->
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="userSearch" placeholder="Buscar usuário...">
            </div>
            <!-- Lista de usuários com scroll -->
            <div class="user-list-scroll">
                <ul id="userListContainer">
                    <?php foreach ($users as $user): ?>
                        <li class="user-item" data-user-id="<?php echo htmlspecialchars($user['id']); ?>">
                            <img src="<?php echo BASE_URL . htmlspecialchars($user['avatar']); ?>"
                                alt="Foto de Perfil"
                                class="user-avatar"
                                onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>/assets/images/profile.png';"
                                data-original-src="<?php echo BASE_URL . htmlspecialchars($user['avatar']); ?>">
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                                <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <!-- Controles de paginação -->
            <div class="pagination">
                <button id="prevPage" class="btn-secondary"><i class="fas fa-chevron-left"></i> Anterior</button>
                <span id="pageInfo">Página <span id="currentPage">1</span> de <span id="totalPages">1</span></span>
                <button id="nextPage" class="btn-secondary">Próximo <i class="fas fa-chevron-right"></i></button>
            </div>
        </div>

        <!-- Painel de permissões -->
        <div class="permissions-panel">
            <h2><i class="fas fa-shield-alt"></i> Permissões</h2>
            <!-- Placeholder exibido quando nenhum usuário está selecionado -->
            <div id="permissionsPlaceholder" class="permissions-placeholder">
                <i class="fas fa-user-cog fa-4x"></i>
                <h3>Gerenciamento de Permissões</h3>
                <p>Selecione um usuário na lista à esquerda para visualizar e editar suas permissões.</p>
            </div>
            <!-- Formulário de permissões (inicialmente oculto) -->
            <form id="permissionsForm" method="post" style="display: none;">
                <input type="hidden" id="selectedUserId" name="user_id" value="">
                <div class="permissions-sections">
                    <!-- Seção de permissões simples -->
                    <div class="permissions-column">
                        <div id="simplePermissionsContainer" class="permissions-group">
                            <h3><i class="fas fa-check-circle"></i>Simples</h3>
                            <!-- Permissões simples serão inseridas aqui via JavaScript -->
                        </div>
                    </div>
                    <!-- Seção de permissões avançadas -->
                    <div class="permissions-column">
                        <div id="advancedPermissionsContainer" class="permissions-group">
                            <h3><i class="fas fa-star"></i>Avançadas</h3>
                            <!-- Permissões avançadas serão inseridas aqui via JavaScript -->
                        </div>
                    </div>
                    <!-- Aviso para seleção de permissões avançadas -->
                    <div id="advancedPermissionsWarning" style="display: none; color: red; margin-top: 10px;">
                        <i class="fas fa-exclamation-triangle"></i> Selecione pelo menos uma permissão avançada para solicitar o código de verificação.
                    </div>
                </div>
                <!-- Container para o código de verificação -->
                <div id="verificationCodeContainer" style="display: none;">
                    <h3><i class="fas fa-lock"></i> Verificação para Permissões Avançadas</h3>
                    <p><i class="fas fa-envelope"></i> Um código de verificação será enviado para o email do administrador geral.</p>
                    <button type="button" id="requestVerificationCodeBtn" class="btn-primary"><i class="fas fa-paper-plane"></i> Solicitar Código de Verificação</button>
                    <div id="verificationCodeInputContainer" style="display: none;">
                        <label for="verificationCode"><i class="fas fa-key"></i> Código de Verificação:</label>
                        <input type="text" id="verificationCode" name="verification_code">
                        <button type="button" id="verifyCodeBtn" class="btn-primary"><i class="fas fa-check"></i> Verificar Código</button>
                    </div>
                </div>
                <button type="submit" class="btn-save" disabled><i class="fas fa-save"></i> Salvar Permissões</button>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Salvamento -->
<div id="saveConfirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h1>Permissões Atualizadas</h1>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>As seguintes permissões foram atualizadas:</p>
            <ul id="updatedPermissionsList"></ul>
        </div>
        <div class="modal-footer">
            <button id="confirmSaveBtn" class="modal-close-btn">OK</button>
        </div>
    </div>
</div>

<!-- Modal de Alterações Não Salvas -->
<div id="unsavedChangesModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h1>Alterações Não Salvas</h1>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Existem alterações não salvas. O que você deseja fazer?</p>
        </div>
        <div class="modal-footer">
            <button id="saveChanges" class="modal-close-btn">Salvar Alterações</button>
            <button id="continueWithoutSaving" class="modal-close-btn">Continuar sem Salvar</button>
            <button id="cancelUnsavedChanges" class="modal-close-btn">Cancelar</button>
        </div>
    </div>
</div>

<!-- Modal de Notificação -->
<div id="notificationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h1 id="notificationTitle"></h1>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p id="notificationMessage"></p>
        </div>
        <div class="modal-footer">
            <button id="notificationOkBtn" class="modal-close-btn">OK</button>
        </div>
    </div>
</div>

<!-- Indicador de Carregamento -->
<div id="loadingIndicator" style="display: none;">
    <i class="fas fa-spinner fa-spin"></i> Processando...
</div>

<!-- Inclusão do script JavaScript -->
<script src="<?php echo BASE_URL; ?>/assets/js/user_permissions.js"></script>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>