<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<h2>Gerenciar Licenças</h2>

<?php if (isset($error)): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<?php if (isset($success)): ?>
    <p style="color: green;"><?php echo $success; ?></p>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Empresa</th>
            <th>Total de Licenças</th>
            <th>Licenças Usadas</th>
            <th>Ação</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($companies as $company): ?>
            <tr>
                <td><?php echo htmlspecialchars($company['name']); ?></td>
                <td><?php echo $company['total_licenses']; ?></td>
                <td><?php echo $company['used_licenses']; ?></td>
                <td>
                    <form action="<?php echo BASE_URL; ?>/admin/manage-licenses" method="post">
                        <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                        <input type="number" name="total_licenses" value="<?php echo $company['total_licenses']; ?>" min="<?php echo $company['used_licenses']; ?>">
                        <button type="submit">Atualizar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>