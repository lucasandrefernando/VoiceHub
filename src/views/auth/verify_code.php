<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<h2>Verificar Código</h2>

<?php if (isset($error)): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<?php if (isset($success)): ?>
    <p style="color: green;"><?php echo $success; ?></p>
<?php endif; ?>

<form action="<?php echo BASE_URL; ?>/verify-code" method="post">
    <div>
        <label for="verification_code">Código de Verificação:</label>
        <input type="text" id="verification_code" name="verification_code" required>
    </div>
    <div>
        <button type="submit">Verificar</button>
    </div>
</form>

<p><a href="<?php echo BASE_URL; ?>/resend-verification-code">Reenviar código de verificação</a></p>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>