<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<h1>Detalhes da Gravação</h1>

<?php if (isset($recording)): ?>
    <div class="recording-details">
        <h2><?php echo htmlspecialchars($recording['filename']); ?></h2>
        <audio controls>
            <source src="<?php echo htmlspecialchars($recording['wavFile']); ?>" type="audio/wav">
            Seu navegador não suporta o elemento de áudio.
        </audio>
        <h3>Transcrição:</h3>
        <?php if ($recording['transcription'] === 'Transcrição em andamento...'): ?>
            <p>A transcrição está em andamento. Por favor, recarregue a página em alguns minutos.</p>
        <?php else: ?>
            <pre><?php echo htmlspecialchars($recording['transcription']); ?></pre>
        <?php endif; ?>

        <h3>Informações adicionais:</h3>
        <ul>
            <li>ID da gravação: <?php echo htmlspecialchars($recording['id']); ?></li>
            <li>Nome do arquivo original: <?php echo htmlspecialchars($recording['filename']); ?></li>
            <li>Caminho do arquivo WAV: <?php echo htmlspecialchars($recording['wavFile']); ?></li>
        </ul>
    </div>

    <a href="<?php echo BASE_URL . '/recordings'; ?>">Voltar para a lista de gravações</a>
<?php else: ?>
    <p>Gravação não encontrada.</p>
    <a href="<?php echo BASE_URL . '/recordings'; ?>">Voltar para a lista de gravações</a>
<?php endif; ?>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>