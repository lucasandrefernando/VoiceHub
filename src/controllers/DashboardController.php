<?php

require_once BASE_PATH . '/src/controllers/AuthController.php';

/**
 * DashboardController
 * 
 * Controlador responsável por gerenciar as funcionalidades do dashboard
 */
class DashboardController
{
    /** @var PDO $db Conexão com o banco de dados */
    private $db;

    /** @var AuthController $authController Instância do controlador de autenticação */
    private $authController;

    /**
     * Construtor da classe
     * Inicializa a conexão com o banco de dados e o controlador de autenticação
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->authController = new AuthController();
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
        $totalRecordings = $this->getTotalRecordings($companyId);
        $todayRecordings = $this->getTodayRecordings($companyId);
        $activeUsers = $this->authController->getActiveUsersCount();

        // Buscar permissões do usuário
        $userPermissions = $this->getUserPermissions($userId);

        // Verificar se o usuário é admin do sistema
        $is_admin = isset($userPermissions['administrador_sistema']) && $userPermissions['administrador_sistema'] == 1;

        if ($is_admin) {
            // Buscar estatísticas de admin
            $totalCompanies = $this->getTotalCompanies();
            $totalUsers = $this->getTotalUsers();
            $activeLicenses = $this->getActiveLicenses();
        }

        $photoPath = $user['photo'] ? BASE_URL . '/uploads/profile_pictures/' . $user['photo'] : null;

        // Carregar a view do dashboard
        require_once BASE_PATH . '/src/views/dashboard.php';
    }

    /**
     * Retorna as estatísticas atualizadas em formato JSON
     */
    public function getStats()
    {
        $companyId = $_SESSION['company_id'];
        $userId = $_SESSION['user_id'];

        $stats = [
            'totalRecordings' => $this->getTotalRecordings($companyId),
            'todayRecordings' => $this->getTodayRecordings($companyId),
            'activeUsers' => $this->authController->getActiveUsersCount()
        ];

        $userPermissions = $this->getUserPermissions($userId);
        $is_admin = isset($userPermissions['administrador_sistema']) && $userPermissions['administrador_sistema'] == 1;

        if ($is_admin) {
            $stats['totalCompanies'] = $this->getTotalCompanies();
            $stats['totalUsers'] = $this->getTotalUsers();
            $stats['activeLicenses'] = $this->getActiveLicenses();
        }

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
        $stmt = $this->db->prepare("SELECT name, email, photo FROM users WHERE id = ?");
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
     * Retorna o total de gravações para uma empresa
     * @param int $companyId ID da empresa
     * @return int
     */
    private function getTotalRecordings($companyId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM recordings WHERE company_id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetchColumn();
    }

    /**
     * Retorna o total de gravações de hoje para uma empresa
     * @param int $companyId ID da empresa
     * @return int
     */
    private function getTodayRecordings($companyId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM recordings WHERE company_id = ? AND DATE(created_at) = CURDATE()");
        $stmt->execute([$companyId]);
        return $stmt->fetchColumn();
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

    /**
     * Retorna o total de empresas
     * @return int
     */
    private function getTotalCompanies()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM companies");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Retorna o total de usuários
     * @return int
     */
    private function getTotalUsers()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Retorna o total de licenças ativas
     * @return int
     */
    private function getActiveLicenses()
    {
        $stmt = $this->db->prepare("SELECT SUM(used_licenses) FROM licenses");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
