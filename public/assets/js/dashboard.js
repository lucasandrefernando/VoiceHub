/**
 * dashboard.js
 * Este arquivo contém todas as funcionalidades JavaScript para o dashboard,
 * incluindo atualizações de estatísticas, interações do usuário e gerenciamento de modais.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Verificação inicial para garantir que BASE_URL está definido
    if (typeof BASE_URL === 'undefined') {
        console.error('BASE_URL não está definido. Verifique se ele está sendo passado corretamente do PHP para o JavaScript.');
        return;
    }

    // Elementos do DOM que serão utilizados
    const statCards = document.querySelectorAll('.stat-card');
    const adminButtons = document.querySelectorAll('.admin-btn');
    const adminCards = document.querySelectorAll('.admin-card');
    const permissionDeniedModalElement = document.getElementById('permissionDeniedModal');
    const permissionDeniedMessage = document.getElementById('permissionDeniedMessage');

    // Inicialização do modal de permissão negada
    const permissionDeniedModal = new bootstrap.Modal(permissionDeniedModalElement, {
        backdrop: 'static',
        keyboard: false
    });

    /**
     * Anima a mudança de valor de um elemento
     * @param {HTMLElement} element - O elemento a ser animado
     * @param {number} start - O valor inicial
     * @param {number} end - O valor final
     * @param {number} duration - A duração da animação em milissegundos
     */
    function animateValue(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            element.textContent = Math.floor(progress * (end - start) + start);
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }

    /**
     * Busca as estatísticas atualizadas do servidor
     */
    function fetchUpdatedStats() {
        fetch(`${BASE_URL}/dashboard/stats`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Resposta da rede não foi ok');
                }
                return response.json();
            })
            .then(data => {
                updateStatsDisplay(data);
            })
            .catch(error => {
                console.error('Erro ao buscar estatísticas:', error);
            });
    }

    /**
     * Atualiza a exibição das estatísticas na página
     * @param {Object} data - Os dados das estatísticas
     */
    function updateStatsDisplay(data) {
        statCards.forEach(card => {
            const statName = card.dataset.stat;
            const numberElement = card.querySelector('.stat-number');
            const currentValue = parseInt(numberElement.textContent);
            const newValue = data[statName];

            if (newValue !== undefined && newValue !== currentValue) {
                animateValue(numberElement, currentValue, newValue, 1000);
            }
        });
    }

    /**
     * Configura os eventos para os botões de administração
     */
    function setupAdminButtons() {
        adminButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                if (this.classList.contains('disabled')) {
                    e.preventDefault();
                    const feature = this.closest('.admin-card').querySelector('h4').textContent;
                    showPermissionDeniedModal(feature);
                }
            });
        });
    }

    /**
     * Adiciona efeitos de hover aos cards de administração
     */
    function setupAdminCards() {
        adminCards.forEach(card => {
            card.addEventListener('mouseenter', function () {
                this.style.transform = 'translateY(-10px)';
            });
            card.addEventListener('mouseleave', function () {
                this.style.transform = 'translateY(0)';
            });
        });
    }

    /**
     * Exibe o modal de permissão negada
     * @param {string} feature - O nome da funcionalidade que foi negada
     */
    function showPermissionDeniedModal(feature) {
        permissionDeniedMessage.textContent = `Você não tem permissão para acessar a área de ${feature}. Entre em contato com o administrador do sistema.`;
        permissionDeniedModal.show();
    }

    // Configuração para fechar o modal corretamente
    permissionDeniedModalElement.addEventListener('hidden.bs.modal', function () {
        document.body.classList.remove('modal-open');
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    });

    // Configuração do botão para fechar o modal
    const closeModalBtn = permissionDeniedModalElement.querySelector('.btn-secondary');
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function () {
            permissionDeniedModal.hide();
        });
    }

    // Inicialização das funcionalidades
    setupAdminButtons();
    setupAdminCards();

    // Busca as estatísticas iniciais e configura a atualização periódica
    fetchUpdatedStats();
    setInterval(fetchUpdatedStats, 30000); // Atualiza a cada 30 segundos
});

/**
 * Função global para mostrar o modal de permissão negada
 * Esta função é exposta globalmente para ser usada em atributos onclick no HTML
 * @param {string} feature - O nome da funcionalidade que foi negada
 */
window.showPermissionDeniedModal = function (feature) {
    const modalElement = document.getElementById('permissionDeniedModal');
    const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
    document.getElementById('permissionDeniedMessage').textContent =
        `Você não tem permissão para acessar a área de ${feature}. Entre em contato com o administrador do sistema.`;
    modal.show();
};