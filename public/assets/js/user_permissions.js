/**
 * Gerenciamento de Permissões de Usuários
 * 
 * Este script gerencia a interação do usuário na página de permissões,
 * incluindo a seleção de usuários, atualização de permissões e manipulação de modais.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Elementos do DOM
    const userList = document.getElementById('userListContainer');
    const permissionsForm = document.getElementById('permissionsForm');
    const permissionsPlaceholder = document.getElementById('permissionsPlaceholder');
    const selectedUserId = document.getElementById('selectedUserId');
    const userSearch = document.getElementById('userSearch');
    const saveConfirmModal = document.getElementById('saveConfirmModal');
    const unsavedChangesModal = document.getElementById('unsavedChangesModal');
    const notificationModal = document.getElementById('notificationModal');
    const updatedPermissionsList = document.getElementById('updatedPermissionsList');
    const verificationCodeContainer = document.getElementById('verificationCodeContainer');
    const verificationCodeInput = document.getElementById('verificationCode');
    const requestVerificationCodeBtn = document.getElementById('requestVerificationCodeBtn');
    const verificationCodeInputContainer = document.getElementById('verificationCodeInputContainer');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const saveButton = document.querySelector('.btn-save');
    const verifyCodeBtn = document.getElementById('verifyCodeBtn');

    // Variáveis de estado
    let originalPermissions = {};
    let isVerificationCodeRequested = false;
    let verificationCodeTimeout;
    let verifiedCode = null;

    // Configurações
    const simplePermissions = ['gravacoes', 'transcricoes', 'relatorio_inteligente'];
    const advancedPermissions = ['gerenciar_licencas', 'gerenciar_empresas', 'administrador_sistema'];
    let currentPage = 1;
    const usersPerPage = 5;
    let totalPages = 1;

    // Inicialização
    initializeEventListeners();
    displayUsers(currentPage);
    handleUserAvatars();

    /**
     * Inicializa todos os event listeners necessários
     */
    function initializeEventListeners() {
        if (userList) userList.addEventListener('click', handleUserClick);
        if (permissionsForm) permissionsForm.addEventListener('submit', handlePermissionSubmit);
        if (userSearch) userSearch.addEventListener('input', handleUserSearch);
        document.getElementById('continueWithoutSaving').addEventListener('click', continueWithoutSaving);
        document.getElementById('saveChanges').addEventListener('click', saveChanges);
        document.getElementById('cancelUnsavedChanges').addEventListener('click', cancelUnsavedChanges);
        document.getElementById('confirmSaveBtn').addEventListener('click', closeSaveConfirmModal);
        requestVerificationCodeBtn.addEventListener('click', requestVerificationCode);
        verificationCodeInput.addEventListener('input', handleVerificationCodeInput);
        verifyCodeBtn.addEventListener('click', verifyCode);
        document.getElementById('notificationOkBtn').addEventListener('click', closeNotificationModal);
        document.getElementById('prevPage').addEventListener('click', () => changePage(-1));
        document.getElementById('nextPage').addEventListener('click', () => changePage(1));

        // Event listeners para os botões de fechar modais
        document.querySelectorAll('.close, .modal-close-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const modal = this.closest('.modal');
                hideModal(modal.id);
            });
        });

        // Fechar modal clicando fora do conteúdo
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function (event) {
                if (event.target === this) {
                    hideModal(this.id);
                }
            });
        });
    }

    /**
     * Lida com o clique em um usuário na lista
     * @param {Event} e - O evento de clique
     */
    function handleUserClick(e) {
        const userItem = e.target.closest('.user-item');
        if (userItem) {
            if (hasUnsavedChanges()) {
                showModal('unsavedChangesModal');
            } else {
                requestAnimationFrame(() => selectUser(userItem));
            }
        }
    }

    /**
     * Seleciona um usuário e carrega suas permissões
     * @param {HTMLElement} userItem - O elemento do usuário clicado
     */
    function selectUser(userItem) {
        document.querySelectorAll('.user-item').forEach(item => item.classList.remove('active'));
        userItem.classList.add('active');
        selectedUserId.value = userItem.dataset.userId;
        animateUserTransition(() => fetchUserPermissions(userItem.dataset.userId));
    }

    /**
     * Anima a transição entre usuários
     * @param {Function} callback - Função a ser executada após a animação
     */
    function animateUserTransition(callback) {
        const permissionsPanel = document.querySelector('.permissions-panel');
        permissionsPanel.style.opacity = '0';
        permissionsPanel.style.transform = 'translateY(20px)';
        setTimeout(() => {
            callback();
            permissionsPanel.style.opacity = '1';
            permissionsPanel.style.transform = 'translateY(0)';
        }, 300);
    }

    /**
     * Busca as permissões do usuário selecionado
     * @param {string} userId - ID do usuário selecionado
     */
    function fetchUserPermissions(userId) {
        if (!userId) {
            console.error('User ID is not defined');
            return;
        }
        showLoading();
        fetch(`${BASE_URL}/admin/get-user-permissions/${userId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include'
        })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`Network response was not ok (${response.status}): ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    renderPermissions(data);
                    originalPermissions = { ...data.permissions };
                } else {
                    throw new Error(data.error || 'Erro desconhecido ao carregar permissões');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Erro ao carregar permissões do usuário: ' + error.message, 'error');
            })
            .finally(hideLoading);
    }

    /**
     * Renderiza as permissões na interface
     * @param {Object} data - Dados das permissões do usuário
     */
    function renderPermissions(data) {
        const simpleContainer = document.getElementById('simplePermissionsContainer');
        const advancedContainer = document.getElementById('advancedPermissionsContainer');
        simpleContainer.innerHTML = '<h3><i class="fas fa-check-circle"></i>Simples</h3>';
        advancedContainer.innerHTML = '<h3><i class="fas fa-star"></i>Avançadas</h3>';

        Object.entries(data.permissions).forEach(([key, value]) => {
            const item = document.createElement('div');
            item.className = 'permission-item';
            item.innerHTML = `
                <label class="toggle">
                    <input type="checkbox" name="permissions[]" value="${key}" ${value ? 'checked' : ''}>
                    <span class="slider"></span>
                    <span class="label">${data.labels[key]}</span>
                </label>
            `;

            if (simplePermissions.includes(key)) {
                simpleContainer.appendChild(item);
            } else if (advancedPermissions.includes(key)) {
                advancedContainer.appendChild(item);
            }
        });

        permissionsPlaceholder.style.display = 'none';
        permissionsForm.style.display = 'block';
        document.querySelector('.btn-save').style.display = 'block';
        document.getElementById('verificationCodeContainer').style.display = 'block';
        addPermissionChangeListeners();
        toggleSaveButton();
    }

    /**
     * Adiciona listeners de mudança nas permissões
     */
    function addPermissionChangeListeners() {
        document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                toggleSaveButton();
                animatePermissionChange(this);
            });
        });
    }

    /**
     * Anima a mudança de permissão
     * @param {HTMLElement} checkbox - O checkbox que foi alterado
     */
    function animatePermissionChange(checkbox) {
        const permissionItem = checkbox.closest('.permission-item');
        permissionItem.classList.add('permission-changed');
        setTimeout(() => {
            permissionItem.classList.remove('permission-changed');
        }, 300);
    }

    /**
     * Habilita/desabilita o botão de salvar
     */
    function toggleSaveButton() {
        const hasChanges = hasUnsavedChanges();
        saveButton.disabled = !hasChanges;
    }

    /**
     * Lida com o envio do formulário de permissões
     * @param {Event} e - O evento de submit
     */
    function handlePermissionSubmit(e) {
        e.preventDefault();
        const hasAdvancedPermission = advancedPermissions.some(permission =>
            document.querySelector(`input[value="${permission}"]`).checked
        );
        if (hasAdvancedPermission && !verifiedCode) {
            showNotification('É necessário um código de verificação para permissões avançadas.', 'warning');
            return;
        }
        updateUserPermissions();
    }

    /**
     * Atualiza as permissões do usuário
     */
    function updateUserPermissions() {
        const userId = selectedUserId.value;
        if (!userId) {
            showNotification('Por favor, selecione um usuário primeiro.', 'error');
            return;
        }
        const formData = new FormData(permissionsForm);

        if (verifiedCode) {
            formData.append('verification_code', verifiedCode);
        }

        showLoading();
        saveButton.disabled = true;
        fetch(`${BASE_URL}/admin/update-permissions`, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSaveConfirmModal(data.updatedPermissions);
                    originalPermissions = { ...data.updatedPermissions };
                    showNotification(data.message, 'success');
                    verificationCodeInput.value = '';
                    isVerificationCodeRequested = false;
                    verifiedCode = null;
                    toggleSaveButton();
                } else {
                    throw new Error(data.message || 'Erro desconhecido ao atualizar permissões');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Ocorreu um erro ao atualizar as permissões: ' + error.message, 'error');
            })
            .finally(() => {
                hideLoading();
                toggleSaveButton();
            });
    }

    /**
     * Solicita o código de verificação
     */
    function requestVerificationCode() {
        if (!confirm('Tem certeza que deseja solicitar um código de verificação? Um e-mail será enviado ao administrador.')) {
            return;
        }

        const userId = selectedUserId.value;
        if (!userId) {
            showNotification('Por favor, selecione um usuário primeiro.', 'error');
            return;
        }

        const hasAdvancedPermissionSelected = advancedPermissions.some(permission => {
            const checkbox = document.querySelector(`input[name="permissions[]"][value="${permission}"]`);
            return checkbox && checkbox.checked;
        });

        if (!hasAdvancedPermissionSelected) {
            showNotification('Por favor, selecione pelo menos uma permissão avançada antes de solicitar o código.', 'warning');
            return;
        }

        const formData = new FormData(permissionsForm);

        showLoading();
        fetch(`${BASE_URL}/admin/request-verification-code`, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Código de verificação enviado com sucesso. Verifique o e-mail do administrador.', 'success');
                    isVerificationCodeRequested = true;
                    verificationCodeInputContainer.style.display = 'block';
                    startVerificationCodeTimeout();
                } else {
                    throw new Error(data.message || 'Erro ao solicitar código de verificação');
                }
            })
            .catch(error => {
                console.error('Erro detalhado:', error);
                showNotification('Ocorreu um erro ao solicitar o código de verificação: ' + error.message, 'error');
            })
            .finally(() => {
                hideLoading();
                requestVerificationCodeBtn.style.display = 'none';
            });
    }

    /**
     * Lida com a entrada do código de verificação
     */
    function handleVerificationCodeInput() {
        toggleSaveButton();
    }

    /**
     * Verifica o código de verificação
     */
    function verifyCode() {
        const code = verificationCodeInput.value;
        const userId = selectedUserId.value;

        if (!code) {
            showNotification('Por favor, insira o código de verificação.', 'error');
            return;
        }

        showLoading();
        fetch(`${BASE_URL}/admin/verify-code`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId, code: code })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Código verificado com sucesso!', 'success');
                    verifiedCode = code;
                    verificationCodeInputContainer.style.display = 'none';
                    toggleSaveButton();
                } else {
                    showNotification(data.message || 'Código inválido.', 'error');
                    verifiedCode = null;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification('Erro ao verificar o código: ' + error.message, 'error');
                verifiedCode = null;
            })
            .finally(hideLoading);
    }

    /**
     * Lida com a pesquisa de usuários
     * @param {Event} e - O evento de input
     */
    function handleUserSearch(e) {
        const searchTerm = e.target.value.toLowerCase();
        const userItems = document.querySelectorAll('.user-item');

        userItems.forEach(item => {
            const userName = item.querySelector('.user-name').textContent.toLowerCase();
            const userEmail = item.querySelector('.user-email').textContent.toLowerCase();
            item.style.display = userName.includes(searchTerm) || userEmail.includes(searchTerm) ? 'flex' : 'none';
        });

        currentPage = 1;
        displayUsers(currentPage);
    }

    /**
     * Mostra uma notificação
     * @param {string} message - A mensagem a ser exibida
     * @param {string} type - O tipo de notificação (success, error, warning)
     */
    function showNotification(message, type) {
        const title = document.getElementById('notificationTitle');
        const messageElement = document.getElementById('notificationMessage');

        title.textContent = type.charAt(0).toUpperCase() + type.slice(1);
        messageElement.textContent = message;

        showModal('notificationModal');
    }

    /**
     * Verifica se há mudanças não salvas
     * @returns {boolean} - True se houver mudanças não salvas, false caso contrário
     */
    function hasUnsavedChanges() {
        const currentPermissions = {};
        document.querySelectorAll('#permissionsForm input[type="checkbox"]').forEach(checkbox => {
            currentPermissions[checkbox.value] = checkbox.checked;
        });
        return JSON.stringify(currentPermissions) !== JSON.stringify(originalPermissions);
    }

    /**
     * Continua sem salvar as alterações
     */
    function continueWithoutSaving() {
        hideModal('unsavedChangesModal');
        selectUser(document.querySelector('.user-item.active'));
    }

    /**
     * Salva as alterações
     */
    function saveChanges() {
        hideModal('unsavedChangesModal');
        updateUserPermissions();
    }

    /**
     * Cancela as alterações não salvas
     */
    function cancelUnsavedChanges() {
        hideModal('unsavedChangesModal');
    }

    /**
     * Mostra o modal de confirmação de salvamento
     * @param {Object} updatedPermissions - As permissões atualizadas
     */
    function showSaveConfirmModal(updatedPermissions) {
        updatedPermissionsList.innerHTML = '';
        Object.entries(updatedPermissions).forEach(([key, value]) => {
            const li = document.createElement('li');
            li.textContent = `${key}: ${value ? 'Ativado' : 'Desativado'}`;
            updatedPermissionsList.appendChild(li);
        });
        showModal('saveConfirmModal');
    }

    /**
     * Fecha o modal de confirmação de salvamento
     */
    function closeSaveConfirmModal() {
        hideModal('saveConfirmModal');
    }

    /**
     * Fecha o modal de notificação
     */
    function closeNotificationModal() {
        hideModal('notificationModal');
    }

    /**
     * Mostra o indicador de carregamento
     */
    function showLoading() {
        loadingIndicator.style.display = 'block';
    }

    /**
     * Esconde o indicador de carregamento
     */
    function hideLoading() {
        loadingIndicator.style.display = 'none';
    }

    /**
     * Inicia o timeout do código de verificação
     */
    function startVerificationCodeTimeout() {
        clearTimeout(verificationCodeTimeout);
        verificationCodeTimeout = setTimeout(() => {
            verificationCodeInput.value = '';
            isVerificationCodeRequested = false;
            verifiedCode = null;
            toggleSaveButton();
            showNotification('O código de verificação expirou. Por favor, solicite um novo.', 'warning');
        }, 10 * 60 * 1000); // 10 minutos
    }

    /**
     * Mostra um modal
     * @param {string} modalId - O ID do modal a ser exibido
     */
    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.style.display = 'block';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
        document.body.style.overflow = 'hidden'; // Previne rolagem do body
    }

    /**
     * Esconde um modal
     * @param {string} modalId - O ID do modal a ser escondido
     */
    function hideModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
        document.body.style.overflow = ''; // Restaura rolagem do body
    }

    /**
     * Lida com os avatares dos usuários
     */
    function handleUserAvatars() {
        document.querySelectorAll('.user-avatar').forEach(function (img) {
            img.addEventListener('error', function () {
                this.src = BASE_URL + '/assets/images/profile.png';
            });
        });
    }

    /**
     * Exibe os usuários da página atual
     * @param {number} page - O número da página atual
     */
    function displayUsers(page) {
        const userItems = document.querySelectorAll('.user-item');
        const startIndex = (page - 1) * usersPerPage;
        const endIndex = startIndex + usersPerPage;

        userItems.forEach((item, index) => {
            if (index >= startIndex && index < endIndex) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });

        updatePaginationInfo();
    }

    /**
     * Atualiza as informações de paginação
     */
    function updatePaginationInfo() {
        const totalUsers = document.querySelectorAll('.user-item').length;
        totalPages = Math.ceil(totalUsers / usersPerPage);

        document.getElementById('currentPage').textContent = currentPage;
        document.getElementById('totalPages').textContent = totalPages;

        document.getElementById('prevPage').disabled = currentPage === 1;
        document.getElementById('nextPage').disabled = currentPage === totalPages;
    }

    /**
     * Muda a página de usuários
     * @param {number} direction - A direção da mudança (-1 para anterior, 1 para próxima)
     */
    function changePage(direction) {
        currentPage += direction;
        if (currentPage < 1) currentPage = 1;
        if (currentPage > totalPages) currentPage = totalPages;
        displayUsers(currentPage);
    }

    // Inicialização de listeners adicionais
    initializeAdvancedPermissionListeners();
});

/**
 * Inicializa os listeners para as permissões avançadas
 */
function initializeAdvancedPermissionListeners() {
    document.querySelectorAll('#advancedPermissionsContainer input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const hasAdvancedPermissionSelected = advancedPermissions.some(permission => {
                const checkbox = document.querySelector(`input[name="permissions[]"][value="${permission}"]`);
                return checkbox && checkbox.checked;
            });

            const warningElement = document.getElementById('advancedPermissionsWarning');
            const requestVerificationCodeBtn = document.getElementById('requestVerificationCodeBtn');

            if (hasAdvancedPermissionSelected) {
                warningElement.style.display = 'none';
                requestVerificationCodeBtn.disabled = false;
            } else {
                warningElement.style.display = 'block';
                requestVerificationCodeBtn.disabled = true;
            }
        });
    });
}