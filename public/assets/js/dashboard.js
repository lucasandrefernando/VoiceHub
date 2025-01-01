document.addEventListener('DOMContentLoaded', function () {
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
            .then(response => response.json())
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

    // Adiciona efeitos de hover aos cards de estatísticas
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-10px)';
        });
        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
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

    // Busca as estatísticas iniciais e configura a atualização periódica
    fetchUpdatedStats();
    setInterval(fetchUpdatedStats, 30000); // Atualiza a cada 30 segundos
});