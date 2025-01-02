<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<div class="container">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <h1>Lista de Empresas</h1>
    <a href="<?php echo BASE_URL; ?>/companies/create" class="btn btn-primary mb-3">Nova Empresa</a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>CNPJ</th>
                <th>Nome</th>
                <th>Nome Fantasia</th>
                <th>Cidade</th>
                <th>Estado</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($companies as $company): ?>
                <tr>
                    <td><?php echo htmlspecialchars($company['cnpj']); ?></td>
                    <td><?php echo htmlspecialchars($company['name']); ?></td>
                    <td><?php echo htmlspecialchars($company['trade_name']); ?></td>
                    <td><?php echo htmlspecialchars($company['city']); ?></td>
                    <td><?php echo htmlspecialchars($company['state']); ?></td>
                    <td>
                        <a href="<?php echo BASE_URL; ?>/companies/edit/<?php echo $company['id']; ?>" class="btn btn-sm btn-info">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>