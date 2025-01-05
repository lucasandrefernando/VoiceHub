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


require_once BASE_PATH . '/src/controllers/UserPermissionsController.php';

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
    return isset($_SESSION['user_permissions']['administrador_sistema']) && $_SESSION['user_permissions']['administrador_sistema'] == 1;
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
        $controller = new LoginController();
        $controller->login();
        break;

    case '/register':
        // Página de registro
        $controller = new RegisterController();
        $controller->register();
        break;

    case '/check-first-access':
        // Verificar primeiro acesso
        $controller = new LoginController();
        $controller->checkFirstAccess();
        break;

    case '/forgot-password':
        // Página de recuperação de senha
        $controller = new PasswordResetController();
        $controller->forgotPassword();
        break;

    case '/reset-password':
        // Página de redefinição de senha
        $controller = new PasswordResetController();
        $controller->resetPassword();
        break;

    case '/new-password':
        // Página para definir nova senha
        $controller = new PasswordResetController();
        $controller->newPassword();
        break;

    case '/verify-code':
        // Verificação de código
        $controller = new RegisterController();
        $controller->verifyCode();
        break;

    case '/resend-verification-code':
        // Reenvio de código de verificação
        $controller = new RegisterController();
        $controller->resendVerificationCode();
        break;

    case '/logout':
        // Logout do usuário
        $controller = new LoginController();
        $controller->logout();
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
        $controller = new CompanyController();
        $controller->index();
        break;

    case (preg_match('/^\/companies\/create$/', $request_uri) ? true : false):
        $controller = new CompanyController();
        $controller->create();
        break;

    case (preg_match('/^\/companies\/update\/(\d+)$/', $request_uri, $matches) ? true : false):
        $controller = new CompanyController();
        $controller->update($matches[1]);
        break;

    case (preg_match('/^\/companies\/delete\/(\d+)$/', $request_uri, $matches) ? true : false):
        $controller = new CompanyController();
        $controller->delete($matches[1]);
        break;

    case '/companies/search-cnpj':
        $controller = new CompanyController();
        $controller->searchByCNPJ();
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


    case '/admin':
        if (isAdmin()) {
            $controller = new AdminController();
            $controller->dashboard();
        } else {
            header("Location: " . BASE_URL . "/login");
            exit();
        }
        break;

    case '/admin/add-company':
        if (isAdmin()) {
            $controller = new AdminController();
            $controller->addCompany();
        } else {
            header("Location: " . BASE_URL . "/login");
            exit();
        }
        break;

    case '/admin/update-licenses':
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
            header("Location: " . BASE_URL . "/dashboard");
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
        $controller = new RegisterController();
        $controller->resendVerificationCode();
        break;


    case (preg_match('/^\/admin\/get-user-permissions\/(\d+)$/', $request_uri, $matches) ? true : false):
        if (isAdmin()) {
            $userId = $matches[1];
            $controller = new UserPermissionsController();
            $controller->getUserPermissions($userId);
        } else {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['error' => 'Acesso não autorizado']);
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

    case '/user/profile':
        $controller = new UserProfileController();
        $controller->index();
        break;

    case '/user/update-profile':
        $controller = new UserProfileController();
        $controller->updateProfile();
        break;

    case '/user/update-password':
        $controller = new UserProfileController();
        $controller->updatePassword();
        break;

    case '/user/update-profile-picture':
        $controller = new UserProfileController();
        $controller->updateProfilePicture();
        break;
        // No seu arquivo de rotas (index.php ou similar)

    case '/user/remove-profile-picture':
        $controller = new UserProfileController();
        $controller->removeProfilePicture();
        break;

    default:
        // Página não encontrada
        header("HTTP/1.0 404 Not Found");
        require_once BASE_PATH . '/src/views/404.php';
        break;
}
