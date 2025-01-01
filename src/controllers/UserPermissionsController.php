<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserPermissionsController
{
    private $db;

    // Lista de todas as permissões possíveis
    private static $allPermissions = [
        'gravacoes' => 'Gravações',
        'transcricoes' => 'Transcrições',
        'relatorio_inteligente' => 'Relatório Inteligente',
        'gerenciar_licencas' => 'Gerenciar Licenças',
        'gerenciar_empresas' => 'Gerenciar empresas',
        'administrador_sistema' => 'Admin do Sistema'
    ];

    // Permissões simples
    private static $simplePermissions = ['gravacoes', 'transcricoes', 'relatorio_inteligente'];

    // Permissões avançadas que requerem verificação especial
    private static $advancedPermissions = ['gerenciar_licencas', 'gerenciar_empresas', 'administrador_sistema'];

    public function __construct()
    {
        global $db;
        $this->db = $db;

        // Configurar um manipulador de erros personalizado
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
    }

    private function getPublicPath()
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
    }

    // Método para exibir a página de gerenciamento de permissões
    public function index()
    {
        // Verifica se o usuário é um administrador
        if (!isAdmin()) {
            header("Location: " . BASE_URL . "/dashboard");
            exit();
        }

        $users = $this->getAllUsers();
        $permissions = self::$allPermissions;

        // Carrega a view de gerenciamento de permissões
        require_once BASE_PATH . '/src/views/admin/user_permissions.php';
    }

    // Método para obter as permissões de um usuário específico
    public function getUserPermissions($userId)
    {
        if (!isAdmin()) {
            header("HTTP/1.0 403 Forbidden");
            exit();
        }

        // Busque as permissões do usuário
        $stmt = $this->db->prepare("SELECT permission_key FROM user_permissions WHERE user_id = ? AND value = 1");
        $stmt->execute([$userId]);
        $userPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Crie um array associativo com todas as permissões e seus estados
        $permissions = [];
        foreach (self::$allPermissions as $key => $label) {
            $permissions[$key] = in_array($key, $userPermissions);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'permissions' => $permissions,
            'labels' => self::$allPermissions
        ]);
    }

    // Método para atualizar as permissões de um usuário
    public function updatePermissions()
    {
        header('Content-Type: application/json');

        try {
            if (!isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Acesso não autorizado');
            }

            $userId = $_POST['user_id'] ?? null;
            $permissions = $_POST['permissions'] ?? [];
            $verificationCode = $_POST['verification_code'] ?? null;

            if (!$userId) {
                throw new Exception('ID de usuário não fornecido');
            }

            $this->db->beginTransaction();

            // Verifica se há permissões avançadas sendo adicionadas
            $newAdvancedPermissions = array_intersect($permissions, self::$advancedPermissions);

            if (!empty($newAdvancedPermissions)) {
                if ($verificationCode) {
                    $isValid = $this->verifyAdvancedPermissions($userId, $verificationCode);
                    if (!$isValid) {
                        throw new Exception("Código de verificação inválido para permissões avançadas.");
                    }
                } else {
                    throw new Exception("Código de verificação necessário para permissões avançadas.");
                }
            }

            // Remove todas as permissões existentes do usuário
            $stmt = $this->db->prepare("DELETE FROM user_permissions WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Insere as novas permissões
            $stmt = $this->db->prepare("INSERT INTO user_permissions (user_id, permission_key, value) VALUES (?, ?, 1)");
            foreach ($permissions as $permission) {
                $stmt->execute([$userId, $permission]);
            }

            // Deletar o código de verificação após atualizar as permissões com sucesso
            if ($verificationCode) {
                $deleteStmt = $this->db->prepare("DELETE FROM verification_codes WHERE user_id = ? AND code = ?");
                $deleteStmt->execute([$userId, $verificationCode]);
            }

            $this->db->commit();

            $updatedPermissions = array_fill_keys(array_keys(self::$allPermissions), false);
            foreach ($permissions as $permission) {
                $updatedPermissions[$permission] = true;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Permissões atualizadas com sucesso',
                'updatedPermissions' => $updatedPermissions
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao atualizar permissões: ' . $e->getMessage()
            ]);
        }
    }

    // Método para solicitar um código de verificação
    public function requestVerificationCode()
    {
        header('Content-Type: application/json');

        try {
            if (!isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Acesso não autorizado');
            }

            $userId = $_POST['user_id'] ?? null;
            $permissions = $_POST['permissions'] ?? [];

            if (!$userId) {
                throw new Exception('ID do usuário não fornecido');
            }

            $newAdvancedPermissions = array_intersect($permissions, self::$advancedPermissions);

            if (empty($newAdvancedPermissions)) {
                throw new Exception("Nenhuma permissão avançada selecionada.");
            }

            $verificationCode = $this->generateVerificationCode();
            $this->saveVerificationCode($userId, $verificationCode);

            $emailSent = $this->sendVerificationEmail($userId, $verificationCode, $newAdvancedPermissions);

            echo json_encode([
                'success' => true,
                'emailSent' => $emailSent,
                'message' => $emailSent ? 'Código de verificação enviado com sucesso.' : 'Código gerado, mas houve um problema ao enviar o e-mail. Por favor, contate o administrador.',
            ]);
        } catch (Exception $e) {
            error_log("Erro ao solicitar código de verificação: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // Método para enviar e-mail com o código de verificação
    private function sendVerificationEmail($userId, $verificationCode, $newAdvancedPermissions)
    {
        try {
            $user = $this->getUserById($userId);
            $to = 'lucas.santos@eagletelecom.com.br';

            $mail = new PHPMailer(true);

            // Configurações SMTP
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($_ENV['SMTP_FROM'], 'VoiceHub');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = 'VoiceHub - Solicitação de Permissões Avançadas';

            // Preparar a lista de permissões solicitadas
            $permissionsList = '';
            foreach ($newAdvancedPermissions as $permission) {
                $permissionsList .= "<li>" . htmlspecialchars(self::$allPermissions[$permission]) . "</li>";
            }

            // HTML do corpo do e-mail
            $mailContent = $this->getEmailTemplate($user, $permissionsList, $verificationCode);

            $mail->Body = $mailContent;
            $mail->AltBody = "Solicitação de permissões avançadas para o usuário {$user['name']} (ID: {$user['id']}). Código de verificação: $verificationCode";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail: " . $e->getMessage());
            return false;
        }
    }

    // Método para verificar o código
    public function verifyCode()
    {
        header('Content-Type: application/json');

        try {
            if (!isAdmin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Acesso não autorizado');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $data['user_id'] ?? null;
            $code = $data['code'] ?? null;

            if (!$userId || !$code) {
                throw new Exception('Dados incompletos');
            }

            $isValid = $this->verifyAdvancedPermissions($userId, $code);

            if ($isValid) {
                echo json_encode(['success' => true, 'message' => 'Código verificado com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Código inválido ou expirado']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao verificar o código: ' . $e->getMessage()]);
        }
    }

    // Método para gerar o template do e-mail
    private function getEmailTemplate($user, $permissionsList, $verificationCode)
    {
        // Template do e-mail (mantenha o template existente)
        return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Solicitação de Permissões Avançadas - VoiceHub</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            h1 { color: #3498db; }
            .code { font-size: 24px; font-weight: bold; color: #2ecc71; background-color: #f1f1f1; padding: 10px; border-radius: 5px; }
            .permissions { background-color: #f9f9f9; padding: 15px; border-radius: 5px; }
            .footer { margin-top: 20px; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Solicitação de Permissões Avançadas - VoiceHub</h1>
            <p>Olá Administrador,</p>
            <p>Uma solicitação de permissões avançadas foi feita para o seguinte usuário:</p>
            <p><strong>Nome:</strong> {$user['name']}<br>
            <strong>ID:</strong> {$user['id']}<br>
            <strong>Email:</strong> {$user['email']}</p>
            <p>As seguintes permissões avançadas foram solicitadas:</p>
            <div class='permissions'>
                <ul>
                    $permissionsList
                </ul>
            </div>
            <p>Para aprovar estas permissões, use o seguinte código de verificação:</p>
            <p class='code'>$verificationCode</p>
            <p>Este código é válido por 30 minutos.</p>
            <p>Se você não autorizou esta solicitação, por favor, ignore este e-mail.</p>
            <div class='footer'>
                <p>Atenciosamente,<br>Equipe VoiceHub</p>
                <p>Este é um e-mail automático. Por favor, não responda.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    }

    // Método para verificar o código de permissões avançadas
    private function verifyAdvancedPermissions($userId, $code)
    {
        $stmt = $this->db->prepare("SELECT * FROM verification_codes WHERE user_id = ? AND code = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
        $stmt->execute([$userId, $code]);
        return $stmt->fetch() !== false;
    }

    // Método para gerar um código de verificação
    private function generateVerificationCode()
    {
        return sprintf("%06d", mt_rand(1, 999999));
    }

    // Método para salvar o código de verificação
    private function saveVerificationCode($userId, $code)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO verification_codes (user_id, code) VALUES (?, ?)");
            $result = $stmt->execute([$userId, $code]);
            if (!$result) {
                throw new Exception("Falha ao salvar o código de verificação.");
            }
        } catch (PDOException $e) {
            error_log("Erro ao salvar código de verificação: " . $e->getMessage());
            throw new Exception("Erro ao salvar código de verificação.");
        }
    }

    // Método para obter todos os usuários
    private function getAllUsers()
    {
        $stmt = $this->db->query("
        SELECT id, name, email, profile_picture, photo
        FROM users 
        ORDER BY name
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $publicPath = $this->getPublicPath();
        $uploadsPath = '/uploads/profile_pictures/';

        foreach ($users as &$user) {
            $avatarPath = '';
            if (!empty($user['profile_picture'])) {
                $avatarPath = $uploadsPath . $user['profile_picture'];
            } elseif (!empty($user['photo'])) {
                $avatarPath = $uploadsPath . $user['photo'];
            }

            if (empty($avatarPath) || !file_exists($publicPath . $avatarPath)) {
                $avatarPath = '/assets/images/profile.png';
            }

            $user['avatar'] = $avatarPath;

            // Removemos profile_picture e photo para não enviar dados desnecessários
            unset($user['profile_picture']);
            unset($user['photo']);
        }

        return $users;
    }

    // Método para obter um usuário pelo ID
    private function getUserById($userId)
    {
        $stmt = $this->db->prepare("SELECT id, name, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
