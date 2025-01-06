<?php

require_once BASE_PATH . '/src/controllers/LoginController.php';

/**
 * DashboardController
 * 
 * Controlador responsável por gerenciar as funcionalidades do dashboard
 */
class DashboardController
{
    /** @var PDO $db Conexão com o banco de dados */
    private $db;

    /** @var LoginController $loginController Instância do controlador de login */
    private $loginController;

    /**
     * Construtor da classe
     * Inicializa a conexão com o banco de dados e o controlador de login
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->loginController = new LoginController();
    }

    /**
     * Exibe a página principal do dashboard
     */
    public function index()
    {
        // Verificar se o usuário está logado
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        $userId = $_SESSION['user_id'];
        $companyId = $_SESSION['company_id'];

        // Verificar se a sessão é válida
        if (!$this->isValidSession($userId, $companyId)) {
            session_destroy();
            header("Location: " . BASE_URL . "/login");
            exit();
        }

        // Buscar permissões do usuário
        $userPermissions = $this->getUserPermissions($userId);
        error_log("User Permissions: " . print_r($userPermissions, true));

        // Buscar informações do usuário
        $user = $this->getUserInfo($userId);

        // Buscar nome da empresa
        $companyName = $this->getCompanyName($companyId);

        // Buscar estatísticas
        $totalRecordings = $this->getTotalRecordings();
        $todayRecordings = $this->getTodayRecordings();
        $activeUsers = $this->loginController->getActiveUsersCount();

        // Determinar o caminho da foto do usuário
        $photoPath = $this->getUserPhotoPath($user);

        // Log para depuração
        error_log("User Photo Path: " . $photoPath);

        // Carregar a view do dashboard
        require_once BASE_PATH . '/src/views/dashboard.php';
    }

    /**
     * Determina o caminho da foto do usuário
     * @param array $user Informações do usuário
     * @return string|null
     */
    private function getUserPhotoPath($user)
    {
        if (!empty($user['profile_picture'])) {
            return BASE_URL . '/uploads/profile_pictures/' . $user['profile_picture'];
        }

        if (!empty($user['photo'])) {
            $tempFileName = 'temp_' . uniqid() . '.jpg';
            $tempFilePath = BASE_PATH . '/public/uploads/profile_pictures/' . $tempFileName;
            file_put_contents($tempFilePath, $user['photo']);
            return BASE_URL . '/uploads/profile_pictures/' . $tempFileName;
        }

        return BASE_URL . '/assets/images/profile.png';
    }

    /**
     * Retorna as estatísticas atualizadas em formato JSON
     */
    public function getStats()
    {
        $stats = [
            'totalRecordings' => $this->getTotalRecordings(),
            'todayRecordings' => $this->getTodayRecordings(),
            'activeUsers' => $this->loginController->getActiveUsersCount()
        ];

        header('Content-Type: application/json');
        echo json_encode($stats);
        exit;
    }

    /**
     * Verifica se a sessão do usuário é válida
     * @param int $userId ID do usuário
     * @param int $companyId ID da empresa
     * @return bool
     */
    private function isValidSession($userId, $companyId)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? AND company_id = ?");
        $stmt->execute([$userId, $companyId]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Busca informações do usuário
     * @param int $userId ID do usuário
     * @return array
     */
    private function getUserInfo($userId)
    {
        $stmt = $this->db->prepare("SELECT name, email, photo, profile_picture FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca o nome da empresa
     * @param int $companyId ID da empresa
     * @return string
     */
    private function getCompanyName($companyId)
    {
        $stmt = $this->db->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetchColumn();
    }

    /**
     * Retorna o total de gravações
     * @return int
     */
    private function getTotalRecordings()
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM records");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erro ao buscar total de gravações: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Retorna o total de gravações de hoje
     * @return int
     */
    private function getTodayRecordings()
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM records WHERE DATE(created_at) = CURDATE()");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erro ao buscar gravações de hoje: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Retorna as permissões do usuário
     * @param int $userId ID do usuário
     * @return array
     */
    private function getUserPermissions($userId)
    {
        $stmt = $this->db->prepare("SELECT permission_key, value FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
