<?php

require_once BASE_PATH . '/src/controllers/AuthController.php';

class DashboardController
{
    private $db;
    private $authController;

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

        // Buscar informações do usuário
        $stmt = $this->db->prepare("SELECT name, email, photo FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Buscar nome da empresa
        $stmt = $this->db->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        $companyName = $company['name'];

        // Buscar estatísticas
        $totalRecordings = $this->getTotalRecordings($companyId);
        $todayRecordings = $this->getTodayRecordings($companyId);
        $activeUsers = $this->authController->getActiveUsersCount();

        // Verificar se o usuário é admin
        $is_admin = $this->isUserAdmin($userId);

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

        if ($this->isUserAdmin($userId)) {
            $stats['totalCompanies'] = $this->getTotalCompanies();
            $stats['totalUsers'] = $this->getTotalUsers();
            $stats['activeLicenses'] = $this->getActiveLicenses();
        }

        header('Content-Type: application/json');
        echo json_encode($stats);
    }

    /**
     * Retorna o total de gravações para uma empresa
     */
    private function getTotalRecordings($companyId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM recordings WHERE company_id = ?");
        $stmt->execute([$companyId]);
        return $stmt->fetchColumn();
    }

    /**
     * Retorna o total de gravações de hoje para uma empresa
     */
    private function getTodayRecordings($companyId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM recordings WHERE company_id = ? AND DATE(created_at) = CURDATE()");
        $stmt->execute([$companyId]);
        return $stmt->fetchColumn();
    }

    /**
     * Verifica se um usuário é administrador
     */
    private function isUserAdmin($userId)
    {
        $stmt = $this->db->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() == 1;
    }

    /**
     * Retorna o total de empresas
     */
    private function getTotalCompanies()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM companies");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Retorna o total de usuários
     */
    private function getTotalUsers()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Retorna o total de licenças ativas
     */
    private function getActiveLicenses()
    {
        $stmt = $this->db->prepare("SELECT SUM(used_licenses) FROM licenses");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
