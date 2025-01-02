document.addEventListener('DOMContentLoaded', function () {
    // Verifica se BASE_URL está definido
    if (typeof BASE_URL === 'undefined') {
        console.error('BASE_URL não está definido. Verifique se ele está sendo passado corretamente do PHP para o JavaScript.');
        return;
    }

    /**
     * Anima a mudança de valor de um elemento
     * @param {HTMLElement} obj - O elemento a ser animado
     * @param {number} start - O valor inicial
     * @param {number} end - O valor final
     * @param {number} duration - A duração da animação em milissegundos
     */
    function animateValue(obj, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            obj.textContent = Math.floor(progress * (end - start) + start);
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
        fetch(BASE_URL + '/dashboard/stats')
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
        const statCards = document.querySelectorAll('.stat-card');
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

    // Adiciona evento de clique aos botões de administração
    const adminButtons = document.querySelectorAll('.admin-btn');
    adminButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            if (this.classList.contains('disabled')) {
                e.preventDefault();
                const permission = this.getAttribute('data-permission');
                const permissionName = permission === 'gerenciar_empresas' ? 'Gerenciar Empresas' : 'Gerenciar Licenças';

                permissionDeniedMessage.textContent = `Você não tem permissão para acessar a área de ${permissionName}. Por favor, entre em contato com o administrador do sistema.`;
                permissionDeniedModal.show();
            } else {
                // Se não estiver desabilitado, permite o comportamento padrão do link
                return true;
            }
        });
    });
    // Adiciona efeitos de hover aos cards de administração
    const adminCards = document.querySelectorAll('.admin-card');
    adminCards.forEach(card => {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-10px)';
        });
        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
        });
    });

    // Configuração do modal de permissão negada
    const permissionDeniedModalElement = document.getElementById('permissionDeniedModal');
    const permissionDeniedModal = new bootstrap.Modal(permissionDeniedModalElement);
    const permissionDeniedMessage = document.getElementById('permissionDeniedMessage');

    // Adiciona evento de clique aos botões desabilitados
    const disabledButtons = document.querySelectorAll('.admin-btn.disabled');
    disabledButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const permission = this.getAttribute('data-permission');
            const permissionName = permission === 'gerenciar_empresas' ? 'Gerenciar Empresas' : 'Gerenciar Licenças';

            permissionDeniedMessage.textContent = `Você não tem permissão para acessar a área de ${permissionName}. Por favor, entre em contato com o administrador do sistema.`;
            permissionDeniedModal.show();
        });
    });

    // Busca as estatísticas iniciais e configura a atualização periódica
    fetchUpdatedStats();
    setInterval(fetchUpdatedStats, 30000); // Atualiza a cada 30 segundos
});