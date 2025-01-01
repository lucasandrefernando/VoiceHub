<?php
// src/controllers/HomeController.php

class HomeController
{
    public function index()
    {
        if (isLoggedIn()) {
            // Redirecionar para o dashboard se o usuário estiver logado
            header("Location: " . BASE_URL . "/dashboard");
            exit();
        } else {
            // Mostrar a página inicial pública
            require_once BASE_PATH . '/src/views/home_public.php';
        }
    }
}
