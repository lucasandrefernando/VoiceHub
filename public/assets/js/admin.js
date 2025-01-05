document.addEventListener('DOMContentLoaded', function () {
    console.log('Admin dashboard loaded');

    // Configuração do canvas para o efeito de fundo
    const canvas = document.getElementById('backgroundCanvas');
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    // Configurações do efeito de onda
    const waveCount = 3;
    const waves = [];

    // Inicialização das ondas
    for (let i = 0; i < waveCount; i++) {
        waves.push({
            frequency: 0.01 + Math.random() * 0.01,
            amplitude: 50 + Math.random() * 50,
            offset: Math.random() * Math.PI * 2,
            speed: 0.02 + Math.random() * 0.02,
            color: `rgba(0, 0, 255, ${0.1 + i * 0.05})` // Azul com opacidade variável
        });
    }

    // Função para desenhar o efeito de onda
    function drawWaves() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        waves.forEach(wave => {
            ctx.beginPath();
            ctx.moveTo(0, canvas.height / 2);

            for (let x = 0; x < canvas.width; x++) {
                const y = Math.sin(x * wave.frequency + wave.offset) * wave.amplitude + canvas.height / 2;
                ctx.lineTo(x, y);
            }

            ctx.strokeStyle = wave.color;
            ctx.stroke();

            wave.offset += wave.speed;
        });

        requestAnimationFrame(drawWaves);
    }

    // Iniciar a animação do fundo
    drawWaves();

    // Redimensionar o canvas quando a janela for redimensionada
    window.addEventListener('resize', function () {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    });

    // Animações GSAP para os elementos da página
    gsap.from(".dashboard-title", {
        duration: 1,
        y: -50,
        opacity: 0,
        ease: "power3.out"
    });

    // Definir opacidade inicial dos cards para 0 e depois revelá-los
    gsap.set(".admin-card", { opacity: 0 });
    gsap.to(".admin-card", {
        duration: 0.8,
        opacity: 1,
        stagger: 0.2,
        ease: "power3.out"
    });

    // Efeito hover para os cards
    document.querySelectorAll('.admin-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            gsap.to(card, {
                duration: 0.3,
                y: -10,
                boxShadow: "0 15px 30px rgba(0,0,0,0.2)",
                ease: "power2.out"
            });
        });

        card.addEventListener('mouseleave', () => {
            gsap.to(card, {
                duration: 0.3,
                y: 0,
                boxShadow: "0 4px 6px rgba(0,0,0,0.1)",
                ease: "power2.out"
            });
        });
    });

    // Função para animar os números
    function animateValue(obj, start, end, duration) {
        gsap.to(obj, {
            duration: duration,
            innerText: end,
            snap: { innerText: 1 },
            ease: "power1.inOut"
        });
    }

    // Animar os números iniciais
    const statNumbers = document.querySelectorAll('.stat-number span');
    statNumbers.forEach(number => {
        const finalValue = parseInt(number.innerText);
        animateValue(number, 0, finalValue, 2);
    });

    // Função para atualizar as estatísticas em tempo real
    function updateStats() {
        fetch(BASE_URL + '/admin/get-stats')
            .then(response => response.json())
            .then(data => {
                updateStatValue('totalUsers', data.totalUsers);
                updateStatValue('totalCompanies', data.totalCompanies);
                updateStatValue('activeLicenses', data.activeLicenses);
            })
            .catch(error => console.error('Error:', error));
    }
 
    // Função para atualizar um valor estatístico específico
    function updateStatValue(statId, newValue) {
        const statElement = document.getElementById(statId);
        if (statElement) {
            const currentValue = parseInt(statElement.innerText);
            animateValue(statElement, currentValue, newValue, 1);
        }
    }

    // Atualizar estatísticas a cada 30 segundos
    setInterval(updateStats, 30000);
});