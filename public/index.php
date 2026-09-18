<?php

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Seeders\AdminSeeder;
use Core\JwtHandler;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));
define('PROJECT_URL', '/work-hr-management');
define('APP_URL', '/work-hr-management/public');

// Composer autoloader hoặc Fallback PSR-4 Autoloader
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
} else {
    // Tự động load class chuẩn PSR-4 khi chưa chạy composer install
    spl_autoload_register(function ($class) {
        $prefixes = [
            'App\\'  => BASE_PATH . '/app/',
            'Core\\' => BASE_PATH . '/core/',
        ];

        foreach ($prefixes as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }

            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
                return;
            }

            // Hỗ trợ thư mục viết thường trong app (services, enums, etc.)
            $parts = explode('\\', $relativeClass);
            if (count($parts) > 1) {
                $parts[0] = strtolower($parts[0]);
                $fileLower = $baseDir . implode('/', $parts) . '.php';
                if (file_exists($fileLower)) {
                    require_once $fileLower;
                    return;
                }
            }
        }
    });
}

// Đọc cấu hình từ file .env
if (file_exists(BASE_PATH . '/.env')) {
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
        $dotenv->load();
    } else {
        // Fallback đọc file .env thuần PHP không phụ thuộc thư viện ngoài
        $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                // Gỡ bỏ dấu ngoặc kép hoặc đơn bọc ngoài nếu có
                if (preg_match('/^([\'"])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
}


/**
 * Normalize JWT payload vì JwtHandler có thể trả object hoặc array.
 */
function cah_normalize_auth_payload($payload): ?array {
    if (!$payload) {
        return null;
    }

    if (is_object($payload)) {
        $payload = (array)$payload;
    }

    if (!is_array($payload)) {
        return null;
    }

    if (isset($payload['data']) && (is_array($payload['data']) || is_object($payload['data']))) {
        $payload = (array)$payload['data'];
    }

    if (empty($payload['id']) || empty($payload['role'])) {
        return null;
    }

    return [
        'id' => (int)$payload['id'],
        'email' => $payload['email'] ?? null,
        'role' => strtolower((string)$payload['role']),
        'full_name' => $payload['full_name'] ?? ($payload['name'] ?? null),
    ];
}

/**
 * Khôi phục session từ cookie token.
 * Mục tiêu: header/sidebar không bị lấy role cũ từ session/localStorage.
 */
$token = $_COOKIE['cah_token'] ?? null;

if ($token) {
    try {
        $jwt = new JwtHandler();
        $decoded = $jwt->decode($token);
        $authPayload = cah_normalize_auth_payload($decoded);

        if ($authPayload) {
            $_SESSION['user_id'] = $authPayload['id'];
            $_SESSION['user_email'] = $authPayload['email'];
            $_SESSION['user_role'] = $authPayload['role'];
            $_SESSION['full_name'] = $authPayload['full_name'];
        }
    } catch (Throwable $e) {
        unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_role'], $_SESSION['full_name']);
    }
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, user_id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$path = $uri;

if (strpos($path, APP_URL) === 0) {
    $path = substr($path, strlen(APP_URL));
} elseif (strpos($path, PROJECT_URL . '/public') === 0) {
    $path = substr($path, strlen(PROJECT_URL . '/public'));
} elseif (strpos($path, PROJECT_URL) === 0) {
    $path = substr($path, strlen(PROJECT_URL));
} elseif (strpos($path, '/public') === 0) {
    $path = substr($path, strlen('/public'));
}

$path = '/' . trim($path, '/');

if ($path === '//') {
    $path = '/';
}

/**
 * Load route file nếu tồn tại.
 */
function cah_load_routes(string $file): array {
    if (!file_exists($file)) {
        return [];
    }

    $routes = require $file;

    return is_array($routes) ? $routes : [];
}

/**
 * Thứ tự:
 * - admin/staff/client trước để route luồng bắt trước
 * - api sau cùng cho API dùng chung
 */
$routes = array_merge(
    cah_load_routes(BASE_PATH . '/routes/admin.php'),
    cah_load_routes(BASE_PATH . '/routes/staff.php'),
    cah_load_routes(BASE_PATH . '/routes/client.php'),
    cah_load_routes(BASE_PATH . '/routes/api.php')
);

/**
 * Resolve controller class linh hoạt theo cấu trúc hiện tại.
 */
function cah_resolve_controller_class(string $controllerName): ?string {
    $candidates = [];

    if (strpos($controllerName, '\\') !== false) {
        $candidates[] = 'App\\Controllers\\' . $controllerName;
    } else {
        $folders = [
            '',
            'Admin\\',
            'Auth\\',
            'Client\\',
            'HRM\\',
            'Task\\',
            'Project\\',
            'Payroll\\',
            'Core\\',
        ];

        foreach ($folders as $folder) {
            $candidates[] = 'App\\Controllers\\' . $folder . $controllerName;
        }
    }

    foreach ($candidates as $class) {
        if (class_exists($class)) {
            return $class;
        }
    }

    return null;
}

/**
 * Tạo controller, có truyền $authUser nếu constructor nhận tham số.
 */
function cah_make_controller(string $controllerClass, ?array $authUser = null) {
    $reflection = new ReflectionClass($controllerClass);
    $constructor = $reflection->getConstructor();

    if ($constructor && $constructor->getNumberOfParameters() >= 1) {
        return new $controllerClass($authUser);
    }

    return new $controllerClass();
}

/**
 * Redirect helper cho route closure.
 */
function cah_redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

try {
    // Tự động kiểm tra và khởi tạo tài khoản quản trị ban đầu nếu database còn trống
    if (class_exists(AdminSeeder::class)) {
        AdminSeeder::run();
    }

    foreach ($routes as $route) {
        [$routeMethod, $routePath, $handler, $roles] = array_pad($route, 4, null);

        $routePath = '/' . trim((string)$routePath, '/');
        $pattern = preg_replace('#:(\w+)#', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if ($method !== $routeMethod || !preg_match($pattern, $path, $matches)) {
            continue;
        }

        array_shift($matches);

        if (strpos($path, '/api/') === 0) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $authUser = null;

        if ($roles !== null) {
            $authUser = AuthMiddleware::check(strpos($path, '/api/') === 0);
            RoleMiddleware::handle($authUser, $roles);
        }

        if (is_callable($handler)) {
            return call_user_func_array($handler, $matches);
        }

        if (!is_string($handler) || strpos($handler, '@') === false) {
            throw new Exception('Invalid route handler for path: ' . $routePath);
        }

        [$controllerName, $action] = explode('@', $handler, 2);
        $controllerClass = cah_resolve_controller_class($controllerName);

        if (!$controllerClass) {
            throw new Exception('Controller not found: ' . $controllerName);
        }

        $controller = cah_make_controller($controllerClass, $authUser);

        if (!method_exists($controller, $action)) {
            throw new Exception("Method {$action} not found in {$controllerClass}");
        }

        call_user_func_array([$controller, $action], $matches);
        exit;
    }

    /**
     * View resolver giữ tương thích cấu trúc app/View hiện tại.
     * Đợt sau mới move sang app/View/admin, staff, client sâu hơn.
     */
    if (strpos($path, '/app/View/') === 0) {
        $viewPath = BASE_PATH . $path;
    } else {
        $viewPath = BASE_PATH . '/app/View' . $path;
    }

    if (file_exists($viewPath) && is_file($viewPath)) {
        header('Content-Type: text/html; charset=utf-8');
        require_once $viewPath;
        exit;
    }

    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>404 Not Found</h1>';
    echo '<p>Path: ' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '</p>';
} catch (Throwable $e) {
    if (strpos($path, '/api/') === 0) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Internal Server Error</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}