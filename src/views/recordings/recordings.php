<?php
// Inclui o cabeçalho padrão do site
include BASE_PATH . '/src/views/layouts/header.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    // Redireciona para a página de login se não estiver logado
    header("Location: " . BASE_URL . "/login");
    exit();
}

// Busca as gravações do banco de dados
// Nota: Esta parte deve ser movida para o controlador (RecordingsController)
$db = $GLOBALS['db'];
$stmt = $db->prepare("SELECT * FROM records ORDER BY created_at DESC");
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Inclui o CSS específico para a página de gravações -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/recordings.css">
<!-- Inclui as bibliotecas JavaScript necessárias -->
<script src="https://unpkg.com/wavesurfer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="recordings-wrapper">
    <div class="sidebar">
        <h2>Gravações</h2>
        <!-- Caixa de pesquisa para filtrar gravações -->
        <div class="search-box">
            <input type="text" id="searchRecordings" placeholder="Pesquisar gravações...">
            <i class="fas fa-search"></i>
        </div>
        <!-- Lista de gravações -->
        <div class="recordings-list">
            <?php if (!empty($records)): ?>
                <?php foreach ($records as $record): ?>
                    <div class="recording-item" data-id="<?php echo htmlspecialchars($record['id']); ?>">
                        <i class="fas fa-microphone"></i>
                        <div class="recording-info">
                            <h3>Gravação #<?php echo htmlspecialchars($record['id']); ?></h3>
                            <p><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($record['created_at']))); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nenhuma gravação encontrada.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-content">
        <div class="recording-details">
            <h2 id="recordingTitle">Selecione uma gravação</h2>
            <!-- Container para o visualizador de onda de áudio -->
            <div id="waveform"></div>
            <!-- Controles de áudio -->
            <div class="controls">
                <button id="playPause" class="btn"><i class="fas fa-play"></i> Play/Pause</button>
                <input type="range" id="volume" min="0" max="1" step="0.1" value="1">
            </div>
            <!-- Tabs para alternar entre transcrição e análise -->
            <div class="tabs">
                <button class="tab-btn active" data-tab="transcription"><i class="fas fa-file-alt"></i> Transcrição</button>
                <button class="tab-btn" data-tab="analysis"><i class="fas fa-chart-bar"></i> Análise</button>
            </div>
            <!-- Conteúdo da transcrição -->
            <div id="transcription" class="tab-content active">
                <div id="transcription-text"></div>
            </div>
            <!-- Conteúdo da análise -->
            <div id="analysis" class="tab-content">
                <div id="analysis-result"></div>
                <canvas id="sentimentChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Define a URL base para uso no JavaScript -->
<script>
    var BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<!-- Inclui o arquivo JavaScript específico para a página de gravações -->
<script src="<?php echo BASE_URL; ?>/assets/js/recordings.js"></script>

<?php
// Inclui o rodapé padrão do site
include BASE_PATH . '/src/views/layouts/footer.php';
?>