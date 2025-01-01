<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<!-- Inclusão dos estilos CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/login.css">

<!-- Container principal da página de login -->
<div class="login-container">
    <div class="login-content">
        <!-- Área para imagem decorativa -->
        <div class="login-image"></div>
        <!-- Container do formulário de login -->
        <div class="login-form-container">
            <form id="loginForm" class="login-form" action="<?php echo BASE_URL; ?>/login" method="post">
                <h2>Bem-vindo!</h2>

                <!-- Campo de e-mail -->
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> E-mail:</label>
                    <input type="email" id="email" name="email" class="form-control">
                </div>

                <!-- Campo de senha com toggle de visibilidade -->
                <div class="form-group password-field">
                    <label for="password"><i class="fas fa-lock"></i> Senha:</label>
                    <input type="password" id="password" name="password" class="form-control">
                    <span class="password-toggle">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>

                <!-- Campo de código de verificação (inicialmente oculto) -->
                <div id="verificationCodeField" class="form-group verification-code-group" style="display: none;">
                    <label for="verification_code"><i class="fas fa-shield-alt"></i> Código de Verificação</label>
                    <div class="verification-input-group">
                        <input type="text" id="verification_code" name="verification_code" class="form-control" placeholder="Digite o código de 6 dígitos">
                        <button type="button" id="resendCodeBtn" class="btn-resend">
                            <i class="fas fa-redo-alt"></i> Reenviar
                        </button>
                    </div>
                    <small class="verification-info">Um código de verificação foi enviado para o seu e-mail.</small>
                </div>

                <!-- Botão de submit -->
                <button type="submit" class="btn-primary">Entrar <i class="fas fa-sign-in-alt"></i></button>

                <!-- Links adicionais -->
                <div class="login-links">
                    <a href="<?php echo BASE_URL; ?>/reset-password" class="link-forgot">
                        <i class="fas fa-key"></i>
                        <span>Esqueceu a senha?</span>
                    </a>
                    <div class="link-divider"></div>
                    <a href="<?php echo BASE_URL; ?>/register" class="link-register">
                        <i class="fas fa-user-plus"></i>
                        <span>Criar nova conta</span>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para mensagens -->
<div id="messageModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"></h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="modalIcon"></div>
            <p id="modalMessage"></p>
        </div>
        <div class="modal-footer">
            <button id="modalCloseButton" class="btn-modal">Fechar</button>
        </div>
    </div>
</div>

<!-- Definição da constante BASE_URL para uso no JavaScript -->
<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>

<!-- Inclusão do JavaScript específico para a página de login -->
<script src="<?php echo BASE_URL; ?>/assets/js/login.js"></script>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>