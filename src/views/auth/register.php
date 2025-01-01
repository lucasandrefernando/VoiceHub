<?php require_once BASE_PATH . '/src/views/layouts/header.php'; ?>

<!-- Inclusão de estilos e scripts necessários -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/register.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<script>
    var BASE_URL = '<?php echo BASE_URL; ?>';
</script>

<main class="main-container">
    <!-- Fundo tecnológico com ícones flutuantes -->
    <div class="tech-background">
        <div class="network-lines"></div>
        <div class="connection-nodes"></div>
        <i class="fas fa-user-plus floating-icon" style="top: 10%; left: 5%; font-size: 24px;"></i>
        <i class="fas fa-clipboard-list floating-icon" style="top: 30%; left: 80%; font-size: 32px;"></i>
        <i class="fas fa-id-card floating-icon" style="top: 60%; left: 15%; font-size: 28px;"></i>
        <i class="fas fa-users floating-icon" style="top: 80%; left: 70%; font-size: 36px;"></i>
        <i class="fas fa-user-shield floating-icon" style="top: 40%; left: 40%; font-size: 30px;"></i>
    </div>

    <!-- Container principal do formulário -->
    <div class="glass-container">
        <!-- Seção de foto do perfil -->
        <div class="photo-section">
            <h2>Cadastre o Seu Usuário.</h2>
            <div class="photo-upload" id="photoUpload">
                <img src="<?php echo BASE_URL; ?>/assets/images/profile.png" alt="Profile Picture" id="profilePreview">
                <input type="file" name="photo" id="photoInput" accept="image/*">
            </div>
            <p class="text-center">Clique para adicionar uma foto</p>
        </div>

        <!-- Seção do formulário de registro -->
        <div class="form-section">
            <form action="<?php echo BASE_URL; ?>/register" method="post" enctype="multipart/form-data" id="registerForm" class="register-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Nome</label>
                        <input type="text" class="form-control" id="name" name="name">
                    </div>
                    <div class="form-group">
                        <label for="surname">Sobrenome</label>
                        <input type="text" class="form-control" id="surname" name="surname">
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
                <div class="form-row">
                    <div class="form-group password-field">
                        <label for="password">Senha</label>
                        <div class="password-input-wrapper">
                            <input type="password" class="form-control" id="password" name="password">
                            <i class="fas fa-eye password-toggle" id="passwordToggle"></i>
                        </div>
                    </div>
                    <div class="form-group password-field">
                        <label for="confirm_password">Confirmar Senha</label>
                        <div class="password-input-wrapper">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            <i class="fas fa-eye password-toggle" id="confirmPasswordToggle"></i>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="company_id">Empresa</label>
                    <select class="form-select" id="company_id" name="company_id">
                        <option value="">Selecione uma empresa</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>"><?php echo $company['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cpf">CPF (opcional)</label>
                    <input type="text" class="form-control" id="cpf" name="cpf">
                </div>
                <div class="form-group terms-checkbox">
                    <label for="terms">
                        <input type="checkbox" id="terms" name="terms">
                        <span class="checkmark"></span>
                        Eu aceito os <a href="#" id="termsLink">Termos de Serviço</a> e a <a href="#" id="privacyLink">Política de Privacidade</a>
                    </label>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Modal para Termos de Serviço e Política de Privacidade -->
<div id="termsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h1 id="termsModalTitle"></h1>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="termsModalContent"></div>
        </div>
        <div class="modal-footer">
            <button class="modal-close-btn">Fechar</button>
        </div>
    </div>
</div>


<!-- Conteúdo oculto para termos e privacidade -->
<div id="termsContent" style="display: none;">
    <?php include 'terms.php'; ?>
</div>
<div id="privacyContent" style="display: none;">
    <?php include 'privacy.php'; ?>
</div>

<!-- Modal de Erro -->
<div id="errorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h1>Ops! Encontramos alguns problemas</h1>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <ul id="errorList"></ul>
        </div>
        <div class="modal-footer">
            <button class="modal-close-btn">Entendi</button>
        </div>
    </div>
</div>

<!-- Modal de Sucesso -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h1>Sucesso!</h1>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p id="successMessage"></p>
        </div>
        <div class="modal-footer">
            <button class="modal-close-btn">Fechar</button>
        </div>
    </div>
</div>

<!-- Modal de E-mail Existente -->
<div id="emailExistsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h1>E-mail já cadastrado</h1>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p id="emailExistsMessage"></p>
            <p>Deseja recuperar sua senha?</p>
        </div>
        <div class="modal-footer">
            <button id="recoverPasswordBtn" class="btn btn-primary">Sim, recuperar senha</button>
            <button id="stayOnPageBtn" class="btn btn-secondary">Não, permanecer na página</button>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/jquery.inputmask.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/register.js"></script>
</body>

<?php require_once BASE_PATH . '/src/views/layouts/footer.php'; ?>