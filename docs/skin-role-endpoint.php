<?php
/**
 * YITMC 皮肤站角色查询端点（部署在皮肤站 Web 根目录，不是官网服务器）。
 *
 * 用途：官网 OIDC 登录时读取皮肤站 uid => permission，用于映射官网管理员：
 *   1 = 管理员，2 = 超级管理员。
 *
 * 部署：
 *   1. 放到皮肤站 public 目录：/var/www/skin.uemcraft.cn/public_html/public/yitmc-admins.php
 *   2. 所有者 www-data:www-data，权限 644。
 *   3. 仅允许官网服务器 IP 访问；如官网服务器 IP 变更，修改 $allowed。
 *
 * 该端点只返回 uid 与权限位，不返回邮箱、昵称或其他个人信息。
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
$allowed = ['39.105.95.22', '127.0.0.1', '::1'];
if (!in_array($remote, $allowed, true)) {
    http_response_code(403);
    echo '{"ok":false}';
    exit;
}

$envFile = dirname(__DIR__) . '/.env';
if (!is_file($envFile)) {
    http_response_code(500);
    echo '{"ok":false}';
    exit;
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $value = trim($value);
    if (strlen($value) >= 2) {
        $first = $value[0];
        $last = substr($value, -1);
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $value = substr($value, 1, -1);
        }
    }
    $env[trim($key)] = $value;
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$database = $env['DB_DATABASE'] ?? '';
$username = $env['DB_USERNAME'] ?? '';
$password = $env['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $database . ';charset=utf8mb4',
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
    );
    $rows = $pdo->query('SELECT uid, permission FROM users WHERE permission >= 1')->fetchAll(PDO::FETCH_ASSOC);
    $admins = [];
    foreach ($rows as $row) {
        $admins[(string) $row['uid']] = (int) $row['permission'];
    }
    echo json_encode(['ok' => true, 'admins' => $admins], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    http_response_code(500);
    echo '{"ok":false}';
}
