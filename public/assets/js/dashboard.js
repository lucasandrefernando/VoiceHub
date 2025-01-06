/**
 * dashboard.js
 * Este arquivo contém todas as funcionalidades JavaScript para o dashboard,
 * incluindo atualizações de estatísticas, interações do usuário e efeitos visuais de fundo.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Verificação inicial para garantir que BASE_URL está definido
    if (typeof BASE_URL === 'undefined') {
        console.error('BASE_URL não está definido. Verifique se ele está sendo passado corretamente do PHP para o JavaScript.');
        return;
    }

    // Elementos do DOM que serão utilizados
    const statCards = document.querySelectorAll('.stat-card');
    const canvas = document.getElementById('backgroundCanvas');
    const ctx = canvas.getContext('2d');

    // Configurações do efeito de fundo
    const gears = [];
    const equations = [];
    const graphs = [];

    // Paleta de cores mais vibrante, mas ainda sutil
    const colors = [
        'rgba(41, 128, 185, 0.6)',  // Azul
        'rgba(192, 57, 43, 0.6)',   // Vermelho
        'rgba(39, 174, 96, 0.6)',   // Verde
        'rgba(243, 156, 18, 0.6)',  // Amarelo
        'rgba(142, 68, 173, 0.6)'   // Roxo
    ];

    /**
     * Função para redimensionar o canvas
     */
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        initializeVisualElements();
    }

    /**
     * Classe Gear para criar engrenagens
     */
    class Gear {
        constructor(x, y, radius, teeth, color) {
            this.x = x;
            this.y = y;
            this.radius = radius;
            this.teeth = teeth;
            this.color = color;
            this.rotation = 0;
            this.speed = Math.random() * 0.02 + 0.01;
        }

        draw() {
            ctx.save();
            ctx.translate(this.x, this.y);
            ctx.rotate(this.rotation);
            ctx.beginPath();
            for (let i = 0; i < this.teeth; i++) {
                const angle = (i / this.teeth) * Math.PI * 2;
                const innerRadius = this.radius * 0.8;
                const outerRadius = this.radius;
                ctx.lineTo(Math.cos(angle) * innerRadius, Math.sin(angle) * innerRadius);
                ctx.lineTo(Math.cos(angle + 0.1) * outerRadius, Math.sin(angle + 0.1) * outerRadius);
            }
            ctx.closePath();
            ctx.fillStyle = this.color;
            ctx.fill();
            ctx.strokeStyle = 'rgba(0, 0, 0, 0.2)';
            ctx.stroke();
            ctx.restore();
            this.rotation += this.speed;
        }
    }

    /**
     * Classe Equation para criar cálculos matemáticos
     */
    class Equation {
        constructor(x, y) {
            this.x = x;
            this.y = y;
            this.equation = this.generateEquation();
            this.color = colors[Math.floor(Math.random() * colors.length)];
            this.fontSize = Math.random() * 6 + 14; // Tamanho de fonte entre 14 e 20
        }

        generateEquation() {
            const operators = ['+', '-', '*', '/'];
            const a = Math.floor(Math.random() * 10);
            const b = Math.floor(Math.random() * 10);
            const operator = operators[Math.floor(Math.random() * operators.length)];
            return `${a} ${operator} ${b} = ?`;
        }

        draw() {
            ctx.font = `${this.fontSize}px Arial`;
            ctx.fillStyle = this.color;
            ctx.fillText(this.equation, this.x, this.y);
        }
    }

    /**
     * Classe Graph para criar gráficos oscilantes
     */
    class Graph {
        constructor(x, y, width, height) {
            this.x = x;
            this.y = y;
            this.width = width;
            this.height = height;
            this.points = [];
            this.maxPoints = 50;
            this.color = colors[Math.floor(Math.random() * colors.length)];
        }

        update() {
            if (this.points.length >= this.maxPoints) {
                this.points.shift();
            }
            this.points.push(Math.sin(Date.now() * 0.01) * this.height / 2 + this.height / 2);
        }

        draw() {
            ctx.beginPath();
            ctx.moveTo(this.x, this.y + this.height / 2);
            for (let i = 0; i < this.points.length; i++) {
                ctx.lineTo(this.x + (i / this.maxPoints) * this.width, this.y + this.points[i]);
            }
            ctx.strokeStyle = this.color;
            ctx.lineWidth = 2;
            ctx.stroke();
        }
    }

    /**
     * Inicializa os elementos visuais
     */
    function initializeVisualElements() {
        gears.length = 0;
        equations.length = 0;
        graphs.length = 0;

        // Criar engrenagens
        for (let i = 0; i < 7; i++) {
            gears.push(new Gear(
                Math.random() * canvas.width,
                Math.random() * canvas.height,
                Math.random() * 30 + 20,
                Math.floor(Math.random() * 10) + 5,
                colors[Math.floor(Math.random() * colors.length)]
            ));
        }

        // Criar equações
        for (let i = 0; i < 15; i++) {
            equations.push(new Equation(
                Math.random() * canvas.width,
                Math.random() * canvas.height
            ));
        }

        // Criar gráficos
        for (let i = 0; i < 3; i++) {
            graphs.push(new Graph(
                Math.random() * (canvas.width - 300),
                Math.random() * (canvas.height - 100),
                300,
                100
            ));
        }
    }

    /**
     * Função para desenhar o fundo dinâmico
     */
    function drawBackground() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        gears.forEach(gear => gear.draw());
        equations.forEach(eq => eq.draw());
        graphs.forEach(graph => {
            graph.update();
            graph.draw();
        });

        requestAnimationFrame(drawBackground);
    }

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

    // Inicialização
    resizeCanvas();
    initializeVisualElements();
    drawBackground();

    // Event Listeners
    window.addEventListener('resize', resizeCanvas);

    // Busca as estatísticas iniciais e configura a atualização periódica
    fetchUpdatedStats();
    setInterval(fetchUpdatedStats, 30000); // Atualiza a cada 30 segundos
}); 