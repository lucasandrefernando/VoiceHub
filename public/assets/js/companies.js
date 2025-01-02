document.addEventListener('DOMContentLoaded', function () {
    // Elementos do DOM
    const addCompanyBtn = document.getElementById('addCompanyBtn');
    const companyModal = new bootstrap.Modal(document.getElementById('companyModal'));
    const companyForm = document.getElementById('companyForm');
    const saveCompanyBtn = document.getElementById('saveCompanyBtn');
    const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const searchCnpjBtn = document.getElementById('searchCnpjBtn');
    const cnpjInput = document.getElementById('companyCnpj');
    const BASE_URL = '/voicehub/public';

    // Variáveis globais
    let companies = initialCompanies || [];
    let currentCompanyId = null;

    // Inicialização
    renderCompaniesTable();

    // Event Listeners
    addCompanyBtn.addEventListener('click', () => openCompanyModal());
    saveCompanyBtn.addEventListener('click', saveCompany);
    confirmDeleteBtn.addEventListener('click', deleteCompany);
    searchCnpjBtn.addEventListener('click', searchCompanyByCNPJ);

    /**
     * Renderiza a tabela de empresas
     */
    function renderCompaniesTable() {
        const tableBody = document.querySelector('#companiesTable tbody');
        tableBody.innerHTML = '';
        companies.forEach(company => {
            const row = `
                <tr>
                    <td>${company.id}</td>
                    <td>${company.name}</td>
                    <td>${company.cnpj || ''}</td>
                    <td>${company.email || ''}</td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-btn" data-id="${company.id}">Editar</button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${company.id}">Excluir</button>
                    </td>
                </tr>
            `;
            tableBody.innerHTML += row;
        });

        // Adiciona event listeners para os botões de editar e excluir
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => openCompanyModal(e.target.dataset.id));
        });
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => openDeleteConfirmModal(e.target.dataset.id));
        });
    }

    /**
     * Abre o modal para adicionar ou editar uma empresa
     * @param {string|null} companyId - ID da empresa a ser editada, ou null para adicionar nova empresa
     */
    function openCompanyModal(companyId = null) {
        currentCompanyId = companyId;
        const modalTitle = document.getElementById('companyModalLabel');

        if (companyId) {
            modalTitle.textContent = 'Editar Empresa';
            const company = companies.find(c => c.id == companyId);
            fillCompanyForm(company);
        } else {
            modalTitle.textContent = 'Adicionar Empresa';
            companyForm.reset();
        }

        companyModal.show();
    }

    /**
     * Preenche o formulário com os dados da empresa
     * @param {Object} company - Objeto contendo os dados da empresa
     */
    function fillCompanyForm(company) {
        document.getElementById('companyName').value = company.name || '';
        document.getElementById('companyCnpj').value = company.cnpj || '';
        document.getElementById('companyEmail').value = company.email || '';
        document.getElementById('companyAddress').value = company.address || '';
        document.getElementById('companyCity').value = company.city || '';
        document.getElementById('companyState').value = company.state || '';
        document.getElementById('companyZipCode').value = company.zip_code || '';
        document.getElementById('companyPhone').value = company.phone || '';
    }

    /**
     * Salva uma nova empresa ou atualiza uma existente
     */
    function saveCompany() {
        const companyData = {
            name: document.getElementById('companyName').value,
            cnpj: document.getElementById('companyCnpj').value,
            email: document.getElementById('companyEmail').value,
            address: document.getElementById('companyAddress').value,
            city: document.getElementById('companyCity').value,
            state: document.getElementById('companyState').value,
            zip_code: document.getElementById('companyZipCode').value,
            phone: document.getElementById('companyPhone').value
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
                    companyModal.hide();
                    if (currentCompanyId) {
                        const index = companies.findIndex(c => c.id == currentCompanyId);
                        companies[index] = { ...companies[index], ...companyData, id: currentCompanyId };
                    } else {
                        companies.push({ ...companyData, id: data.id });
                    }
                    renderCompaniesTable();
                    alert(currentCompanyId ? 'Empresa atualizada com sucesso!' : 'Empresa adicionada com sucesso!');
                } else {
                    alert('Erro ao salvar empresa: ' + data.message);
                }
            })
            .catch(error => console.error('Erro:', error));
    }

    /**
     * Abre o modal de confirmação para excluir uma empresa
     * @param {string} companyId - ID da empresa a ser excluída
     */
    function openDeleteConfirmModal(companyId) {
        currentCompanyId = companyId;
        deleteConfirmModal.show();
    }

    /**
     * Exclui a empresa atualmente selecionada
     */
    function deleteCompany() {
        if (confirm('Tem certeza que deseja excluir esta empresa? Esta ação não pode ser desfeita.')) {
            console.log('Iniciando exclusão da empresa:', currentCompanyId);
            const url = `${BASE_URL}/companies/delete/${currentCompanyId}`;
            console.log('URL da requisição:', url);

            fetch(url, {
                method: 'POST', // Mudamos para POST porque alguns servidores não aceitam DELETE
                headers: {
                    'Content-Type': 'application/json',
                },
            })
                .then(response => {
                    console.log('Status da resposta:', response.status);
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Dados recebidos:', data);
                    if (data.success) {
                        companies = companies.filter(c => c.id != currentCompanyId);
                        renderCompaniesTable();
                        alert('Empresa excluída com sucesso!');
                    } else {
                        alert('Erro ao excluir empresa: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro detalhado ao excluir empresa:', error);
                    alert('Erro ao excluir empresa. Por favor, tente novamente. Detalhes: ' + error.message);
                });
        }
    }


    /**
     * Busca os dados da empresa pelo CNPJ usando a API
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
                } else {
                    alert('Erro ao buscar dados da empresa: ' + data.message);
                }
            })
            .catch(error => console.error('Erro:', error));
    }
});