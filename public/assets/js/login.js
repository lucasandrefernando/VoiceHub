// Espera que o DOM esteja completamente carregado antes de executar o script
document.addEventListener('DOMContentLoaded', function () {
    // Adiciona a opacidade ao container após um pequeno atraso para garantir a animação
    setTimeout(() => document.querySelector('.login-content').style.opacity = '1', 100);

    // Seleciona o formulário de login
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        // Adiciona um listener para o evento de submit do formulário
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Previne o comportamento padrão de submit do formulário
            if (validateForm()) { // Valida o formulário antes de prosseguir
                login(); // Chama a função de login se a validação passar
            }
        });
    }

    // Configuração do modal
    const modal = document.getElementById('messageModal');
    const closeBtn = modal.querySelector('.close');
    const modalCloseButton = document.getElementById('modalCloseButton');

    // Adiciona eventos para fechar o modal
    closeBtn.onclick = closeModal;
    modalCloseButton.onclick = closeModal;
    window.onclick = function (event) {
        if (event.target == modal) {
            closeModal();
        }
    }

    // Adiciona evento de clique para o botão de reenviar código
    const resendCodeBtn = document.getElementById('resendCodeBtn');
    if (resendCodeBtn) {
        resendCodeBtn.addEventListener('click', function (e) {
            e.preventDefault(); // Previne o comportamento padrão do botão
            resendVerificationCode();
        });
    }

    // Adiciona toggle de visibilidade para o campo de senha
    const passwordToggle = document.querySelector('.password-toggle');
    if (passwordToggle) {
        passwordToggle.addEventListener('click', togglePassword);
    }
});

// Função para validar o formulário antes do envio
function validateForm() {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();

    if (email === '' && password === '') {
        showModal('Campos Vazios', 'Por favor, preencha o e-mail e a senha.', 'error');
        return false;
    } else if (email === '') {
        showModal('E-mail Vazio', 'Por favor, preencha o e-mail.', 'error');
        return false;
    } else if (password === '') {
        showModal('Senha Vazia', 'Por favor, preencha a senha.', 'error');
        return false;
    }

    return true;
}

// Função para realizar o login
function login() {
    const form = document.getElementById('loginForm');
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect; // Redireciona se o login for bem-sucedido
                }
            } else if (data.requiresVerification) {
                // Mostra o campo de verificação se necessário
                document.getElementById('verificationCodeField').style.display = 'block';
                document.getElementById('resendCodeBtn').style.display = 'inline-block';
                showModal('Verificação Necessária', 'Por favor, insira o código de verificação enviado para o seu e-mail.', 'info');
            } else {
                showModal('Erro de Login', data.message || 'Ocorreu um erro ao processar a solicitação.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showModal('Erro de Conexão', 'Ocorreu um erro ao processar a solicitação. Por favor, tente novamente.', 'error');
        });
}

// Função para reenviar o código de verificação
function resendVerificationCode() {
    console.log("Função resendVerificationCode chamada");
    const email = document.getElementById('email').value;
    console.log("Email:", email);

    fetch(BASE_URL + '/resend-verification', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ email: email })
    })
        .then(response => {
            console.log("Resposta recebida:", response);
            return response.json();
        })
        .then(data => {
            console.log("Dados recebidos:", data);
            if (data.success) {
                showModal('Código Reenviado', 'Um novo código de verificação foi enviado para o seu e-mail.', 'success');
            } else {
                showModal('Erro', data.message || 'Não foi possível reenviar o código de verificação.', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showModal('Erro', 'Ocorreu um erro ao reenviar o código de verificação.', 'error');
        });
}

// Função para mostrar o modal
function showModal(title, message, type = 'error') {
    const modal = document.getElementById('messageModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalIcon = document.getElementById('modalIcon');

    modalTitle.textContent = title;
    modalMessage.textContent = message;

    // Definir ícone baseado no tipo de mensagem
    if (type === 'error') {
        modalIcon.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #d32f2f;"></i>';
    } else if (type === 'success') {
        modalIcon.innerHTML = '<i class="fas fa-check-circle" style="color: #388e3c;"></i>';
    } else {
        modalIcon.innerHTML = '<i class="fas fa-info-circle" style="color: #1976d2;"></i>';
    }

    modal.style.display = 'block';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
}

// Função para fechar o modal
function closeModal() {
    const modal = document.getElementById('messageModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// Função para alternar a visibilidade da senha
function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleBtn = document.querySelector('.password-toggle i');

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleBtn.classList.remove('fa-eye');
        toggleBtn.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleBtn.classList.remove('fa-eye-slash');
        toggleBtn.classList.add('fa-eye');
    }
}