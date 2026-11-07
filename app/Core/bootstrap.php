<?php
/**
 * BOOTSTRAP — učitava konfiguraciju, autoloader, sesiju, timezone i
 * error handling. Uključuje se na početku public/index.php i svakog
 * CLI/seeder skripta.
 */

error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__, 2));

$config = require BASE_PATH . '/config/config.php';
$GLOBALS['__config'] = $config;

ini_set('display_errors', $config['app']['debug'] ? '1' : '0');
ini_set('display_startup_errors', $config['app']['debug'] ? '1' : '0');

date_default_timezone_set($config['app']['timezone']);

// --- Jednostavan PSR-4-ish autoloader za /app -----------------------
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Helpers/helpers.php';

// --- Sesija ----------------------------------------------------------
$sessionCfg = $config['session'];

session_name($sessionCfg['name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Session timeout zbog neaktivnosti.
if (isset($_SESSION['zadnja_aktivnost'])) {
    $neaktivnost = time() - $_SESSION['zadnja_aktivnost'];
    if ($neaktivnost > $sessionCfg['lifetime']) {
        $_SESSION = [];
        session_unset();
        session_destroy();
        session_start();
    }
}
$_SESSION['zadnja_aktivnost'] = time();

// --- Error / exception handling --------------------------------------
set_exception_handler(function (\Throwable $e) use ($config) {
    error_log('[UNCAUGHT] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if ($config['app']['debug']) {
        echo '<pre style="padding:20px;background:#fff3f3;color:#900;font-family:monospace;">';
        echo htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString());
        echo '</pre>';
    } else {
        require BASE_PATH . '/app/Views/errors/500.php';
    }
});
