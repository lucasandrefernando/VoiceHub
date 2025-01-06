document.addEventListener('DOMContentLoaded', function () {
    // Elementos do DOM
    const addCompanyBtn = document.getElementById('addCompanyBtn');
    const companyForm = document.getElementById('addEditCompanyForm');
    const saveCompanyBtn = document.getElementById('saveCompanyBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const searchCnpjBtn = document.getElementById('searchCnpjBtn');
    const cnpjInput = document.getElementById('companyCnpj');
    const companyList = document.getElementById('companyList');
    const companyDetails = document.getElementById('companyDetails');
    const companyFormContainer = document.getElementById('companyForm');
    const editCompanyBtn = document.getElementById('editCompanyBtn');
    const deleteCompanyBtn = document.getElementById('deleteCompanyBtn');
    const companySearch = document.getElementById('companySearch');
    const notificationModal = document.getElementById('notificationModal');
    const deleteConfirmModal = document.getElementById('deleteConfirmModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

    // Variáveis globais
    let companies = initialCompanies || [];
    let currentCompanyId = null;

    // Inicialização
    renderCompanyList();

    // Event Listeners
    addCompanyBtn.addEventListener('click', showAddCompanyForm);
    companyForm.addEventListener('submit', saveCompany);
    cancelBtn.addEventListener('click', hideCompanyForm);
    searchCnpjBtn.addEventListener('click', searchCompanyByCNPJ);
    editCompanyBtn.addEventListener('click', () => showEditCompanyForm(currentCompanyId));
    deleteCompanyBtn.addEventListener('click', confirmDeleteCompany);
    companySearch.addEventListener('input', filterCompanies);
    confirmDeleteBtn.addEventListener('click', deleteCompany);
    cancelDeleteBtn.addEventListener('click', closeModal);

    // Adiciona listeners para fechar modais
    document.querySelectorAll('.close, .modal-close-btn').forEach(element => {
        element.addEventListener('click', closeModal);
    });

    /**
     * Renderiza a lista de empresas na sidebar
     */
    function renderCompanyList() {
        companyList.innerHTML = '';
        companies.forEach(company => {
            const companyItem = document.createElement('div');
            companyItem.className = 'company-item';
            companyItem.textContent = company.name;
            companyItem.addEventListener('click', () => showCompanyDetails(company.id));
            companyList.appendChild(companyItem);
        });
    }

    /**
     * Exibe os detalhes de uma empresa selecionada
     * @param {string} companyId - ID da empresa
     */
    function showCompanyDetails(companyId) {
        const company = companies.find(c => c.id == companyId);
        if (company) {
            currentCompanyId = companyId;
            document.getElementById('companyNameDisplay').textContent = company.name;
            document.getElementById('companyTradeNameDisplay').textContent = company.trade_name || 'N/A';
            document.getElementById('companyCnpjDisplay').textContent = company.cnpj || 'N/A';
            document.getElementById('companyEmailDisplay').textContent = company.email || 'N/A';
            document.getElementById('companyPhoneDisplay').textContent = company.phone || 'N/A';
            document.getElementById('companyAddressDisplay').textContent = `${company.address || ''}, ${company.city || ''}, ${company.state || ''} ${company.zip_code || ''}`;
            document.getElementById('companyWebsiteDisplay').textContent = company.website || 'N/A';
            document.getElementById('companyCreatedAtDisplay').textContent = new Date(company.created_at).toLocaleDateString();

            companyDetails.style.display = 'block';
            companyFormContainer.style.display = 'none';
            gsap.from(companyDetails, { opacity: 0, y: 20, duration: 0.5, ease: "power2.out" });
        }
    }

    /**
     * Exibe o formulário para adicionar uma nova empresa
     */
    function showAddCompanyForm() {
        companyForm.reset();
        document.getElementById('formTitle').textContent = 'Adicionar Nova Empresa';
        currentCompanyId = null;
        companyDetails.style.display = 'none';
        companyFormContainer.style.display = 'block';
        gsap.from(companyFormContainer, { opacity: 0, y: 20, duration: 0.5, ease: "power2.out" });
    }

    /**
     * Exibe o formulário para editar uma empresa existente
     * @param {string} companyId - ID da empresa a ser editada
     */
    function showEditCompanyForm(companyId) {
        const company = companies.find(c => c.id == companyId);
        if (company) {
            document.getElementById('formTitle').textContent = 'Editar Empresa';
            fillCompanyForm(company);
            companyDetails.style.display = 'none';
            companyFormContainer.style.display = 'block';
            gsap.from(companyFormContainer, { opacity: 0, y: 20, duration: 0.5, ease: "power2.out" });
        }
    }

    /**
     * Esconde o formulário e volta para a visualização de detalhes
     */
    function hideCompanyForm() {
        companyFormContainer.style.display = 'none';
        if (currentCompanyId) {
            showCompanyDetails(currentCompanyId);
        } else {
            companyDetails.style.display = 'block';
        }
    }

    /**
     * Salva ou atualiza uma empresa
     * @param {Event} e - Evento de submit do formulário
     */
    function saveCompany(e) {
        e.preventDefault();
        const companyData = {
            name: document.getElementById('companyName').value,
            trade_name: document.getElementById('companyTradeName').value,
            cnpj: document.getElementById('companyCnpj').value,
            email: document.getElementById('companyEmail').value,
            phone: document.getElementById('companyPhone').value,
            website: document.getElementById('companyWebsite').value,
            address: document.getElementById('companyAddress').value,
            city: document.getElementById('companyCity').value,
            state: document.getElementById('companyState').value,
            zip_code: document.getElementById('companyZipCode').value
        };

        const url = currentCompanyId
            ? `${BASE_URL}/companies/update/${currentCompanyId}`
            : `${BASE_URL}/companies/create`;

        fetch(url, {
            method: currentCompanyId ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(companyData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (currentCompanyId) {
                        const index = companies.findIndex(c => c.id == currentCompanyId);
                        companies[index] = { ...companies[index], ...companyData, id: currentCompanyId };
                    } else {
                        companies.push({ ...companyData, id: data.id, created_at: new Date().toISOString() });
                    }
                    renderCompanyList();
                    showCompanyDetails(currentCompanyId || data.id);
                    showNotification(currentCompanyId ? 'Empresa atualizada com sucesso!' : 'Empresa adicionada com sucesso!', 'success');
                } else {
                    showNotification('Erro ao salvar empresa: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification('Erro ao salvar empresa. Por favor, tente novamente.', 'error');
            });
    }

    /**
     * Confirma a exclusão de uma empresa
     */
    function confirmDeleteCompany() {
        showModal(deleteConfirmModal);
    }

    /**
     * Exclui uma empresa
     */
    function deleteCompany() {
        const url = `${BASE_URL}/companies/delete/${currentCompanyId}`;

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    companies = companies.filter(c => c.id != currentCompanyId);
                    renderCompanyList();
                    showCompanyDetails(companies[0]?.id);
                    showNotification('Empresa excluída com sucesso!', 'success');
                } else {
                    showNotification('Erro ao excluir empresa: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification('Erro ao excluir empresa. Por favor, tente novamente.', 'error');
            });
        closeModal();
    }

    /**
     * Busca os dados da empresa pelo CNPJ
     */
    function searchCompanyByCNPJ() {
        const cnpj = cnpjInput.value;
        fetch(`${BASE_URL}/companies/search-cnpj`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cnpj=${encodeURIComponent(cnpj)}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fillCompanyForm(data.data);
                    showNotification('Dados da empresa carregados com sucesso!', 'success');
                } else {
                    showNotification('Erro ao buscar dados da empresa: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification('Erro ao buscar dados da empresa. Por favor, tente novamente.', 'error');
            });
    }

    /**
     * Preenche o formulário com os dados da empresa
     * @param {Object} data - Dados da empresa
     */
    function fillCompanyForm(data) {
        document.getElementById('companyName').value = data.name || '';
        document.getElementById('companyTradeName').value = data.trade_name || '';
        document.getElementById('companyEmail').value = data.email || '';
        document.getElementById('companyPhone').value = data.phone || '';
        document.getElementById('companyWebsite').value = data.website || '';
        document.getElementById('companyAddress').value = data.address || '';
        document.getElementById('companyCity').value = data.city || '';
        document.getElementById('companyState').value = data.state || '';
        document.getElementById('companyZipCode').value = data.zip_code || '';
    }

    /**
     * Filtra as empresas com base no texto de busca
     */
    function filterCompanies() {
        const searchText = companySearch.value.toLowerCase();
        const filteredCompanies = companies.filter(company =>
            company.name.toLowerCase().includes(searchText) ||
            (company.cnpj && company.cnpj.includes(searchText))
        );
        renderFilteredCompanies(filteredCompanies);
    }

    /**
     * Renderiza a lista de empresas filtradas
     * @param {Array} filteredCompanies - Array de empresas filtradas
     */
    function renderFilteredCompanies(filteredCompanies) {
        companyList.innerHTML = '';
        filteredCompanies.forEach(company => {
            const companyItem = document.createElement('div');
            companyItem.className = 'company-item';
            companyItem.textContent = company.name;
            companyItem.addEventListener('click', () => showCompanyDetails(company.id));
            companyList.appendChild(companyItem);
        });
    }

    /**
     * Exibe uma notificação no modal
     * @param {string} message - Mensagem a ser exibida
     * @param {string} type - Tipo de notificação ('success' ou 'error')
     */
    function showNotification(message, type) {
        const title = document.getElementById('notificationTitle');
        const messageElement = document.getElementById('notificationMessage');
        const modalContent = notificationModal.querySelector('.modal-content');

        title.textContent = type === 'success' ? 'Sucesso' : 'Erro';
        messageElement.textContent = message;

        modalContent.className = 'modal-content ' + (type === 'success' ? 'success-modal-content' : 'error-modal-content');

        showModal(notificationModal);
    }

    /**
     * Exibe um modal
     * @param {HTMLElement} modal - Elemento do modal a ser exibido
     */
    function showModal(modal) {
        modal.style.display = 'block';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }

    /**
     * Fecha todos os modais abertos
     */
    function closeModal() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        });
    }

    // Fechar modal ao clicar fora dele
    window.onclick = function (event) {
        if (event.target.classList.contains('modal')) {
            closeModal();
        }
    }
});