<?php
// src/controllers/ProfileController.php

class ProfileController
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function index()
    {
        if (!isLoggedIn()) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        $user_id = $_SESSION['user_id'];

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        require_once BASE_PATH . '/src/views/profile.php';
    }

    public function update()
    {
        if (!isLoggedIn()) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        $user_id = $_SESSION['user_id'];

        // Processar upload de foto de perfil
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $this->handleProfilePictureUpload($user_id);
            exit(); // Termina a execução após o upload
        }

        // Processar remoção de foto de perfil
        if (isset($_POST['remove_picture'])) {
            $this->handleProfilePictureRemoval($user_id);
            exit(); // Termina a execução após a remoção
        }

        // Processar atualização de informações do perfil
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';

        if (empty($name) || empty($email)) {
            $_SESSION['error'] = "Nome e e-mail são obrigatórios.";
            header("Location: " . BASE_URL . "/profile");
            exit();
        }

        $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $user_id]);

        // Processar alteração de senha
        $this->handlePasswordChange($user_id);

        $_SESSION['success'] = "Perfil atualizado com sucesso.";
        header("Location: " . BASE_URL . "/profile");
        exit();
    }

    private function handleProfilePictureUpload($user_id)
    {
        $upload_dir = BASE_PATH . '/public/uploads/profile_pictures/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $stmt = $this->db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$new_filename, $user_id]);
                $_SESSION['success'] = "Imagem de perfil atualizada com sucesso.";
            } else {
                $_SESSION['error'] = "Falha ao fazer upload da imagem.";
            }
        } else {
            $_SESSION['error'] = "Tipo de arquivo não permitido. Tipos permitidos: " . implode(', ', $allowed);
        }

        header("Location: " . BASE_URL . "/profile");
    }

    private function handleProfilePictureRemoval($user_id)
    {
        $stmt = $this->db->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_picture = $stmt->fetchColumn();

        $upload_dir = BASE_PATH . '/public/uploads/profile_pictures/';
        if ($current_picture && file_exists($upload_dir . $current_picture)) {
            unlink($upload_dir . $current_picture);
        }

        $stmt = $this->db->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "Foto de perfil removida com sucesso.";

        header("Location: " . BASE_URL . "/profile");
    }

    private function handlePasswordChange($user_id)
    {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!empty($current_password) && !empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = "As novas senhas não coincidem.";
                return;
            }

            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $_SESSION['success'] = "Senha atualizada com sucesso.";
            } else {
                $_SESSION['error'] = "Senha atual incorreta.";
            }
        }
    }
}
