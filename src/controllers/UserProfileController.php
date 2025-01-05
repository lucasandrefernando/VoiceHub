<?php
// src/controllers/UserProfileController.php

class UserProfileController
{
    private $db;
    private $uploadDir;

    /**
     * Construtor da classe
     * Inicializa a conexão com o banco de dados e define o diretório de upload
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->uploadDir = BASE_PATH . '/public/uploads/profile_pictures/';

        // Garante que o diretório de upload existe
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Exibe a página de perfil do usuário
     */
    public function index()
    {
        if (!isLoggedIn()) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        $userId = $_SESSION['user_id'];
        $user = $this->getUserInfo($userId);
        $permissions = $this->getUserPermissions($userId);

        // Determina o caminho da foto de perfil
        $photoPath = $this->getProfilePhotoPath($user);

        // Log para depuração
        error_log("User info: " . print_r($user, true));
        error_log("Photo path: " . $photoPath);

        require_once BASE_PATH . '/src/views/user/user_profile.php';
    }

    /**
     * Obtém as informações do usuário
     * 
     * @param int $userId ID do usuário
     * @return array Informações do usuário
     */
    private function getUserInfo($userId)
    {
        $stmt = $this->db->prepare("SELECT id, name, email, cpf, photo, profile_picture, gender FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém as permissões do usuário
     * 
     * @param int $userId ID do usuário
     * @return array Permissões do usuário
     */
    private function getUserPermissions($userId)
    {
        $stmt = $this->db->prepare("SELECT permission_key, value FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Determina o caminho da foto de perfil do usuário
     * 
     * @param array $user Informações do usuário
     * @return string Caminho da foto de perfil
     */
    private function getProfilePhotoPath($user)
    {
        // Verifica primeiro se há um nome de arquivo na coluna profile_picture
        if (!empty($user['profile_picture'])) {
            $filePath = $this->uploadDir . $user['profile_picture'];
            if (file_exists($filePath)) {
                return BASE_URL . '/uploads/profile_pictures/' . $user['profile_picture'];
            }
        }

        // Se não houver profile_picture, verifica se há dados BLOB na coluna photo
        if (!empty($user['photo'])) {
            $tempFileName = 'temp_' . uniqid() . '.jpg';
            $tempFilePath = $this->uploadDir . $tempFileName;
            file_put_contents($tempFilePath, $user['photo']);
            return BASE_URL . '/uploads/profile_pictures/' . $tempFileName;
        }

        // Se nenhuma imagem for encontrada, retorna a imagem padrão
        return BASE_URL . '/assets/images/profile.png';
    }

    /**
     * Atualiza a foto de perfil do usuário
     */
    public function updateProfilePicture()
    {
        if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(false, 'Acesso não autorizado', 403);
        }

        $userId = $_SESSION['user_id'];

        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = isset($_FILES['profile_picture']) ? 'Erro no upload: ' . $_FILES['profile_picture']['error'] : 'Nenhum arquivo enviado';
            error_log("Erro no upload da imagem: " . $errorMessage);
            $this->sendJsonResponse(false, 'Erro no upload da imagem: ' . $errorMessage);
        }

        $fileExtension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            error_log("Extensão de arquivo não permitida: " . $fileExtension);
            $this->sendJsonResponse(false, 'Tipo de arquivo não permitido. Use apenas JPG, JPEG, PNG ou GIF.');
        }

        $fileName = uniqid() . '.' . $fileExtension;
        $uploadFile = $this->uploadDir . $fileName;

        error_log("Tentando mover o arquivo para: " . $uploadFile);

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFile)) {
            $imageContent = file_get_contents($uploadFile);

            error_log("Arquivo movido com sucesso. Tamanho: " . filesize($uploadFile) . " bytes");

            try {
                $stmt = $this->db->prepare("UPDATE users SET profile_picture = ?, photo = ? WHERE id = ?");
                $result = $stmt->execute([$fileName, $imageContent, $userId]);

                if ($result) {
                    error_log("Banco de dados atualizado com sucesso");
                    $this->sendJsonResponse(true, 'Foto de perfil atualizada com sucesso', 200, [
                        'photoPath' => BASE_URL . '/uploads/profile_pictures/' . $fileName
                    ]);
                } else {
                    error_log("Falha ao atualizar o banco de dados: " . print_r($stmt->errorInfo(), true));
                    $this->sendJsonResponse(false, 'Erro ao atualizar a foto de perfil no banco de dados');
                }
            } catch (PDOException $e) {
                error_log("Exceção PDO: " . $e->getMessage());
                $this->sendJsonResponse(false, 'Erro ao atualizar o banco de dados: ' . $e->getMessage());
            }
        } else {
            $phpFileUploadErrors = [
                0 => 'There is no error, the file uploaded with success',
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                3 => 'The uploaded file was only partially uploaded',
                4 => 'No file was uploaded',
                6 => 'Missing a temporary folder',
                7 => 'Failed to write file to disk.',
                8 => 'A PHP extension stopped the file upload.',
            ];
            $errorMessage = $phpFileUploadErrors[$_FILES['profile_picture']['error']] ?? 'Unknown upload error';
            error_log("Falha ao mover o arquivo enviado. Erro: " . $errorMessage);
            $this->sendJsonResponse(false, 'Erro ao mover o arquivo enviado: ' . $errorMessage);
        }
    }

    /**
     * Remove a foto de perfil do usuário
     */
    public function removeProfilePicture()
    {
        if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(false, 'Acesso não autorizado', 403);
        }

        $userId = $_SESSION['user_id'];

        // Busca o nome do arquivo da foto atual
        $stmt = $this->db->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Remove o arquivo físico se existir
        if ($user && $user['profile_picture']) {
            $filePath = $this->uploadDir . $user['profile_picture'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Limpa os campos de foto no banco de dados
        $stmt = $this->db->prepare("UPDATE users SET profile_picture = NULL, photo = NULL WHERE id = ?");
        $result = $stmt->execute([$userId]);

        if ($result) {
            $this->sendJsonResponse(true, 'Foto de perfil removida com sucesso');
        } else {
            $this->sendJsonResponse(false, 'Erro ao remover a foto de perfil');
        }
    }

    /**
     * Atualiza as informações do perfil do usuário
     */
    public function updateProfile()
    {
        if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(false, 'Acesso não autorizado', 403);
        }

        $userId = $_SESSION['user_id'];
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        $gender = $_POST['gender'] ?? '';

        // Validação dos campos
        if (empty($name) || empty($email)) {
            $this->sendJsonResponse(false, 'Nome e email são obrigatórios');
        }

        // Atualiza as informações no banco de dados
        $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ?, cpf = ?, gender = ? WHERE id = ?");
        $result = $stmt->execute([$name, $email, $cpf, $gender, $userId]);

        if ($result) {
            $this->sendJsonResponse(true, 'Perfil atualizado com sucesso');
        } else {
            $this->sendJsonResponse(false, 'Erro ao atualizar o perfil');
        }
    }

    /**
     * Atualiza a senha do usuário
     */
    public function updatePassword()
    {
        if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(false, 'Acesso não autorizado', 403);
        }

        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validação das senhas
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->sendJsonResponse(false, 'Todos os campos de senha são obrigatórios');
        }

        if ($newPassword !== $confirmPassword) {
            $this->sendJsonResponse(false, 'A nova senha e a confirmação não coincidem');
        }

        // Verifica a senha atual
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($currentPassword, $user['password'])) {
            $this->sendJsonResponse(false, 'Senha atual incorreta');
        }

        // Atualiza a senha
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $result = $stmt->execute([$hashedPassword, $userId]);

        if ($result) {
            $this->sendJsonResponse(true, 'Senha atualizada com sucesso');
        } else {
            $this->sendJsonResponse(false, 'Erro ao atualizar a senha');
        }
    }

    /**
     * Envia uma resposta JSON
     * 
     * @param bool $success Indica se a operação foi bem-sucedida
     * @param string $message Mensagem de resposta
     * @param int $statusCode Código de status HTTP
     * @param array $extraData Dados adicionais para incluir na resposta
     */
    private function sendJsonResponse($success, $message, $statusCode = 200, $extraData = [])
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $extraData));
        exit();
    }
}
