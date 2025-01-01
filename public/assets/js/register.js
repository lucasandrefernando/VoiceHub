document.addEventListener('DOMContentLoaded', function () {
    // Elementos do DOM
    const form = document.getElementById('registerForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const email = document.getElementById('email');
    const photoInput = document.getElementById('photoInput');
    const profilePreview = document.getElementById('profilePreview');
    const cpfInput = document.getElementById('cpf');
    const termsCheckbox = document.getElementById('terms');
    const termsLink = document.getElementById('termsLink');
    const privacyLink = document.getElementById('privacyLink');
    const nameInput = document.getElementById('name');
    const surnameInput = document.getElementById('surname');
    const companySelect = document.getElementById('company_id');

    // Elementos dos modais
    const termsModal = document.getElementById('termsModal');
    const errorModal = document.getElementById('errorModal');
    const successModal = document.getElementById('successModal');
    const emailExistsModal = document.getElementById('emailExistsModal');
    const termsModalContent = document.getElementById('termsModalContent');
    const termsModalTitle = document.getElementById('termsModalTitle');
    const closeButtons = document.getElementsByClassName('close');
    const modalCloseButtons = document.querySelectorAll('.modal-close-btn');

    // Inicialização
    initializeCPFMask();
    addEventListeners();
    createConnectionNodes();

    // Função para inicializar a máscara do CPF
    function initializeCPFMask() {
        if (typeof Inputmask !== 'undefined' && cpfInput) {
            Inputmask({ "mask": "999.999.999-99" }).mask(cpfInput);
        } else {
            console.error('Inputmask não está definido ou o campo CPF não foi encontrado.');
        }
    }

    // Função para adicionar todos os event listeners necessários
    function addEventListeners() {
        if (photoInput) photoInput.addEventListener('change', handlePhotoUpload);
        if (form) form.addEventListener('submit', handleFormSubmit);
        if (termsLink) termsLink.addEventListener('click', (e) => openModal(e, 'terms'));
        if (privacyLink) privacyLink.addEventListener('click', (e) => openModal(e, 'privacy'));

        Array.from(closeButtons).forEach(btn => {
            if (btn) btn.addEventListener('click', closeModal);
        });

        modalCloseButtons.forEach(btn => {
            if (btn) btn.addEventListener('click', closeModal);
        });

        window.addEventListener('click', (event) => {
            if (event.target.classList.contains('modal')) {
                closeModal();
            }
        });

        // Adiciona toggle de visibilidade para campos de senha
        ['password', 'confirm_password'].forEach(id => {
            const input = document.getElementById(id);
            const toggle = document.getElementById(id + 'Toggle');
            if (input && toggle) {
                toggle.addEventListener('click', () => togglePasswordVisibility(input, toggle));
            }
        });

        // Adiciona evento de clique para o label do checkbox
        const termsLabel = document.querySelector('.terms-checkbox label');
        if (termsLabel) {
            termsLabel.addEventListener('click', toggleCheckbox);
        }
    }

    // Função para alternar o estado do checkbox
    function toggleCheckbox(event) {
        event.preventDefault();
        const checkbox = document.getElementById('terms');
        if (checkbox) {
            checkbox.checked = !checkbox.checked;
            const checkmark = document.querySelector('.terms-checkbox .checkmark');
            if (checkmark) {
                checkmark.classList.toggle('checked', checkbox.checked);
            }
        }
    }

    // Função para lidar com o upload de foto
    function handlePhotoUpload(event) {
        const file = event.target.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                showErrorModal(['O arquivo é muito grande. Por favor, escolha uma imagem menor que 5MB.']);
                return;
            }
            if (!file.type.startsWith('image/')) {
                showErrorModal(['Por favor, selecione apenas arquivos de imagem.']);
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                if (profilePreview) profilePreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }

    // Função para lidar com o envio do formulário
    function handleFormSubmit(event) {
        event.preventDefault();
        const errors = validateForm();
        if (errors.length > 0) {
            showErrorModal(errors);
        } else {
            const formData = new FormData(form);

            // Garantir que a foto seja incluída no FormData
            const photoFile = photoInput.files[0];
            if (photoFile) {
                formData.set('photo', photoFile);
            }

            // Log do conteúdo do FormData
            console.log("Conteúdo do FormData:");
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            fetch(BASE_URL + '/register', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    console.log("Resposta do servidor:", data);
                    if (data.success) {
                        showSuccessModal(data.message);
                    } else {
                        if (data.message.includes("Este e-mail já está cadastrado")) {
                            showEmailExistsModal(data.message);
                        } else {
                            showErrorModal([data.message]);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal(['Ocorreu um erro ao processar o registro. Por favor, tente novamente.']);
                });
        }
    }

    // Função para exibir o modal de e-mail existente
    function showEmailExistsModal(message) {
        if (emailExistsModal) {
            const messageElement = document.getElementById('emailExistsMessage');
            if (messageElement) messageElement.textContent = message;
            emailExistsModal.style.display = 'block';
            setTimeout(() => {
                emailExistsModal.classList.add('show');
                const content = emailExistsModal.querySelector('.modal-content');
                if (content) content.classList.add('email-exists-modal-content');
            }, 10);
            document.body.style.overflow = 'hidden';

            const recoverPasswordBtn = document.getElementById('recoverPasswordBtn');
            const stayOnPageBtn = document.getElementById('stayOnPageBtn');

            if (recoverPasswordBtn) {
                recoverPasswordBtn.addEventListener('click', () => {
                    window.location.href = BASE_URL + '/reset-password';
                });
            }

            if (stayOnPageBtn) {
                stayOnPageBtn.addEventListener('click', closeModal);
            }
        }
    }

    // Função para validar o formulário
    function validateForm() {
        const errors = [];
        if (nameInput && !nameInput.value.trim()) errors.push('O campo Nome é obrigatório.');
        if (surnameInput && !surnameInput.value.trim()) errors.push('O campo Sobrenome é obrigatório.');
        if (email) {
            if (!email.value.trim()) {
                errors.push('O campo Email é obrigatório.');
            } else if (!isValidEmail(email.value)) {
                errors.push('Por favor, insira um endereço de e-mail válido.');
            }
        }
        if (password) {
            if (!password.value) {
                errors.push('O campo Senha é obrigatório.');
            } else if (!isStrongPassword(password.value)) {
                errors.push('A senha deve ter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas, números e símbolos.');
            }
        }
        if (password && confirmPassword && password.value !== confirmPassword.value) {
            errors.push('As senhas não coincidem.');
        }
        if (companySelect && !companySelect.value) errors.push('Por favor, selecione uma empresa.');
        if (termsCheckbox && !termsCheckbox.checked) {
            errors.push('Você deve aceitar os Termos de Serviço e a Política de Privacidade.');
        }
        return errors;
    }

    // Função para validar o formato do email
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Função para verificar se a senha é forte
    function isStrongPassword(password) {
        return password.length >= 8 &&
            /[A-Z]/.test(password) &&
            /[a-z]/.test(password) &&
            /\d/.test(password) &&
            /[^A-Za-z0-9]/.test(password);
    }

    // Função para alternar a visibilidade da senha
    function togglePasswordVisibility(input, toggle) {
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        toggle.classList.toggle('fa-eye');
        toggle.classList.toggle('fa-eye-slash');
    }

    // Função para abrir o modal de termos ou política de privacidade
    function openModal(e, type) {
        e.preventDefault();
        if (termsModal && termsModalContent && termsModalTitle) {
            const content = document.getElementById(type + 'Content');
            if (content) {
                termsModalTitle.textContent = type === 'terms' ? 'Termos de Serviço' : 'Política de Privacidade';
                termsModalContent.innerHTML = content.innerHTML;
                termsModal.style.display = 'block';
                setTimeout(() => {
                    termsModal.classList.add('show');
                    const modalContent = termsModal.querySelector('.modal-content');
                    if (modalContent) modalContent.classList.add('terms-modal-content');
                }, 10);
                document.body.style.overflow = 'hidden';
            }
        }
    }

    // Função para mostrar o modal de erro
    function showErrorModal(errors) {
        if (errorModal) {
            const errorList = document.getElementById('errorList');
            if (errorList) {
                errorList.innerHTML = '';
                errors.forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error;
                    errorList.appendChild(li);
                });
            }
            errorModal.style.display = 'block';
            setTimeout(() => {
                errorModal.classList.add('show');
                const content = errorModal.querySelector('.modal-content');
                if (content) content.classList.add('error-modal-content');
            }, 10);
            document.body.style.overflow = 'hidden';
        }
    }

    // Função para mostrar o modal de sucesso
    function showSuccessModal(message) {
        if (successModal) {
            const successMessage = document.getElementById('successMessage');
            if (successMessage) successMessage.textContent = message;
            successModal.style.display = 'block';
            setTimeout(() => {
                successModal.classList.add('show');
                const content = successModal.querySelector('.modal-content');
                if (content) content.classList.add('success-modal-content');
            }, 10);
            document.body.style.overflow = 'hidden';

            const closeBtn = successModal.querySelector('.modal-close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () {
                    closeModal();
                    window.location.href = BASE_URL + '/login';
                });
            }
        }
    }

    // Função para fechar todos os modais abertos
    function closeModal() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                const modalContent = modal.querySelector('.modal-content');
                if (modalContent) {
                    modalContent.classList.remove('error-modal-content', 'success-modal-content', 'email-exists-modal-content', 'terms-modal-content');
                }
            }, 300);
        });
        document.body.style.overflow = 'auto';
    }

    // Função para criar nós de conexão (efeito visual)
    function createConnectionNodes() {
        const nodesContainer = document.querySelector('.connection-nodes');
        if (nodesContainer) {
            const nodeCount = 50;
            for (let i = 0; i < nodeCount; i++) {
                const node = document.createElement('div');
                node.classList.add('node');
                node.style.left = `${Math.random() * 100}%`;
                node.style.top = `${Math.random() * 100}%`;
                node.style.animationDelay = `${Math.random() * 5}s`;
                nodesContainer.appendChild(node);
            }
        }
    }
});