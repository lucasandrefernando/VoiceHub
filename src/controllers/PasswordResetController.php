<?php
// src/controllers/PasswordResetController.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PasswordResetController
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
     * Processa a solicitação de recuperação de senha
     */
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

    /**
     * Processa a redefinição de senha
     */
    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            require_once BASE_PATH . '/src/views/auth/reset_password.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $action = $_POST['action'] ?? '';

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

    /**
     * Processa a definição de uma nova senha
     */
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

    /**
     * Envia e-mail de redefinição de senha
     */
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

    /**
     * Envia e-mail com código de redefinição
     */
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

    /**
     * Verifica o código de redefinição
     */
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

    /**
     * Completa o processo de redefinição de senha
     */
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

    /**
     * Envia e-mail com código de redefinição
     */
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
}
