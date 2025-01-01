<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<h1 class="mb-4"><?php echo htmlspecialchars($recording['title']); ?></h1>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Detalhes da Gravação</h5>
        <p class="card-text">Gravado por: <?php echo htmlspecialchars($recording['user_name']); ?></p>
        <p class="card-text">Data: <?php echo date('d/m/Y H:i', strtotime($recording['created_at'])); ?></p>

        <h6>Áudio:</h6>
        <audio controls>
            <source src="<?php echo BASE_URL . '/uploads/' . $recording['file_path']; ?>" type="audio/mpeg">
            Seu navegador não suporta o elemento de áudio.
        </audio>

        <h6 class="mt-4">Transcrição:</h6>
        <p><?php echo nl2br(htmlspecialchars($recording['transcription'])); ?></p>

        <h6 class="mt-4">Insights da IA:</h6>
        <p><?php echo nl2br(htmlspecialchars($recording['ai_analysis'])); ?></p>
    </div>
</div>

<a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-secondary">Voltar para o Dashboard</a>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>