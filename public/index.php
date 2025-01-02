<?php

/**
 * Arquivo principal de roteamento da aplicação VoiceHub
 * 
 * Este arquivo é responsável por definir as rotas da aplicação,
 * inicializar configurações globais e gerenciar o fluxo de requisições.
 */

// Definir o caminho base do projeto
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/voicehub/public');

// Incluir o autoloader do Composer
require_once BASE_PATH . '/vendor/autoload.php';

// Função de autoload para os controllers
spl_autoload_register(function ($class_name) {
    $file = BASE_PATH . '/src/controllers/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    } else {
        error_log("Arquivo não encontrado: $file");
    }
});

// Carregar variáveis de ambiente
$dotenvPath = BASE_PATH . '/.env';
if (file_exists($dotenvPath)) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
} else {
    die("Arquivo .env não encontrado. Por favor, crie o arquivo .env na raiz do projeto.");
}

// Verificar se as variáveis de ambiente necessárias estão definidas
$required_env_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'TRANSKRIPTOR_API_KEY'];
foreach ($required_env_vars as $var) {
    if (!isset($_ENV[$var])) {
        die("Variável de ambiente $var não está definida no arquivo .env");
    }
}

// Incluir configurações
require_once BASE_PATH . '/src/config/database.php';

// Incluir funções auxiliares
if (file_exists(BASE_PATH . '/src/helpers/functions.php')) {
    require_once BASE_PATH . '/src/helpers/functions.php';
}

// Iniciar a sessão
session_start();

/**
 * Verifica se o usuário está logado
 *
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Verifica se o usuário é um administrador
 *
 * @return bool
 */
function isAdmin()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Obter a URI atual e remover o prefixo '/voicehub/public'
$request_uri = $_SERVER['REQUEST_URI'];
$prefix = '/voicehub/public';
if (strpos($request_uri, $prefix) === 0) {
    $request_uri = substr($request_uri, strlen($prefix));
}
$request_uri = rtrim($request_uri, '/');
if (empty($request_uri)) {
    $request_uri = '/';
}

// Lista de rotas públicas (acessíveis sem login)
$public_routes = ['/login', '/register', '/confirm', '/forgot-password', '/reset-password', '/terms', '/privacy', '/verify-code', '/resend-verification'];

// Roteamento básico
if (!isLoggedIn() && !in_array($request_uri, $public_routes)) {
    // Se o usuário não está logado e não está tentando acessar uma rota pública,
    // redireciona para a página de login
    header("Location: " . BASE_URL . "/login");
    exit();
}

