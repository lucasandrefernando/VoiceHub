document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.convert-btn').forEach(button => {
        button.addEventListener('click', function () {
            const recordingItem = this.closest('.recording-item');
            const filename = recordingItem.dataset.filename;

            this.disabled = true;
            this.textContent = 'Convertendo...';

            fetch(`${BASE_URL}/convert`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `filename=${encodeURIComponent(filename)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.textContent = 'Transcrever';
                        this.classList.remove('convert-btn');
                        this.classList.add('transcribe-btn');
                    } else {
                        alert('Erro: ' + data.message);
                        this.textContent = 'Converter';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao converter o arquivo');
                    this.textContent = 'Converter';
                })
                .finally(() => {
                    this.disabled = false;
                });
        });
    });

    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('transcribe-btn')) {
            const recordingItem = e.target.closest('.recording-item');
            const filename = recordingItem.dataset.filename;
            const transcriptDiv = recordingItem.querySelector('.transcript');

            e.target.disabled = true;
            e.target.textContent = 'Transcrevendo...';

            fetch(`${BASE_URL}/transcribe`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `filename=${encodeURIComponent(filename)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        transcriptDiv.textContent = data.transcript;
                        transcriptDiv.style.display = 'block';
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao transcrever o arquivo');
                })
                .finally(() => {
                    e.target.disabled = false;
                    e.target.textContent = 'Transcrever';
                });
        }
    });
});