<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// src/controllers/AuthController.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController
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
     * Processa o registro de um novo usuário.
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userData = [
                    'name' => $_POST['name'] ?? '',
                    'surname' => $_POST['surname'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'password' => $_POST['password'] ?? '',
                    'confirm_password' => $_POST['confirm_password'] ?? '',
                    'company_id' => $_POST['company_id'] ?? '',
                    'cpf' => $_POST['cpf'] ?? '',
                    'gender' => $_POST['gender'] ?? ''
                ];

                error_log("Dados recebidos: " . json_encode($userData));

                $validationResult = $this->validateUserData($userData);

                if ($validationResult !== true) {
                    error_log("Erro de validação: " . json_encode($validationResult));
                    echo json_encode(['success' => false, 'message' => implode(", ", $validationResult)]);
                    return;
                }

                // Processa o upload da foto
                $photo = $this->handlePhotoUpload($_FILES['photo'] ?? null);
                error_log("Resultado do upload da foto: " . print_r($photo, true));

                if ($photo === false) {
                    echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload da foto. Verifique o tipo e o tamanho do arquivo.']);
                    return;
                }

                $registrationResult = $this->registerUser($userData, $photo);
                error_log("Resultado do registro: " . json_encode($registrationResult));

                if ($registrationResult === true) {
                    echo json_encode(['success' => true, 'message' => 'Cadastro realizado com sucesso. Por favor, verifique seu e-mail para obter o código de verificação.']);
                } else {
                    echo json_encode(['success' => false, 'message' => $registrationResult]);
                }
            } catch (Exception $e) {
                error_log("Exceção no registro: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
            }
            return;
        }

        $companies = $this->db->query("SELECT id, name FROM companies")->fetchAll(PDO::FETCH_ASSOC);
        require_once BASE_PATH . '/src/views/auth/register.php';
    }
    public function verifyCode()
    {
        if (!isset($_SESSION['temp_user_id'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $enteredCode = $_POST['verification_code'] ?? '';
            $userId = $_SESSION['temp_user_id'];

            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? AND verification_code = ?");
            $stmt->execute([$userId, $enteredCode]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $stmt = $this->db->prepare("UPDATE users SET is_confirmed = 1, verification_code = NULL WHERE id = ?");
                $stmt->execute([$userId]);

                unset($_SESSION['temp_user_id']);
                $this->setUserSession($user);
                header("Location: " . BASE_URL . "/dashboard");
                exit();
            } else {
                $error = "Código de verificação inválido.";
            }
        }

        require_once BASE_PATH . '/src/views/auth/verify_code.php';
    }

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

    public function getActiveUsersCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE is_active = TRUE");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }


    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';

            if (empty($email)) {
                $error = "Por favor, forneça um e-mail.";
            } else {
                $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $token = bin2hex(random_bytes(16));
                    $stmt = $this->db->prepare("UPDATE users SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
                    $stmt->execute([$token, $user['id']]);

                    $this->sendPasswordResetEmail($email, $token);

                    $success = "Um e-mail com instruções para redefinir sua senha foi enviado.";
                } else {
                    $error = "Não foi encontrado um usuário com este e-mail.";
                }
            }
        }

        require_once BASE_PATH . '/src/views/auth/forgot_password.php';
    }

    public function terms()
    {
        require_once BASE_PATH . '/src/views/auth/terms.php';
    }

    public function privacy()
    {
        require_once BASE_PATH . '/src/views/auth/privacy.php';
    }

    public function resendVerificationCode()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $email = $data['email'] ?? '';

            $stmt = $this->db->prepare("SELECT id, is_confirmed FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && !$user['is_confirmed']) {
                $verificationCode = sprintf("%06d", mt_rand(1, 999999));
                $stmt = $this->db->prepare("UPDATE users SET verification_code = ? WHERE id = ?");
                $stmt->execute([$verificationCode, $user['id']]);

                if ($this->sendVerificationEmail($email, $verificationCode)) {
                    echo json_encode(['success' => true, 'message' => 'Novo código de verificação enviado.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao enviar o e-mail de verificação.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Não foi possível reenviar o código de verificação.']);
            }
            return;
        }
    }
    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_once BASE_PATH . '/src/views/auth/reset_password.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $action = $_POST['action'] ?? '';

            /*error_log("Reset password action: " . $action);
            error_log("POST data: " . print_r($_POST, true));*/

            try {
                switch ($action) {
                    case 'sendEmail':
                        $result = $this->sendResetEmail();
                        break;
                    case 'verifyCode':
                        $result = $this->verifyResetCode();
                        break;
                    case 'resetPassword':
                        $result = $this->completePasswordReset();
                        break;
                    default:
                        throw new Exception('Ação inválida');
                }
                echo json_encode($result);
            } catch (Exception $e) {
                error_log("Error in reset password: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    public function newPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($token) || empty($password) || empty($confirmPassword)) {
                echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
                return;
            }

            if ($password !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'As senhas não coincidem.']);
                return;
            }

            $stmt = $this->db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
                $stmt->execute([$hashedPassword, $user['id']]);

                echo json_encode(['success' => true, 'message' => 'Sua senha foi redefinida com sucesso. Você pode fazer login agora.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Token inválido ou expirado.']);
            }
            return;
        }

        require_once BASE_PATH . '/src/views/auth/new_password.php';
    }

    private function validateUserData($userData)
    {
        $errors = [];

        if (
            empty($userData['name']) || empty($userData['surname']) || empty($userData['email']) ||
            empty($userData['password']) || empty($userData['confirm_password']) || empty($userData['company_id'])
        ) {
            $errors[] = "Todos os campos obrigatórios devem ser preenchidos.";
        }

        if ($userData['password'] !== $userData['confirm_password']) {
            $errors[] = "As senhas não coincidem.";
        }

        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "E-mail inválido.";
        }

        if (!$this->isStrongPassword($userData['password'])) {
            $errors[] = "A senha deve ter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas, números e símbolos.";
        }

        if (!empty($userData['cpf']) && !$this->validateCPF($userData['cpf'])) {
            $errors[] = "CPF inválido.";
        }

        return empty($errors) ? true : $errors;
    }

    private function isStrongPassword($password)
    {
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        return strlen($password) >= 8 && $uppercase && $lowercase && $number && $specialChars;
    }

    private function validateCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/is', '', $cpf);

        if (strlen($cpf) != 11) {
            return false;
        }

        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Manipula o upload da foto de perfil do usuário.
     * 
     * @param array|null $file Array contendo informações do arquivo enviado ($_FILES['photo'])
     * @return string|null|false Nome do arquivo salvo, null se nenhum arquivo foi enviado, ou false em caso de erro
     */
    private function handlePhotoUpload($file)
    {
        // Define o diretório de destino para as fotos de perfil
        $target_dir = BASE_PATH . "/public/uploads/profile_pictures/";

        // Cria o diretório se ele não existir
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        // Verifica se um arquivo foi enviado e se não houve erros no upload
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            error_log("Nenhuma foto foi enviada ou houve um erro no upload");
            return null;
        }

        // Obtém a extensão do arquivo
        $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

        // Gera um nome único para o arquivo
        $new_file_name = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_file_name;

        // Define os tipos de arquivo permitidos
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_types)) {
            error_log("Tipo de arquivo não permitido: " . $file_extension);
            return false;
        }

        // Tenta mover o arquivo para o diretório de destino
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            error_log("Arquivo movido com sucesso para: " . $target_file);
            return $new_file_name;
        } else {
            error_log("Falha ao mover o arquivo. Erro: " . error_get_last()['message']);
            return false;
        }
    }


    private function registerUser($userData, $photo)
    {
        try {
            $this->db->beginTransaction();

            error_log("Iniciando registro de usuário");

            // Verificar se o e-mail já existe
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$userData['email']]);
            if ($stmt->fetch()) {
                throw new Exception("Este e-mail já está cadastrado. Deseja recuperar sua senha?");
            }

            // Verificar se a empresa existe
            $stmt = $this->db->prepare("SELECT id FROM companies WHERE id = ?");
            $stmt->execute([$userData['company_id']]);
            if (!$stmt->fetch()) {
                throw new Exception("Empresa selecionada não existe.");
            }

            // Verificar ou criar licença
            $stmt = $this->db->prepare("SELECT total_licenses, used_licenses FROM licenses WHERE company_id = ?");
            $stmt->execute([$userData['company_id']]);
            $license = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$license) {
                error_log("Criando licença padrão para a empresa");
                $stmt = $this->db->prepare("INSERT INTO licenses (company_id, total_licenses, used_licenses) VALUES (?, 10, 0)");
                $stmt->execute([$userData['company_id']]);
                $license = ['total_licenses' => 10, 'used_licenses' => 0];
            }

            if ($license['used_licenses'] >= $license['total_licenses']) {
                throw new Exception("Todas as licenças disponíveis para esta empresa estão em uso.");
            }

            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            $verificationCode = sprintf("%06d", mt_rand(1, 999999));
            $fullName = $userData['name'] . ' ' . $userData['surname'];

            error_log("Valor de \$photo antes da inserção no banco: " . print_r($photo, true));
            error_log("Inserindo novo usuário no banco de dados");
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password, company_id, cpf, gender, verification_code, photo, is_confirmed, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())");
            $stmt->execute([$fullName, $userData['email'], $hashedPassword, $userData['company_id'], $userData['cpf'], $userData['gender'], $verificationCode, $photo]);

            $userId = $this->db->lastInsertId();
            error_log("Usuário inserido com ID: " . $userId);

            $this->db->prepare("UPDATE licenses SET used_licenses = used_licenses + 1 WHERE company_id = ?")->execute([$userData['company_id']]);

            $this->db->commit();

            error_log("Enviando e-mail de verificação");
            $emailSent = $this->sendVerificationEmail($userData['email'], $verificationCode);

            if (!$emailSent) {
                error_log("Falha ao enviar e-mail de verificação");
                // Considere reverter a transação ou marcar o usuário para reenvio de e-mail
            }

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erro no registro de usuário: " . $e->getMessage());
            return $e->getMessage();
        }
    }
    private function sendVerificationEmail($email, $verificationCode)
    {
        $mail = new PHPMailer(true);

        try {
            // Configurações SMTP
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($_ENV['SMTP_FROM'], 'VoiceHub');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Bem-vindo ao VoiceHub - Confirme seu Cadastro';

            // HTML do corpo do e-mail
            $mailContent = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
                    .header { background-color: #4A90E2; color: white; padding: 10px; text-align: center; }
                    .content { background-color: white; padding: 20px; border-radius: 5px; }
                    .verification-code { font-size: 24px; font-weight: bold; color: #4A90E2; text-align: center; padding: 10px; background-color: #e9f0f9; border-radius: 5px; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #888; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Bem-vindo ao VoiceHub!</h1>
                    </div>
                    <div class='content'>
                        <p>Olá,</p>
                        <p>Obrigado por se cadastrar no VoiceHub. Estamos animados para tê-lo conosco!</p>
                        <p>Para completar seu cadastro e garantir a segurança da sua conta, por favor, use o código de verificação abaixo:</p>
                        <div class='verification-code'>$verificationCode</div>
                        <p>Este código é válido por 24 horas. Após esse período, você precisará solicitar um novo código.</p>
                        <p>Se você não se cadastrou no VoiceHub, por favor, ignore este e-mail.</p>
                        <p>Atenciosamente,<br>Equipe VoiceHub</p>
                    </div>
                    <div class='footer'>
                        <p>Este é um e-mail automático, por favor não responda.</p>
                        <p>&copy; 2023 VoiceHub. Todos os direitos reservados.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            $mail->Body = $mailContent;
            $mail->AltBody = "Bem-vindo ao VoiceHub! Seu código de verificação é: $verificationCode. Este código é válido por 24 horas.";

            $mail->send();
            error_log("E-mail de verificação enviado para: $email");
            return true;
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail de verificação: {$mail->ErrorInfo}");
            return false;
        }
    }

    private function confirmUser($userId)
    {
        $stmt = $this->db->prepare("UPDATE users SET is_confirmed = 1, verification_code = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }

    private function sendPasswordResetEmail($email, $token)
    {
        $mail = new PHPMailer(true);

        try {
            // Configurações SMTP
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($_ENV['SMTP_FROM'], 'VoiceHub');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Recuperação de Senha - VoiceHub';

            $resetLink = BASE_URL . "/new-password?token=" . $token;

            $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
                <h2 style='color: #FF0000;'>Recuperação de Senha - VoiceHub</h2>
                <p>Você solicitou a recuperação de senha para sua conta no VoiceHub.</p>
                <p>Para redefinir sua senha, clique no link abaixo:</p>
                <p><a href='{$resetLink}' style='background-color: #FF0000; color: #FFFFFF; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Redefinir Senha</a></p>
                <p>Se você não solicitou esta recuperação, por favor, ignore este e-mail.</p>
                <p>Este link expirará em 1 hora por motivos de segurança.</p>
                <p>Atenciosamente,<br>Equipe VoiceHub</p>
            </div>
        </body>
        </html>
        ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
            return false;
        }
    }

    private function sendResetEmail()
    {
        $email = $_POST['email'] ?? '';

        if (empty($email)) {
            throw new Exception('Por favor, forneça um e-mail válido.');
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $resetCode = sprintf("%06d", mt_rand(1, 999999));
            $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $this->db->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $stmt->execute([$resetCode, $expiryTime, $user['id']]);

            if ($this->sendEmailWithResetCode($email, $resetCode)) {
                return ['success' => true, 'message' => 'Um código de verificação foi enviado para o seu e-mail.'];
            } else {
                throw new Exception('Houve um erro ao enviar o e-mail. Por favor, tente novamente.');
            }
        } else {
            throw new Exception('Não foi encontrado um usuário com este e-mail.');
        }
    }

    private function verifyResetCode()
    {
        $code = $_POST['code'] ?? '';
        $email = $_POST['email'] ?? '';

        if (empty($code) || empty($email)) {
            throw new Exception('Código de verificação ou e-mail inválido.');
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_token_expires > NOW()");
        $stmt->execute([$email, $code]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return ['success' => true, 'message' => 'Código verificado com sucesso.'];
        } else {
            throw new Exception('Código inválido ou expirado.');
        }
    }

    private function completePasswordReset()
    {
        $newPassword = $_POST['newPassword'] ?? '';
        $email = $_POST['email'] ?? '';

        if (empty($newPassword) || empty($email)) {
            throw new Exception('Por favor, forneça uma nova senha e o e-mail.');
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND reset_token_expires > NOW()");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);

            return ['success' => true, 'message' => 'Sua senha foi redefinida com sucesso.'];
        } else {
            throw new Exception('Não foi possível redefinir a senha. Por favor, inicie o processo novamente.');
        }
    }

    private function sendEmailWithResetCode($email, $resetCode)
    {
        $mail = new PHPMailer(true);

        try {
            // Configurações SMTP
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($_ENV['SMTP_FROM'], 'VoiceHub');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Código de Recuperação de Senha - VoiceHub';

            // HTML do e-mail
            $mail->Body = "
<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Recuperação de Senha - VoiceHub</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .container { background-color: #f9f9f9; border-radius: 10px; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { background-color: #4A90E2; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background-color: white; padding: 30px; border-radius: 0 0 10px 10px; }
        .code-container { text-align: center; margin: 30px 0; }
        .code { font-size: 36px; font-weight: bold; color: #4A90E2; letter-spacing: 8px; padding: 15px; background-color: #e9f0f9; border: 2px dashed #4A90E2; border-radius: 10px; display: inline-block; }
        .code-instruction { font-size: 14px; color: #666; margin-top: 10px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #888; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1 style='margin:0;'>VoiceHub</h1>
        </div>
        <div class='content'>
            <h2>Recuperação de Senha</h2>
            <p>Olá,</p>
            <p>Recebemos uma solicitação para redefinir a senha da sua conta no VoiceHub. Se você não fez esta solicitação, por favor, ignore este e-mail.</p>
            <p>Para redefinir sua senha, use o seguinte código de verificação:</p>
            <div class='code-container'>
                <div class='code'>{$resetCode}</div>
                <p class='code-instruction'>Copie o código acima e cole-o na página de recuperação de senha.</p>
            </div>
            <p>Este código é válido por 1 hora. Após este período, você precisará solicitar um novo código.</p>
            <p>Se você tiver alguma dúvida ou precisar de assistência, não hesite em entrar em contato com nossa equipe de suporte.</p>
            <p>Atenciosamente,<br>Equipe VoiceHub</p>
        </div>
        <div class='footer'>
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p>&copy; " . date('Y') . " VoiceHub. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
        ";

            // Versão em texto simples
            $mail->AltBody = "Recuperação de Senha - VoiceHub\n\n" .
                "Olá,\n\n" .
                "Recebemos uma solicitação para redefinir a senha da sua conta no VoiceHub. Se você não fez esta solicitação, por favor, ignore este e-mail.\n\n" .
                "Para redefinir sua senha, use o seguinte código de verificação: {$resetCode}\n\n" .
                "Copie o código acima e cole-o na página de recuperação de senha.\n\n" .
                "Este código é válido por 1 hora. Após este período, você precisará solicitar um novo código.\n\n" .
                "Se você tiver alguma dúvida ou precisar de assistência, não hesite em entrar em contato com nossa equipe de suporte.\n\n" .
                "Atenciosamente,\nEquipe VoiceHub";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
            return false;
        }
    }

    private function setUserSession($user)
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['company_id'] = $user['company_id'];
        $_SESSION['is_admin'] = $user['is_admin'] == 1;
    }
}
