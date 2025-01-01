// Arquivo JavaScript principal
console.log("VoiceHub JavaScript carregado");

// Aqui você pode adicionar qualquer funcionalidade JavaScript necessária para o seu aplicativo

// assets/js/app.js

document.addEventListener('DOMContentLoaded', function () {
    // Adicionar botão de gravação flutuante
    const mainContent = document.querySelector('.app-content');
    const recordBtn = document.createElement('a');
    recordBtn.href = '#';
    recordBtn.className = 'record-btn';
    recordBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    mainContent.appendChild(recordBtn);

    // Funcionalidade do botão de gravação (exemplo)
    recordBtn.addEventListener('click', function (e) {
        e.preventDefault();
        alert('Iniciar gravação...');
        // Aqui você implementaria a lógica real de gravação
    });

    // Adicionar funcionalidade de logout (exemplo)
    const logoutLink = document.querySelector('a[href*="logout"]');
    if (logoutLink) {
        logoutLink.addEventListener('click', function (e) {
            e.preventDefault();
            if (confirm('Tem certeza que deseja sair?')) {
                window.location.href = this.href;
            }
        });
    }
});