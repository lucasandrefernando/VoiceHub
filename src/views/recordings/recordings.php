<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<h1>Gravações</h1>

<div id="recordings-list">
    <?php if (empty($recordings)): ?>
        <p>Nenhuma gravação encontrada.</p>
    <?php else: ?>
        <?php foreach ($recordings as $recording): ?>
            <div class="recording-item">
                <h2><?php echo htmlspecialchars($recording['filename']); ?></h2>
                <audio controls>
                    <source src="<?php echo BASE_URL . $recording['wavFile']; ?>" type="audio/wav">
                    Seu navegador não suporta o elemento de áudio.
                </audio>
                <h3>Transcrição:</h3>
                <?php if ($recording['transcription'] === 'Transcrição em andamento...'): ?>
                    <p>A transcrição está em andamento. Por favor, recarregue a página em alguns minutos.</p>
                <?php else: ?>
                    <pre><?php echo htmlspecialchars($recording['transcription']); ?></pre>
                <?php endif; ?>
                <a href="<?php echo BASE_URL . '/recording/' . $recording['id']; ?>">Ver detalhes</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>