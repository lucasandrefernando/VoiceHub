<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/reset_password.css">

<main class="main-container">
    <i class="fas fa-key floating-icon" style="top: 10%; left: 10%;"></i>
    <i class="fas fa-lock floating-icon" style="top: 20%; right: 15%;"></i>
    <i class="fas fa-shield-alt floating-icon" style="bottom: 15%; left: 15%;"></i>
    <i class="fas fa-user-lock floating-icon" style="bottom: 25%; right: 10%;"></i>

    <div class="glass-container">
        <form id="resetPasswordForm" class="reset-form">
            <h2>Recuperação de Senha</h2>

            <div id="emailStep">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> E-mail:</label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="Seu e-mail">
                </div>
                <button type="button" id="sendEmailBtn" class="btn-primary">Enviar Código <i class="fas fa-paper-plane"></i></button>
            </div>

            <div id="codeStep" style="display:none;">
                <div class="form-group">
                    <label for="verificationCode"><i class="fas fa-key"></i> Código de Verificação:</label>
                    <input type="text" id="verificationCode" name="verificationCode" class="form-control" required placeholder="Digite o código">
                </div>
                <button type="button" id="verifyCodeBtn" class="btn-primary">Verificar <i class="fas fa-check"></i></button>
            </div>

            <div id="passwordStep" style="display:none;">
                <div class="form-group password-field">
                    <label for="newPassword"><i class="fas fa-lock"></i> Nova Senha:</label>
                    <input type="password" id="newPassword" name="newPassword" class="form-control" required placeholder="Nova senha">
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
                    <label for="confirmPassword"><i class="fas fa-lock"></i> Confirmar Senha:</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required placeholder="Confirme a nova senha">
                    <i class="fas fa-eye password-toggle" id="confirmPasswordToggle"></i>
                </div>
                <button type="button" id="resetPasswordBtn" class="btn-primary" disabled>Alterar Senha <i class="fas fa-save"></i></button>
            </div>

            <div class="login-links">
                <a href="<?php echo BASE_URL; ?>/login" class="link-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Voltar para o login</span>
                </a>
            </div>
        </form>
    </div>
</main>

<div id="alertModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h1 id="modalTitle"></h1>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p id="modalMessage"></p>
        </div>
        <div class="modal-footer">
            <button id="modalCloseBtn" class="modal-close-btn">Entendi</button>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo BASE_URL; ?>/assets/js/reset_password.js"></script>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>