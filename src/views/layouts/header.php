<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Permissions-Policy" content="interest-cohort=()">
    <meta name="referrer" content="no-referrer-when-downgrade">

    <title>VoiceHub</title>

    <!-- Inclusão de estilos e fontes -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">

</head>

<body>
    <header class="app-header">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <!-- Logo e nome do aplicativo -->
                <div class="logo-container">
                    <a class="navbar-brand" href="<?php echo BASE_URL; ?>/">
                        <i class="fas fa-microphone-alt"></i> VoiceHub
                    </a>
                </div>
                <!-- Botão de toggle para dispositivos móveis -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <!-- Menu de navegação -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php if (isLoggedIn()): ?>
                            <!-- Links para usuários logados -->
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/user/profile">
                                    <i class="fas fa-user-circle"></i> Perfil
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/recordings">
                                    <i class="fas fa-microphone"></i> Gravações
                                </a>
                            </li>
                            <?php if (isAdmin()): ?>
                                <!-- Link para o painel de administração -->
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>/admin">
                                        <i class="fas fa-cogs"></i> Admin
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/logout">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        <?php else: ?>
                            <!-- Links para usuários não logados -->
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/login">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/register">
                                    <i class="fas fa-user-plus"></i> Registrar
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
        <!-- Container para o efeito de onda -->
        <div class="wave-container">
            <svg class="waves" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
                <defs>
                    <path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z" />
                </defs>
                <g class="parallax">
                    <use xlink:href="#gentle-wave" x="48" y="0" fill="rgba(255,255,255,0.7" />
                    <use xlink:href="#gentle-wave" x="48" y="3" fill="rgba(255,255,255,0.5)" />
                    <use xlink:href="#gentle-wave" x="48" y="5" fill="rgba(255,255,255,0.3)" />
                    <use xlink:href="#gentle-wave" x="48" y="7" fill="#fff" />
                </g>
            </svg>
        </div>
    </header>

    <!-- Scripts necessários para o funcionamento do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>