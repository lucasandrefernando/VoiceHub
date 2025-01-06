// Espera o DOM ser completamente carregado antes de executar o script
document.addEventListener('DOMContentLoaded', function () {
    // Elementos do DOM
    const profilePicture = document.getElementById('profilePicture');
    const profilePictureInput = document.getElementById('profilePictureInput');
    const editProfilePicture = document.getElementById('editProfilePicture');
    const removeProfilePicture = document.getElementById('removeProfilePicture');
    const updateProfileForm = document.getElementById('updateProfileForm');
    const changePasswordForm = document.getElementById('changePasswordForm');
    const currentPasswordInput = document.getElementById('currentPassword');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const passwordStrength = document.getElementById('passwordStrength');
    const passwordCriteria = document.querySelectorAll('#passwordCriteria li');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const genderInputs = document.querySelectorAll('input[name="gender"]');

    // Elementos do modal
    const modal = document.getElementById('alertModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessageList = document.getElementById('modalMessageList');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalCloseX = document.getElementsByClassName('close')[0];

    // Variável para armazenar o gênero original
    let originalGender = document.querySelector('input[name="gender"]:checked').value;

    // Event Listeners
    profilePicture.addEventListener('click', () => profilePictureInput.click());
    editProfilePicture.addEventListener('click', (e) => {
        e.stopPropagation();
        profilePictureInput.click();
    });
    removeProfilePicture.addEventListener('click', handleRemoveProfilePicture);
    profilePictureInput.addEventListener('change', handleProfilePictureChange);
    updateProfileForm.addEventListener('submit', handleUpdateProfile);
    changePasswordForm.addEventListener('submit', handleChangePassword);
    newPasswordInput.addEventListener('input', updatePasswordStrength);
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);

    // Adiciona event listeners para os inputs de gênero
    genderInputs.forEach(input => {
        input.addEventListener('change', () => {
            console.log('Gênero alterado para:', input.value);
        });
    });

    // Configura os toggles de visibilidade da senha
    ['currentPassword', 'newPassword', 'confirmPassword'].forEach(id => {
        const input = document.getElementById(id);
        const toggle = document.getElementById(id + 'Toggle');
        toggle.addEventListener('click', () => togglePasswordVisibility(input, toggle));
    });

    // Event listeners do modal
    modalCloseBtn.onclick = closeModal;
    modalCloseX.onclick = closeModal;
    window.onclick = (event) => {
        if (event.target == modal) closeModal();
    };

    // Função para remover a foto de perfil
    function handleRemoveProfilePicture(e) {
        e.preventDefault();
        if (confirm('Tem certeza que deseja remover sua foto de perfil?')) {
            fetch(`${BASE_URL}/user/remove-profile-picture`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        profilePicture.src = `${BASE_URL}/assets/images/profile.png`;
                        showSuccessModal(['Foto de perfil']);
                    } else {
                        showModal('Erro', 'Erro ao remover a foto de perfil');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showModal('Erro', 'Ocorreu um erro ao remover a foto de perfil');
                });
        }
    }

    // Função para lidar com a mudança da foto de perfil
    function handleProfilePictureChange() {
        if (this.files && this.files[0]) {
            const formData = new FormData();
            formData.append('profile_picture', this.files[0]);

            fetch(`${BASE_URL}/user/update-profile-picture`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        profilePicture.src = data.photoPath + '?t=' + new Date().getTime();
                        showSuccessModal(['Foto de perfil']);
                    } else {
                        showModal('Erro', 'Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showModal('Erro', 'Ocorreu um erro ao atualizar a foto de perfil');
                });
        }
    }

    // Função para lidar com a atualização do perfil
    function handleUpdateProfile(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const updatedFields = [];

        // Compara os valores atuais com os originais para determinar quais campos foram alterados
        if (formData.get('name') !== this.elements['name'].defaultValue) updatedFields.push("Nome");
        if (formData.get('cpf') !== this.elements['cpf'].defaultValue) updatedFields.push("CPF");

        // Verifica se o gênero foi alterado
        const currentGender = formData.get('gender');
        console.log('Gênero atual:', currentGender);
        console.log('Gênero original:', originalGender);
        if (currentGender !== originalGender) {
            updatedFields.push("Gênero");
        }

        fetch(`${BASE_URL}/user/update-profile`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal(updatedFields);
                    // Atualiza os valores padrão dos campos após uma atualização bem-sucedida
                    this.elements['name'].defaultValue = formData.get('name');
                    this.elements['cpf'].defaultValue = formData.get('cpf');
                    originalGender = currentGender; // Atualiza o gênero original
                } else {
                    showModal('Erro', 'Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showModal('Erro', 'Ocorreu um erro ao atualizar o perfil');
            });
    }

    // Função para lidar com a alteração de senha
    function handleChangePassword(e) {
        e.preventDefault();
        if (newPasswordInput.value !== confirmPasswordInput.value) {
            showModal('Erro', 'As senhas não coincidem');
            return;
        }
        const formData = new FormData(this);

        fetch(`${BASE_URL}/user/update-password`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal(['Senha'], true);
                    this.reset();
                    passwordStrength.style.width = '0';
                    passwordStrength.className = '';
                    passwordCriteria.forEach(criterion => criterion.classList.remove('met'));
                    changePasswordBtn.disabled = true;
                } else {
                    showModal('Erro', 'Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showModal('Erro', 'Ocorreu um erro ao alterar a senha');
            });
    }

    // Função para atualizar a força da senha
    function updatePasswordStrength() {
        const password = newPasswordInput.value;
        const strength = calculatePasswordStrength(password);

        passwordStrength.style.width = strength + '%';
        if (strength < 40) {
            passwordStrength.style.backgroundColor = '#FF4136';
        } else if (strength < 80) {
            passwordStrength.style.backgroundColor = '#FFDC00';
        } else {
            passwordStrength.style.backgroundColor = '#2ECC40';
        }

        updatePasswordCriteria(password);
        checkPasswordMatch();
    }

    // Função para calcular a força da senha
    function calculatePasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength += 20;
        if (password.match(/[A-Z]/)) strength += 20;
        if (password.match(/[a-z]/)) strength += 20;
        if (password.match(/[0-9]/)) strength += 20;
        if (password.match(/[^A-Za-z0-9]/)) strength += 20;
        return strength;
    }

    // Função para atualizar os critérios de senha
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

        changePasswordBtn.disabled = !criteria.every(criterion => password.match(criterion.regex));
    }

    // Função para verificar se as senhas coincidem
    function checkPasswordMatch() {
        const password = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        if (password === confirmPassword && password !== '') {
            confirmPasswordInput.style.borderColor = '#2ECC40';
        } else {
            confirmPasswordInput.style.borderColor = '#FF4136';
        }
        changePasswordBtn.disabled = password !== confirmPassword || password === '';
    }

    // Função para alternar a visibilidade da senha
    function togglePasswordVisibility(input, toggle) {
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        toggle.classList.toggle('fa-eye');
        toggle.classList.toggle('fa-eye-slash');
    }

    // Função para exibir o modal de sucesso
    function showSuccessModal(updatedFields, isPasswordChange = false) {
        modalTitle.textContent = 'Atualização bem-sucedida';
        modalMessageList.innerHTML = '';

        if (updatedFields.length === 0) {
            const li = document.createElement('li');
            li.textContent = 'Nenhuma alteração foi feita.';
            modalMessageList.appendChild(li);
        } else {
            updatedFields.forEach(field => {
                const li = document.createElement('li');
                li.textContent = `${field} atualizado(a) com sucesso.`;
                modalMessageList.appendChild(li);
            });
        }

        modal.style.display = 'block';
        modal.querySelector('.modal-content').classList.add('success-animation');

        if (isPasswordChange) {
            const logoutMessage = document.createElement('p');
            logoutMessage.textContent = 'Você será desconectado para aplicar as alterações de senha.';
            modalMessageList.appendChild(logoutMessage);

            setTimeout(() => {
                window.location.href = `${BASE_URL}/logout`;
            }, 3000);
        }
    }

    // Função para exibir o modal de erro
    function showModal(title, message) {
        modalTitle.textContent = title;
        modalMessageList.innerHTML = `<li>${message}</li>`;
        modal.style.display = 'block';
    }

    // Função para fechar o modal
    function closeModal() {
        modal.style.display = 'none';
        modal.querySelector('.modal-content').classList.remove('success-animation');
    }
}); 