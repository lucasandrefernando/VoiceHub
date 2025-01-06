<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<!-- Inclusão dos estilos e scripts necessários -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/companies.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
<script>
    var BASE_URL = "<?php echo BASE_URL; ?>";
    var initialCompanies = <?php echo json_encode($companies); ?>;
</script>

<!-- Container principal -->
<div class="companies-wrapper">
    <!-- Sidebar com lista de empresas e busca -->
    <div class="sidebar">
        <div class="sidebar-fixed">
            <h2>Empresas</h2>
            <button id="addCompanyBtn" class="btn-add">
                <i class="fas fa-plus"></i> Nova Empresa
            </button>
            <div class="search-box">
                <input type="text" id="companySearch" placeholder="Buscar empresa...">
                <i class="fas fa-search"></i>
            </div>
        </div>
        <div class="company-list" id="companyList">
            <!-- Preenchido via JavaScript -->
        </div>
    </div>

    <!-- Conteúdo principal com detalhes da empresa e formulário -->
    <div class="main-content">
        <!-- Card de detalhes da empresa -->
        <div id="companyDetails" class="company-card">
            <h1 id="companyNameDisplay">Selecione uma empresa</h1>
            <div class="company-info">
                <div class="info-item">
                    <i class="fas fa-building"></i>
                    <label>Nome Fantasia</label>
                    <span id="companyTradeNameDisplay"></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-id-card"></i>
                    <label>CNPJ</label>
                    <span id="companyCnpjDisplay"></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <label>E-mail</label>
                    <span id="companyEmailDisplay"></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <label>Telefone</label>
                    <span id="companyPhoneDisplay"></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <label>Endereço</label>
                    <span id="companyAddressDisplay"></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-globe"></i>
                    <label>Website</label>
                    <span id="companyWebsiteDisplay"></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <label>Data de Criação</label>
                    <span id="companyCreatedAtDisplay"></span>
                </div>
            </div>
            <div class="action-buttons">
                <button id="editCompanyBtn" class="btn-edit">Editar</button>
                <button id="deleteCompanyBtn" class="btn-delete">Excluir</button>
            </div>
        </div>

        <!-- Formulário para adicionar/editar empresa -->
        <div id="companyForm" class="company-card" style="display: none;">
            <h1 id="formTitle">Adicionar Nova Empresa</h1>
            <form id="addEditCompanyForm">
                <input type="hidden" id="companyId">
                <div class="form-row">
                    <div class="form-group">
                        <label for="companyName"><i class="fas fa-building"></i> Nome da Empresa</label>
                        <input type="text" id="companyName" required>
                    </div>
                    <div class="form-group">
                        <label for="companyTradeName"><i class="fas fa-store"></i> Nome Fantasia</label>
                        <input type="text" id="companyTradeName">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="companyCnpj"><i class="fas fa-id-card"></i> CNPJ</label>
                        <div class="input-group">
                            <input type="text" id="companyCnpj" required>
                            <button type="button" id="searchCnpjBtn">Buscar CNPJ</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="companyEmail"><i class="fas fa-envelope"></i> E-mail</label>
                        <input type="email" id="companyEmail">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="companyPhone"><i class="fas fa-phone"></i> Telefone</label>
                        <input type="text" id="companyPhone">
                    </div>
                    <div class="form-group">
                        <label for="companyWebsite"><i class="fas fa-globe"></i> Website</label>
                        <input type="url" id="companyWebsite">
                    </div>
                </div>
                <div class="form-group">
                    <label for="companyAddress"><i class="fas fa-map-marker-alt"></i> Endereço</label>
                    <input type="text" id="companyAddress">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="companyCity"><i class="fas fa-city"></i> Cidade</label>
                        <input type="text" id="companyCity">
                    </div>
                    <div class="form-group">
                        <label for="companyState"><i class="fas fa-flag"></i> Estado</label>
                        <input type="text" id="companyState" maxlength="2">
                    </div>
                    <div class="form-group">
                        <label for="companyZipCode"><i class="fas fa-mail-bulk"></i> CEP</label>
                        <input type="text" id="companyZipCode">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" id="cancelBtn" class="btn-secondary">Cancelar</button>
                    <button type="submit" id="saveCompanyBtn" class="btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para notificações -->
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
            <button class="modal-close-btn">Fechar</button>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h1>Confirmar Exclusão</h1>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Tem certeza que deseja excluir esta empresa?</p>
        </div>
        <div class="modal-footer">
            <button id="cancelDeleteBtn" class="btn-secondary">Cancelar</button>
            <button id="confirmDeleteBtn" class="btn-danger">Excluir</button>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/companies.js"></script>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>