// Roteamento principal
switch ($request_uri) {
    case '/':
        // Página inicial
        if (isLoggedIn()) {
            $controller = new HomeController();
            $controller->index();
        } else {
            header("Location: " . BASE_URL . "/login");
            exit();
        }
        break;

    case '/login':
        // Página de login
        $controller = new AuthController();
        $controller->login();
        break;

    case '/register':
        // Página de registro
        $controller = new AuthController();
        $controller->register();
        break;

    case '/check-first-access':
        // Verificar primeiro acesso
        $controller = new AuthController();
        $controller->checkFirstAccess();
        break;

    case '/forgot-password':
        // Página de recuperação de senha
        $controller = new AuthController();
        $controller->forgotPassword();
        break;

    case '/reset-password':
        // Página de redefinição de senha
        $controller = new AuthController();
        $controller->resetPassword();
        break;

    case '/dashboard/stats':
        if (isLoggedIn()) {
            $controller = new DashboardController();
            $controller->getStats();
        } else {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['error' => 'Acesso não autorizado']);
        }
        break;

    case '/companies':
        if (isAdmin() && isset($_SESSION['user_permissions']['gerenciar_empresas']) && $_SESSION['user_permissions']['gerenciar_empresas'] == 1) {
            $controller = new CompanyController();
            $controller->index();
        } else {
            header("Location: " . BASE_URL . "/dashboard");
            exit();
        }
        break;

    case '/companies/create':
        if (isAdmin() && isset($_SESSION['user_permissions']['gerenciar_empresas']) && $_SESSION['user_permissions']['gerenciar_empresas'] == 1) {
            $controller = new CompanyController();
            $controller->create();
        } else {
            header("Location: " . BASE_URL . "/dashboard");
            exit();
        }
        break;

    case '/companies/store':
        if (isAdmin() && isset($_SESSION['user_permissions']['gerenciar_empresas']) && $_SESSION['user_permissions']['gerenciar_empresas'] == 1) {
            $controller = new CompanyController();
            $controller->store();
        } else {
            header("Location: " . BASE_URL . "/dashboard");
            exit();
        }
        break;

    case '/new-password':
        // Página para definir nova senha
        $controller = new AuthController();
        $controller->newPassword();
        break;

    case '/verify-code':
        // Redirecionar para login (verificação de código)
        header("Location: " . BASE_URL . "/login");
        exit();

    case '/resend-verification-code':
        // Redirecionar para login (reenvio de código de verificação)
        header("Location: " . BASE_URL . "/login");
        exit();

    case '/logout':
        // Logout do usuário
        $controller = new AuthController();
        $controller->logout();
        break;

    case '/dashboard':
        // Página do dashboard
        if (isLoggedIn()) {
            $controller = new DashboardController();
            $controller->index();
        } else {
            header("Location: " . BASE_URL . "/login");
            exit();
        }
        break;

    case '/recordings':
        // Página de gravações
        if (isLoggedIn()) {
            $controller = new RecordingController();
            $controller->index();
        } else {
            header("Location: " . BASE_URL . "/login");
            exit();
        }
        break;

    case (preg_match('/^\/recording\/([a-f0-9]{32})$/', $request_uri, $matches) ? true : false):
        $recordingId = $matches[1];
        $controller = new RecordingController();
        $controller->view($recordingId);
        break;

    case '/admin':
        // Página de administração
        if (isAdmin()) {
            $controller = new AdminController();
            $controller->index();
        } else {
            header("Location: " . BASE_URL . "/login");
            exit();
        }
        break;

    case '/admin/add-company':
        // Adicionar nova empresa (admin)
        if (isAdmin()) {
            $controller = new AdminController();
            $controller->addCompany();
        } else {
            header("Location: " . BASE_URL . "/login");
            exit();
        }
        break;

    case '/admin/update-licenses':
        // Atualizar licenças (admin)
        if (isAdmin()) {
            $controller = new AdminController();
            $controller->updateLicenses();
        } else {
            header("Location: " . BASE_URL . "/login");
            exit();
        }
        break;

    case '/admin/user-permissions':
        if (isAdmin()) {
            $controller = new UserPermissionsController();
            $controller->index();
        } else {
            header("Location: " . BASE_URL . "/login");
            exit();
        }
        break;

    case '/admin/update-permissions':
        if (isAdmin() && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new UserPermissionsController();
            $controller->updatePermissions();
        } else {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
        }
        break;

    case '/resend-verification':
        // Reenviar código de verificação
        $controller = new AuthController();
        $controller->resendVerificationCode();
        break;

    case (preg_match('/^\/recording\/(\d+)$/', $request_uri, $matches) ? true : false):
        // Visualizar gravação específica
        if (isLoggedIn()) {
            try {
                $recordingId = $matches[1];
                $controller = new RecordingController();
                $controller->view($recordingId);
            } catch (Exception $e) {
                error_log("Erro ao visualizar gravação: " . $e->getMessage());
                header("HTTP/1.0 500 Internal Server Error");
                echo "Erro ao carregar a gravação. Por favor, tente novamente mais tarde.";
            }
        } else {
            header("Location: " . BASE_URL . "/login");
            exit();
        }
        break;

    case '/profile':
        // Página de perfil do usuário
        $controller = new ProfileController();
        $controller->index();
        break;

    case '/profile/update':
        // Atualizar perfil do usuário
        $controller = new ProfileController();
        $controller->update();
        break;

    case '/terms':
        // Página de termos de uso
        $controller = new AuthController();
        $controller->terms();
        break;

    case '/privacy':
        // Página de política de privacidade
        $controller = new AuthController();
        $controller->privacy();
        break;

    case (preg_match('/^\/admin\/get-user-permissions\/(\d+)$/', $request_uri, $matches) ? true : false):
        if (isAdmin()) {
            $userId = $matches[1];
            $controller = new UserPermissionsController();
            $controller->getUserPermissions($userId);
        } else {
            header("HTTP/1.0 403 Forbidden");
            exit();
        }
        break;

    case '/admin/verify-code':
        if (isAdmin() && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new UserPermissionsController();
            $controller->verifyCode();
        } else {
            header("HTTP/1.0 403 Forbidden");
            exit();
        }
        break;

    case '/admin/request-verification-code':
        if (isAdmin() && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new UserPermissionsController();
            $controller->requestVerificationCode();
        } else {
            header("HTTP/1.0 403 Forbidden");
            exit();
        }
        break;

    default:
        // Página não encontrada
        header("HTTP/1.0 404 Not Found");
        require_once BASE_PATH . '/src/views/404.php';
        break;
}
