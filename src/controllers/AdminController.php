<?php
// src/controllers/AdminController.php

class AdminController
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function index()
    {
        if (!isAdmin()) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        $companies = $this->db->query("
            SELECT c.id, c.name, COALESCE(l.total_licenses, 0) as total_licenses, COALESCE(l.used_licenses, 0) as used_licenses
            FROM companies c
            LEFT JOIN licenses l ON c.id = l.company_id
            ORDER BY c.name
        ")->fetchAll(PDO::FETCH_ASSOC);

        require_once BASE_PATH . '/src/views/admin/index.php';
    }

    public function addCompany()
    {
        if (!isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/admin");
            exit();
        }

        $name = $_POST['name'] ?? '';
        $licenses = intval($_POST['licenses'] ?? 0);

        if (empty($name) || $licenses < 0) {
            $_SESSION['error'] = "Nome da empresa e número de licenças são obrigatórios.";
            header("Location: " . BASE_URL . "/admin");
            exit();
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("INSERT INTO companies (name) VALUES (?)");
            $stmt->execute([$name]);
            $company_id = $this->db->lastInsertId();

            $stmt = $this->db->prepare("INSERT INTO licenses (company_id, total_licenses, used_licenses) VALUES (?, ?, 0)");
            $stmt->execute([$company_id, $licenses]);

            $this->db->commit();
            $_SESSION['success'] = "Empresa adicionada com sucesso.";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = "Erro ao adicionar empresa: " . $e->getMessage();
        }

        header("Location: " . BASE_URL . "/admin");
        exit();
    }

    public function updateLicenses()
    {
        if (!isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/admin");
            exit();
        }

        $company_id = intval($_POST['company_id'] ?? 0);
        $licenses = intval($_POST['licenses'] ?? 0);

        if ($company_id <= 0 || $licenses < 0) {
            $_SESSION['error'] = "ID da empresa e número de licenças são obrigatórios.";
            header("Location: " . BASE_URL . "/admin");
            exit();
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE licenses 
                SET total_licenses = ? 
                WHERE company_id = ?
            ");
            $stmt->execute([$licenses, $company_id]);

            $_SESSION['success'] = "Licenças atualizadas com sucesso.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erro ao atualizar licenças: " . $e->getMessage();
        }

        header("Location: " . BASE_URL . "/admin");
        exit();
    }

    // Adicionamos esta função para substituir manageLicenses()
    public function manageLicenses()
    {
        // Esta função agora apenas redireciona para a página principal do admin
        header("Location: " . BASE_URL . "/admin");
        exit();
    }
}
