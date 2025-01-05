<?php
// src/controllers/RegisterController.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Controlador responsável pelo processo de registro de novos usuários
 */
class RegisterController
{
    /** @var PDO Conexão com o banco de dados */
    private $db;

    /** @var string Diretório para upload de fotos de perfil */
    private $uploadDir;

    /**
     * Construtor da classe
     * Inicializa a conexão com o banco de dados e define o diretório de upload
     */
    public function __construct()
    {
        global $db;
        if (!$db) {
            die("A conexão com o banco de dados não foi estabelecida.");
        }
        $this->db = $db;
        $this->uploadDir = BASE_PATH . "/public/uploads/profile_pictures/";

        // Garantir que o diretório de upload existe
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Processa o registro de um novo usuário
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
                $photoResult = $this->handlePhotoUpload($_FILES['photo'] ?? null);
                error_log("Resultado do upload da foto: " . print_r($photoResult, true));

                if ($photoResult === false) {
                    echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload da foto. Verifique o tipo e o tamanho do arquivo.']);
                    return;
                }

                $registrationResult = $this->registerUser($userData, $photoResult);
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

    /**
     * Verifica o código de confirmação do usuário
     */
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

    /**
     * Reenvia o código de verificação
     */
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

    /**
     * Valida os dados do usuário
     * @param array $userData Dados do usuário a serem validados
     * @return bool|array Retorna true se os dados são válidos, ou um array de mensagens de erro
     */
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

    /**
     * Verifica se a senha é forte
     * @param string $password Senha a ser verificada
     * @return bool Retorna true se a senha é forte, false caso contrário
     */
    private function isStrongPassword($password)
    {
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        return strlen($password) >= 8 && $uppercase && $lowercase && $number && $specialChars;
    }

    /**
     * Valida o CPF
     * @param string $cpf CPF a ser validado
     * @return bool Retorna true se o CPF é válido, false caso contrário
     */
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
     * Manipula o upload da foto de perfil do usuário
     * @param array|null $file Dados do arquivo enviado
     * @return array|false Array com nome do arquivo e conteúdo, ou false em caso de erro
     */
    private function handlePhotoUpload($file)
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            error_log("Nenhuma foto foi enviada ou houve um erro no upload");
            return 0;
        }

        $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $new_file_name = uniqid() . '.' . $file_extension;
        $target_file = $this->uploadDir . $new_file_name;

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_types)) {
            error_log("Tipo de arquivo não permitido: " . $file_extension);
            return false;
        }

        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            error_log("Arquivo movido com sucesso para: " . $target_file);
            $fileContent = file_get_contents($target_file);
            return [
                'filename' => $new_file_name,
                'content' => $fileContent
            ];
        } else {
            error_log("Falha ao mover o arquivo. Erro: " . error_get_last()['message']);
            return false;
        }
    }

    /**
     * Registra um novo usuário
     * @param array $userData Dados do usuário
     * @param array|null $photo Dados da foto do usuário
     * @return bool|string true se o registro for bem-sucedido, string com mensagem de erro caso contrário
     */
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

            error_log("Inserindo novo usuário no banco de dados");
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password, company_id, cpf, gender, verification_code, profile_picture, photo, is_confirmed, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())");
            $stmt->execute([
                $fullName,
                $userData['email'],
                $hashedPassword,
                $userData['company_id'],
                $userData['cpf'],
                $userData['gender'],
                $verificationCode,
                $photo ? $photo['filename'] : null,
                $photo ? $photo['content'] : null
            ]);

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

    /**
     * Envia e-mail de verificação
     * @param string $email Endereço de e-mail do usuário
     * @param string $verificationCode Código de verificação
     * @return bool Retorna true se o e-mail foi enviado com sucesso, false caso contrário
     */
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

    /**
     * Define a sessão do usuário
     * @param array $user Dados do usuário
     */
    private function setUserSession($user)
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['company_id'] = $user['company_id'];
        $_SESSION['is_admin'] = $user['is_admin'] == 1;
    }
}
