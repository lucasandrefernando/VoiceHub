<?php
// src/controllers/LoginController.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class LoginController
{
    private $db;

    public function __construct()
    {
        global $db;
        if (!$db) {
            die("A conexão com o banco de dados não foi estabelecida.");
        }
        $this->db = $db;
    }

    /**
     * Processa o login do usuário
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $verificationCode = $_POST['verification_code'] ?? '';

            $stmt = $this->db->prepare("SELECT id, password, is_confirmed, name, email, company_id, verification_code FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['is_confirmed']) {
                    // Carregar as permissões do usuário
                    $userPermissions = $this->getUserPermissions($user['id']);

                    // Configurar a sessão do usuário
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['company_id'] = $user['company_id'];
                    $_SESSION['user_permissions'] = $userPermissions;

                    error_log("User Permissions after login: " . print_r($userPermissions, true));

                    // Atualizar o status do usuário para ativo
                    $stmt = $this->db->prepare("UPDATE users SET is_active = TRUE WHERE id = ?");
                    $stmt->execute([$user['id']]);

                    $responseData = ['success' => true, 'redirect' => BASE_URL . '/dashboard'];
                } else {
                    if ($verificationCode) {
                        if ($verificationCode === $user['verification_code']) {
                            $this->confirmUser($user['id']);
                            $userPermissions = $this->getUserPermissions($user['id']);
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['company_id'] = $user['company_id'];
                            $_SESSION['user_permissions'] = $userPermissions;
                            $responseData = ['success' => true, 'redirect' => BASE_URL . '/dashboard'];
                        } else {
                            $responseData = ['success' => false, 'message' => 'Código de verificação incorreto.'];
                        }
                    } else {
                        $responseData = [
                            'success' => false,
                            'requiresVerification' => true,
                            'message' => 'Por favor, insira o código de verificação enviado para o seu e-mail.'
                        ];
                    }
                }
            } else {
                $responseData = ['success' => false, 'message' => 'E-mail ou senha inválidos.'];
            }

            echo json_encode($responseData);
            return;
        }

        require_once BASE_PATH . '/src/views/auth/login.php';
    }

    /**
     * Processa o logout do usuário
     */
    public function logout()
    {
        if (isset($_SESSION['user_id'])) {
            // Atualizar o status do usuário para inativo
            $stmt = $this->db->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        }

        session_destroy();
        header("Location: " . BASE_URL . "/login");
        exit();
    }

    /**
     * Verifica o primeiro acesso do usuário
     */
    public function checkFirstAccess()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos.']);
                return;
            }

            $stmt = $this->db->prepare("SELECT id, password, is_confirmed, verification_code FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if (!$user['is_confirmed'] && $user['verification_code']) {
                    echo json_encode(['success' => true, 'requiresVerification' => true]);
                } else {
                    echo json_encode(['success' => true, 'requiresVerification' => false]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'E-mail ou senha inválidos.']);
            }
        }
    }

    /**
     * Obtém as permissões do usuário
     */
    private function getUserPermissions($userId)
    {
        $stmt = $this->db->prepare("SELECT permission_key, value FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Converter os valores para booleanos
        return array_map(function ($value) {
            return $value == 1;
        }, $permissions);
    }

    /**
     * Obtém a contagem de usuários ativos
     */
    public function getActiveUsersCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE is_active = TRUE");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Confirma o usuário após a verificação
     */
    private function confirmUser($userId)
    {
        $stmt = $this->db->prepare("UPDATE users SET is_confirmed = 1, verification_code = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }
}
