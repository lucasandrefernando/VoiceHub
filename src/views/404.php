<?php
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(dirname(__DIR__)));
if (!defined('BASE_URL')) define('BASE_URL', '/voicehub/public');

include BASE_PATH . '/src/views/layouts/header.php';
?>

<div class="error-container">
    <div class="error-content">
        <h1 class="error-code">404</h1>
        <div class="terminal">
            <p class="error-message" id="typingMessage"></p>
        </div>
        <button onclick="history.back()" class="btn-back">Voltar</button>
    </div>
</div>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/404.css">
<script src="<?php echo BASE_URL; ?>/assets/js/404.js"></script>

<?php
include BASE_PATH . '/src/views/layouts/footer.php';
?>