<?php
include BASE_PATH . '/src/views/layouts/header.php';
?>

<script>
    var BASE_URL = "<?php echo BASE_URL; ?>";
    var initialCompanies = <?php echo json_encode($companies); ?>;
</script>

<div class="companies-container">
    <h1>Gerenciamento de Empresas</h1>

    <button id="addCompanyBtn" class="btn btn-primary mb-3">
        <i class="fas fa-plus"></i> Adicionar Nova Empresa
    </button>

    <table id="companiesTable" class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>CNPJ</th>
                <th>E-mail</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <!-- Preenchido via JavaScript -->
        </tbody>
    </table>
</div>

<!-- Modal para adicionar/editar empresa -->
<div class="modal fade" id="companyModal" tabindex="-1" aria-labelledby="companyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="companyModalLabel">Adicionar Empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="companyForm">
                    <input type="hidden" id="companyId">
                    <div class="mb-3">
                        <label for="companyCnpj" class="form-label">CNPJ</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="companyCnpj">
                            <button class="btn btn-outline-secondary" type="button" id="searchCnpjBtn">Buscar</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="companyName" class="form-label">Nome da Empresa</label>
                        <input type="text" class="form-control" id="companyName" required>
                    </div>
                    <div class="mb-3">
                        <label for="companyEmail" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="companyEmail">
                    </div>
                    <div class="mb-3">
                        <label for="companyAddress" class="form-label">Endereço</label>
                        <input type="text" class="form-control" id="companyAddress">
                    </div>
                    <div class="mb-3">
                        <label for="companyCity" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="companyCity">
                    </div>
                    <div class="mb-3">
                        <label for="companyState" class="form-label">Estado</label>
                        <input type="text" class="form-control" id="companyState">
                    </div>
                    <div class="mb-3">
                        <label for="companyZipCode" class="form-label">CEP</label>
                        <input type="text" class="form-control" id="companyZipCode">
                    </div>
                    <div class="mb-3">
                        <label for="companyPhone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="companyPhone">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveCompanyBtn">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Tem certeza que deseja excluir esta empresa?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Excluir</button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/companies.js"></script>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>