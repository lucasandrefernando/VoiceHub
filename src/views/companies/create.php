<?php include BASE_PATH . '/src/views/layouts/header.php'; ?>

<div class="container">
    <h1>Cadastrar Nova Empresa</h1>
    <form id="companyForm" action="<?php echo BASE_URL; ?>/companies/store" method="POST">
        <div class="form-group">
            <label for="cnpj">CNPJ</label>
            <input type="text" class="form-control" id="cnpj" name="cnpj" required>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Buscar e Cadastrar</button>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    $(document).ready(function() {
        $('#cnpj').mask('00.000.000/0000-00');
    });
</script>

<?php include BASE_PATH . '/src/views/layouts/footer.php'; ?>