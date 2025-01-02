// Espera que o DOM esteja completamente carregado antes de executar o script
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
    const DEBUG_MODE = false; // Defina como true quando precisar debugar

    // Variáveis para armazenar dados
    let originalPermissions = {};
    let isVerificationCodeRequested = false;
    let verificationCodeTimeout;
    let verifiedCode = null;

    // Definição das permissões simples e avançadas
    const simplePermissions = ['gravacoes', 'transcricoes', 'relatorio_inteligente'];
    const advancedPermissions = ['gerenciar_licencas', 'gerenciar_empresas', 'administrador_sistema'];

    // Fila de modais
    const modalQueue = [];
    let isModalOpen = false;

    // Event Listeners
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

    // Função para animar a transição entre usuários
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

    // Função para lidar com o clique em um usuário na lista
    function handleUserClick(e) {
        const userItem = e.target.closest('.user-item');
        if (userItem) {
            if (hasUnsavedChanges()) {
                showModal(unsavedChangesModal);
            } else {
                requestAnimationFrame(() => selectUser(userItem));
            }
        }
    }

    // Função para selecionar um usuário
    function selectUser(userItem) {
        document.querySelectorAll('.user-item').forEach(item => item.classList.remove('active'));
        userItem.classList.add('active');
        selectedUserId.value = userItem.dataset.userId;
        animateUserTransition(() => fetchUserPermissions(userItem.dataset.userId));
    }

    // Função para buscar as permissões do usuário selecionado
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

    // Função para renderizar as permissões na interface
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

    // Função para adicionar listeners de mudança nas permissões
    function addPermissionChangeListeners() {
        document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                toggleSaveButton();
                animatePermissionChange(this);
            });
        });
    }

    // Função para animar a mudança de permissão
    function animatePermissionChange(checkbox) {
        const permissionItem = checkbox.closest('.permission-item');
        permissionItem.classList.add('permission-changed');
        setTimeout(() => {
            permissionItem.classList.remove('permission-changed');
        }, 300);
    }

    // Função para habilitar/desabilitar o botão de salvar
    function toggleSaveButton() {
        const hasChanges = hasUnsavedChanges();
        saveButton.disabled = !hasChanges;
    }

    // Função para lidar com o envio do formulário de permissões
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

    // Função para atualizar as permissões do usuário
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

    // Função para solicitar o código de verificação
    function requestVerificationCode() {
        if (!confirm('Tem certeza que deseja solicitar um código de verificação? Um e-mail será enviado ao administrador.')) {
            return;
        }

        const userId = selectedUserId.value;
        if (!userId) {
            showNotification('Por favor, selecione um usuário primeiro.', 'error');
            return;
        }

        // Verificar se alguma permissão avançada foi selecionada
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

    // Função para lidar com a entrada do código de verificação
    function handleVerificationCodeInput() {
        toggleSaveButton();
    }

    // Função para verificar o código
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

    // Função para lidar com a pesquisa de usuários
    function handleUserSearch(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('.user-item').forEach(item => {
            const userName = item.querySelector('.user-name').textContent.toLowerCase();
            const userEmail = item.querySelector('.user-email').textContent.toLowerCase();
            item.style.display = userName.includes(searchTerm) || userEmail.includes(searchTerm) ? '' : 'none';
        });
    }

    // Função para mostrar notificações
    function showNotification(message, type) {
        const title = document.getElementById('notificationTitle');
        const messageElement = document.getElementById('notificationMessage');

        title.textContent = type.charAt(0).toUpperCase() + type.slice(1);
        messageElement.textContent = message;

        showModal(notificationModal);
    }

    // Função para verificar se há mudanças não salvas
    function hasUnsavedChanges() {
        const currentPermissions = {};
        document.querySelectorAll('#permissionsForm input[type="checkbox"]').forEach(checkbox => {
            currentPermissions[checkbox.value] = checkbox.checked;
        });
        return JSON.stringify(currentPermissions) !== JSON.stringify(originalPermissions);
    }

    // Função para continuar sem salvar as alterações
    function continueWithoutSaving() {
        hideModal(unsavedChangesModal);
        selectUser(document.querySelector('.user-item.active'));
    }

    // Função para salvar as alterações
    function saveChanges() {
        hideModal(unsavedChangesModal);
        updateUserPermissions();
    }

    // Função para cancelar as alterações não salvas
    function cancelUnsavedChanges() {
        hideModal(unsavedChangesModal);
    }

    // Função para mostrar o modal de confirmação de salvamento
    function showSaveConfirmModal(updatedPermissions) {
        updatedPermissionsList.innerHTML = '';
        Object.entries(updatedPermissions).forEach(([key, value]) => {
            const li = document.createElement('li');
            li.textContent = `${key}: ${value ? 'Ativado' : 'Desativado'}`;
            updatedPermissionsList.appendChild(li);
        });
        showModal(saveConfirmModal);
    }

    // Função para fechar o modal de confirmação de salvamento
    function closeSaveConfirmModal() {
        hideModal(saveConfirmModal);
    }

    // Função para fechar o modal de notificação
    function closeNotificationModal() {
        hideModal(notificationModal);
    }

    // Função para mostrar o indicador de carregamento
    function showLoading() {
        loadingIndicator.style.display = 'block';
    }

    // Função para esconder o indicador de carregamento
    function hideLoading() {
        loadingIndicator.style.display = 'none';
    }

    // Função para iniciar o timeout do código de verificação
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

    // Função para adicionar efeito de ripple aos botões
    function addRippleEffect(button) {
        button.addEventListener('click', function (e) {
            let ripple = document.createElement('span');
            ripple.classList.add('ripple');
            this.appendChild(ripple);
            let x = e.clientX - e.target.offsetLeft;
            let y = e.clientY - e.target.offsetTop;
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            setTimeout(() => {
                ripple.remove();
            }, 300);
        });
    }

    // Adicionar efeito de ripple aos botões
    addRippleEffect(saveButton);
    addRippleEffect(requestVerificationCodeBtn);
    addRippleEffect(verifyCodeBtn);

    // Função para mostrar modal
    function showModal(modal) {
        modalQueue.push(modal);
        if (!isModalOpen) {
            displayNextModal();
        }
    }

    // Função para exibir o próximo modal na fila
    function displayNextModal() {
        if (modalQueue.length === 0) {
            isModalOpen = false;
            return;
        }

        isModalOpen = true;
        const modal = modalQueue.shift();
        modal.style.display = 'block';
    }

    // Função para esconder modal
    function hideModal(modal) {
        modal.style.display = 'none';
        displayNextModal();
    }

    // Adicionar event listeners para as checkboxes de permissões avançadas
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

    // Função para lidar com os avatares dos usuários
    function handleUserAvatars() {
        document.querySelectorAll('.user-avatar').forEach(function (img) {
            if (DEBUG_MODE) {
                console.log('Avatar src:', img.src);
                console.log('Original src:', img.dataset.originalSrc);
            }
            img.addEventListener('error', function () {
                if (DEBUG_MODE) {
                    console.log('Failed to load:', this.src);
                }
                this.src = BASE_URL + '/assets/images/profile.png';
            });
        });
    }

    // Chamar a função para lidar com os avatares dos usuários
    handleUserAvatars();
});

// Variáveis de paginação
let currentPage = 1;
const usersPerPage = 5;
let totalPages = 1;

// Função para exibir os usuários da página atual
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

// Função para atualizar as informações de paginação
function updatePaginationInfo() {
    const totalUsers = document.querySelectorAll('.user-item').length;
    totalPages = Math.ceil(totalUsers / usersPerPage);

    document.getElementById('currentPage').textContent = currentPage;
    document.getElementById('totalPages').textContent = totalPages;

    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = currentPage === totalPages;
}

// Event listeners para os botões de paginação
document.getElementById('prevPage').addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        displayUsers(currentPage);
    }
});

document.getElementById('nextPage').addEventListener('click', () => {
    if (currentPage < totalPages) {
        currentPage++;
        displayUsers(currentPage);
    }
});

// Chamar displayUsers no carregamento inicial
document.addEventListener('DOMContentLoaded', function () {
    displayUsers(currentPage);
    // ... (resto do seu código existente)
});

// Atualizar a função de busca para resetar a paginação
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

// Atualizar o event listener de busca
if (userSearch) userSearch.addEventListener('input', handleUserSearch);