document.addEventListener('DOMContentLoaded', function () {
    // Adiciona a opacidade ao container após um pequeno atraso para garantir a animação
    setTimeout(() => document.querySelector('.glass-container').style.opacity = '1', 100);

    // Elementos do DOM
    const form = document.getElementById('resetPasswordForm');
    const emailStep = document.getElementById('emailStep');
    const codeStep = document.getElementById('codeStep');
    const passwordStep = document.getElementById('passwordStep');
    const sendEmailBtn = document.getElementById('sendEmailBtn');
    const verifyCodeBtn = document.getElementById('verifyCodeBtn');
    const resetPasswordBtn = document.getElementById('resetPasswordBtn');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const passwordStrength = document.getElementById('passwordStrength');
    const passwordCriteria = document.getElementById('passwordCriteria').getElementsByTagName('li');

    // Elementos do modal
    const modal = document.getElementById('alertModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalCloseX = document.getElementsByClassName('close')[0];

    let userEmail = '';

    // Event listeners
    sendEmailBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const email = document.getElementById('email').value;
        userEmail = email;
        sendResetEmail(email);
    });

    verifyCodeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const code = document.getElementById('verificationCode').value;
        verifyResetCode(code);
    });

    resetPasswordBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (newPassword !== confirmPassword) {
            showModal('Erro', 'As senhas não coincidem');
            return;
        }

        completePasswordReset(newPassword);
    });

    newPasswordInput.addEventListener('input', function () {
        const password = this.value;
        updatePasswordStrength(password);
        updatePasswordCriteria(password);
        checkPasswordMatch();
    });

    confirmPasswordInput.addEventListener('input', checkPasswordMatch);

    // Adiciona toggle de visibilidade para campos de senha
    ['newPassword', 'confirmPassword'].forEach(id => {
        const input = document.getElementById(id);
        const toggle = document.getElementById(id + 'Toggle');
        toggle.addEventListener('click', () => togglePasswordVisibility(input, toggle));
    });

    modalCloseBtn.onclick = closeModal;
    modalCloseX.onclick = closeModal;
    window.onclick = function (event) {
        if (event.target == modal) {
            closeModal();
        }
    };

    // Funções
    function sendResetEmail(email) {
        console.log('Enviando email de redefinição para:', email);
        sendRequest('sendEmail', { email: email })
            .then(data => {
                console.log('Resposta do email de redefinição:', data);
                if (data.success) {
                    emailStep.style.display = 'none';
                    codeStep.style.display = 'block';
                    showModal('Sucesso', data.message);
                } else {
                    showModal('Erro', data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao enviar email de redefinição:', error);
                showModal('Erro', 'Ocorreu um erro ao enviar o email: ' + error.message);
            });
    }

    function verifyResetCode(code) {
        console.log('Verificando código de redefinição:', code);
        sendRequest('verifyCode', { code: code, email: userEmail })
            .then(data => {
                console.log('Resposta da verificação do código:', data);
                if (data.success) {
                    codeStep.style.display = 'none';
                    passwordStep.style.display = 'block';
                    showModal('Sucesso', data.message);
                } else {
                    showModal('Erro', data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao verificar código de redefinição:', error);
                showModal('Erro', 'Ocorreu um erro ao verificar o código: ' + error.message);
            });
    }

    function completePasswordReset(newPassword) {
        console.log('Completando a redefinição de senha');
        sendRequest('resetPassword', { newPassword: newPassword, email: userEmail })
            .then(data => {
                console.log('Resposta da redefinição de senha:', data);
                if (data.success) {
                    showModal('Sucesso', data.message);
                    setTimeout(() => {
                        window.location.href = BASE_URL + '/login';
                    }, 3000);
                } else {
                    showModal('Erro', data.message);
                }
            })
            .catch(error => {
                console.error('Erro ao redefinir senha:', error);
                showModal('Erro', 'Ocorreu um erro ao redefinir a senha: ' + error.message);
            });
    }

    function sendRequest(action, data) {
        const formData = new URLSearchParams();
        formData.append('action', action);
        for (let key in data) {
            formData.append(key, data[key]);
        }

        return fetch(BASE_URL + '/reset-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
            .then(response => response.text())
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    console.error('Erro ao analisar JSON:', error);
                    console.error('Resposta bruta:', text);
                    throw new Error('Resposta inválida do servidor: ' + text);
                }
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Erro desconhecido ocorreu');
                }
                return data;
            });
    }

    function showModal(title, message) {
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        modal.style.display = 'block';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }

    function closeModal() {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }

    function updatePasswordStrength(password) {
        const strength = calculatePasswordStrength(password);
        passwordStrength.style.width = strength + '%';
        passwordStrength.style.backgroundColor = getStrengthColor(strength);
    }

    function calculatePasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength += 20;
        if (password.match(/[A-Z]/)) strength += 20;
        if (password.match(/[a-z]/)) strength += 20;
        if (password.match(/[0-9]/)) strength += 20;
        if (password.match(/[^A-Za-z0-9]/)) strength += 20;
        return strength;
    }

    function getStrengthColor(strength) {
        if (strength < 40) return '#FF4136';
        if (strength < 80) return '#FFDC00';
        return '#2ECC40';
    }

    function updatePasswordCriteria(password) {
        const criteria = [
            { id: 'lengthCriteria', regex: /.{8,}/ },
            { id: 'upperCriteria', regex: /[A-Z]/ },
            { id: 'lowerCriteria', regex: /[a-z]/ },
            { id: 'numberCriteria', regex: /[0-9]/ },
            { id: 'specialCriteria', regex: /[^A-Za-z0-9]/ }
        ];

        criteria.forEach((criterion, index) => {
            if (password.match(criterion.regex)) {
                passwordCriteria[index].classList.add('met');
            } else {
                passwordCriteria[index].classList.remove('met');
            }
        });

        resetPasswordBtn.disabled = !criteria.every(criterion => password.match(criterion.regex));
    }

    function checkPasswordMatch() {
        const password = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        if (password === confirmPassword && password !== '') {
            confirmPasswordInput.style.borderColor = '#2ECC40';
        } else {
            confirmPasswordInput.style.borderColor = '#FF4136';
        }
        resetPasswordBtn.disabled = password !== confirmPassword || password === '';
    }

    function togglePasswordVisibility(input, toggle) {
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        toggle.classList.toggle('fa-eye');
        toggle.classList.toggle('fa-eye-slash');
    }

    // Criação dos nós de conexão
    function createConnectionNodes() {
        const nodesContainer = document.querySelector('.connection-nodes');
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

    createConnectionNodes();
});