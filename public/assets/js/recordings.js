document.addEventListener('DOMContentLoaded', function () {
    const recordingsList = document.querySelector('.recordings-list');
    const recordingDetails = document.querySelector('.recording-details');
    const recordingTitle = document.getElementById('recordingTitle');
    const transcriptionText = document.getElementById('transcription-text');
    const analysisResult = document.getElementById('analysis-result');
    const playPauseBtn = document.getElementById('playPause');
    const volumeSlider = document.getElementById('volume');
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const searchInput = document.getElementById('searchRecordings');

    let wavesurfer;
    let currentRecordingId;

    // Inicializar WaveSurfer
    wavesurfer = WaveSurfer.create({
        container: '#waveform',
        waveColor: '#4a9eff',
        progressColor: '#1e90ff',
        responsive: true,
        cursorColor: '#333',
        barWidth: 2,
        barRadius: 3,
        cursorWidth: 1,
        height: 100,
        barGap: 3
    });

    // Event listeners
    recordingsList.addEventListener('click', handleRecordingClick);
    playPauseBtn.addEventListener('click', togglePlayPause);
    volumeSlider.addEventListener('input', handleVolumeChange);
    searchInput.addEventListener('input', handleSearch);

    tabBtns.forEach(btn => {
        btn.addEventListener('click', handleTabClick);
    });

    // Funções de manipulação de eventos
    function handleRecordingClick(e) {
        const recordingItem = e.target.closest('.recording-item');
        if (recordingItem) {
            const recordingId = recordingItem.dataset.id;
            loadRecordingDetails(recordingId);
        }
    }

    function togglePlayPause() {
        wavesurfer.playPause();
        const icon = playPauseBtn.querySelector('i');
        icon.classList.toggle('fa-play');
        icon.classList.toggle('fa-pause');
    }

    function handleVolumeChange() {
        wavesurfer.setVolume(volumeSlider.value);
    }

    function handleTabClick() {
        const tabName = this.dataset.tab;
        tabBtns.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));
        this.classList.add('active');
        document.getElementById(tabName).classList.add('active');

        if (tabName === 'analysis' && currentRecordingId) {
            analyzeRecording(currentRecordingId);
        }
    }

    function handleSearch() {
        const searchTerm = searchInput.value.toLowerCase();
        const recordingItems = recordingsList.querySelectorAll('.recording-item');

        recordingItems.forEach(item => {
            const title = item.querySelector('h3').textContent.toLowerCase();
            const date = item.querySelector('p').textContent.toLowerCase();
            if (title.includes(searchTerm) || date.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Funções principais
    function loadRecordingDetails(id) {
        currentRecordingId = id;
        fetch(`${BASE_URL}/recordings/${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Erro:', data.error);
                    return;
                }
                recordingTitle.textContent = `Gravação #${id}`;
                transcriptionText.textContent = data.transcription;
                wavesurfer.load(data.audio_file_uri);
            })
            .catch(error => console.error('Erro:', error));
    }

    function analyzeRecording(id) {
        analysisResult.innerHTML = '<div class="loading">Analisando... <i class="fas fa-spinner fa-spin"></i></div>';
        fetch(`${BASE_URL}/recordings/analyze`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Erro:', data.error);
                    analysisResult.innerHTML = `<div class="error">Erro na análise: ${data.error}</div>`;
                    return;
                }
                displayAnalysisResult(data);
            })
            .catch(error => {
                console.error('Erro:', error);
                analysisResult.innerHTML = '<div class="error">Erro ao realizar a análise.</div>';
            });
    }

    function displayAnalysisResult(data) {
        analysisResult.innerHTML = `
            <h3>Resumo da Análise</h3>
            <p>${data.analysis}</p>
            <h3>Pontos-chave</h3>
            <ul>
                ${data.keyPoints.map(point => `<li>${point}</li>`).join('')}
            </ul>
            <h3>Sugestões de Melhoria</h3>
            <ul>
                ${data.suggestions.map(suggestion => `<li>${suggestion}</li>`).join('')}
            </ul>
        `;
        createSentimentChart(data.sentiment);
    }

    function createSentimentChart(sentiment) {
        const ctx = document.getElementById('sentimentChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Positivo', 'Neutro', 'Negativo'],
                datasets: [{
                    data: [sentiment.positive, sentiment.neutral, sentiment.negative],
                    backgroundColor: ['#2ecc71', '#3498db', '#e74c3c']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: 'Análise de Sentimento',
                    fontSize: 18
                },
                legend: {
                    position: 'bottom'
                }
            }
        });
    }
});