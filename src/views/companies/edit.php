<?php

/**
 * View para editar uma empresa existente
 */
include BASE_PATH . '/src/views/layouts/header.php';
?>

<div class="container">
    <h1>Editar Empresa</h1>
    <form action="<?php echo BASE_URL; ?>/companies/update/<?php echo $company['id']; ?>" method="POST">
        <div class="form-group">
            <label for="name">Nome</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($company['name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="trade_name">Nome Fantasia</label>
            <input type="text" class="form-control" id="trade_name" name="trade_name" value="<?php echo htmlspecialchars($company['trade_name']); ?>">
        </div>
        <div class="form-group">
            <label for="address">Endereço</label>
            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($company['address']); ?>">
        </div>
        <div class="form-group">
            <label for="city">Cidade</label>
            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($company['city']); ?>">
        </div>
        <div class="form-group">
            <label for="state">Estado</label>
            <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($company['state']); ?>">
        </div>
        <div class="form-group">
            <label for="zip_code">CEP</label>
            <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($company['zip_code']); ?>">
        </div>
        <div class="form-group">
            <label for="phone">Telefone</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($company['phone']); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($company['email']); ?>">
        </div>
        <div class="form-group">
            <label for="website">Website</label>
            <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($company['website']); ?>">
        </div>
        <button type="submit" class="btn btn-primary mt-3">Atualizar</button>
    </form>
</div>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>