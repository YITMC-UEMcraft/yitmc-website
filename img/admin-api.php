<?php
/**
 * YITMC 官网内容管理后台 - 服务端接口（单文件，无数据库依赖）
 *
 * 部署：随 dist 整包上传到网站根目录（与 index.html 同级）。
 * 首次上线前请在同目录创建 admin-config.php 配置管理密码（见 docs/admin-config.sample.php）。
 * 环境要求：PHP 7.4+（json 扩展默认开启），data/ 与 uploads/ 目录对 PHP 可写。
 */

declare(strict_types=1);

/* ==================== 配置区 ==================== */
// 管理密码不写入代码库：请在与 admin-api.php 同目录创建 admin-config.php，内容：
//   <?php return ['password' => '你的管理密码'];
// 样例见 docs/admin-config.sample.php。登录成功后建议在后台「设置」里修改密码
// （改后密码哈希落盘，此配置文件即可删除）。未创建该文件时后台保持锁定。
$DEFAULT_PASSWORD = '';
$adminConfigFile = __DIR__ . '/admin-config.php';
if (is_file($adminConfigFile)) {
    $adminConfig = require $adminConfigFile;
    if (is_array($adminConfig)) {
        $DEFAULT_PASSWORD = (string) ($adminConfig['password'] ?? '');
    }
}
unset($adminConfigFile, $adminConfig);
/* ================================================ */

ini_set('display_errors', '0');
error_reporting(E_ALL);

session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 14,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

define('ALLOWED_FILES', ['site-config.json', 'works.json', 'news.json', 'members.json', 'stats.json', 'events.json', 'docs.json']);
define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
define('MAX_UPLOAD_BYTES', 20 * 1024 * 1024);

function respond(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, int $code = 400, string $errorCode = ''): void
{
    respond(['ok' => false, 'error' => $message, 'code' => $errorCode], $code);
}

function requireAuth(): void
{
    if (empty($_SESSION['yitmc_admin'])) {
        fail('未登录或登录已过期', 401, 'AUTH_REQUIRED');
    }
}

/* ---------- 密码存储 ---------- */
function authFile(): string
{
    // 优先放在网站根目录之外，避免被当作静态文件下载
    $candidates = [
        dirname(__DIR__) . '/yitmc-admin/auth.json',
        sys_get_temp_dir() . '/yitmc-admin-auth-' . md5(__DIR__) . '.json',
        __DIR__ . '/data/.admin-auth.json',
    ];
    foreach ($candidates as $file) {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            return $file;
        }
    }
    return $candidates[count($candidates) - 1];
}

function setPasswordHash(string $plain): bool
{
    $file = authFile();
    $json = json_encode([
        'hash' => password_hash($plain, PASSWORD_DEFAULT),
        'updated' => time(),
    ], JSON_UNESCAPED_UNICODE);
    return $json !== false && file_put_contents($file, $json, LOCK_EX) !== false;
}

function storedPasswordHash(): ?string
{
    $file = authFile();
    if (is_file($file)) {
        $json = json_decode((string) file_get_contents($file), true);
        if (is_array($json) && !empty($json['hash']) && is_string($json['hash'])) {
            return $json['hash'];
        }
    }
    return null;
}

/* ---------- 数据读写 ---------- */
function dataDir(): string
{
    return __DIR__ . '/data';
}

function uploadDir(): string
{
    return __DIR__ . '/uploads';
}

function loadDataFiles(): array
{
    $files = [];
    foreach (ALLOWED_FILES as $name) {
        $path = dataDir() . '/' . $name;
        $content = is_file($path) ? file_get_contents($path) : false;
        $decoded = $content !== false ? json_decode($content, true) : null;
        $files[$name] = is_array($decoded) ? $decoded : new stdClass();
    }
    return $files;
}

function slugify(string $name): string
{
    $base = pathinfo($name, PATHINFO_FILENAME);
    $base = preg_replace('/[^A-Za-z0-9_-]+/', '-', trim($base)) ?? '';
    $base = trim($base, '-');
    return $base !== '' ? $base : 'image';
}

/* ---------- 主流程 ---------- */
$action = isset($_POST['action']) ? (string) $_POST['action'] : '';
if ($action === '') {
    $raw = file_get_contents('php://input');
    if ($raw !== false && $raw !== '') {
        $body = json_decode($raw, true);
        if (is_array($body) && isset($body['action'])) {
            $action = (string) $body['action'];
            unset($body['action']);
            $GLOBALS['jsonBody'] = $body;
        } else {
            $GLOBALS['jsonBody'] = [];
        }
    } else {
        $GLOBALS['jsonBody'] = [];
    }
} else {
    $GLOBALS['jsonBody'] = [];
}

switch ($action) {
    case 'ping':
        respond(['ok' => true, 'authed' => !empty($_SESSION['yitmc_admin'])]);

    case 'login':
        $fails = isset($_SESSION['yitmc_fails']) ? (int) $_SESSION['yitmc_fails'] : 0;
        $lastFail = isset($_SESSION['yitmc_last_fail']) ? (int) $_SESSION['yitmc_last_fail'] : 0;
        if ($fails >= 5 && time() - $lastFail < 600) {
            fail('尝试次数过多，请 10 分钟后再试', 429);
        }
        $input = !empty($GLOBALS['jsonBody']) ? $GLOBALS['jsonBody'] : $_POST;
        $password = isset($input['password']) ? (string) $input['password'] : '';
        if ($DEFAULT_PASSWORD === '' && storedPasswordHash() === null) {
            fail('后台未配置管理密码：请在服务器上创建 admin-config.php（参见 docs/admin-config.sample.php）', 503, 'PASSWORD_NOT_CONFIGURED');
        }
        $hash = storedPasswordHash() ?? password_hash((string) $DEFAULT_PASSWORD, PASSWORD_DEFAULT);
        if ($password !== '' && password_verify($password, $hash)) {
            session_regenerate_id(true);
            $_SESSION['yitmc_admin'] = true;
            unset($_SESSION['yitmc_fails'], $_SESSION['yitmc_last_fail']);
            respond(['ok' => true, 'files' => loadDataFiles()]);
        }
        $_SESSION['yitmc_fails'] = $fails + 1;
        $_SESSION['yitmc_last_fail'] = time();
        fail('密码不正确', 401);

    case 'logout':
        unset($_SESSION['yitmc_admin']);
        respond(['ok' => true]);

    case 'state':
        requireAuth();
        respond(['ok' => true, 'files' => loadDataFiles(), 'pwIsDefault' => $DEFAULT_PASSWORD !== '' && storedPasswordHash() === null]);

    case 'save':
        requireAuth();
        $input = !empty($GLOBALS['jsonBody']) ? $GLOBALS['jsonBody'] : $_POST;
        $file = isset($input['file']) ? (string) $input['file'] : '';
        if (!in_array($file, ALLOWED_FILES, true)) {
            fail('不允许的数据文件名', 400);
        }
        $content = isset($input['content']) ? $input['content'] : null;
        if (!is_array($content)) {
            fail('保存的内容格式错误', 400);
        }
        if (!is_dir(dataDir())) {
            @mkdir(dataDir(), 0755, true);
        }
        if (!is_writable(dataDir())) {
            fail('服务器 data/ 目录不可写，请联系维护者检查目录权限', 500);
        }
        $encoded = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($encoded === false) {
            fail('内容编码失败', 500);
        }
        $tmp = dataDir() . '/' . $file . '.tmp';
        if (file_put_contents($tmp, $encoded . "\n", LOCK_EX) === false || !rename($tmp, dataDir() . '/' . $file)) {
            fail('写入文件失败，请联系维护者', 500);
        }
        respond(['ok' => true, 'savedAt' => date('c')]);

    case 'upload':
        requireAuth();
        $up = isset($_FILES['file']) ? $_FILES['file'] : null;
        if (!is_array($up) || !isset($up['tmp_name']) || $up['error'] !== UPLOAD_ERR_OK) {
            fail('上传失败，请重试', 400);
        }
        if ((isset($up['size']) ? (int) $up['size'] : 0) > MAX_UPLOAD_BYTES) {
            fail('图片不能超过 20MB，或压缩后再上传', 413);
        }
        $ext = strtolower(pathinfo((string) ($up['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXT, true)) {
            fail('仅支持 jpg / png / webp / gif 图片', 415);
        }
        $mime = '';
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = (string) finfo_file($fi, (string) $up['tmp_name']);
                finfo_close($fi);
            }
        }
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if ($mime !== '' && !in_array($mime, $allowedMimes, true)) {
            fail('文件不是有效的图片', 415);
        }
        if (!is_dir(uploadDir())) {
            @mkdir(uploadDir(), 0755, true);
        }
        if (!is_writable(uploadDir())) {
            fail('服务器 uploads/ 目录不可写，请联系维护者检查目录权限', 500);
        }
        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '-' . slugify((string) $up['name']) . '.' . $ext;
        $dest = uploadDir() . '/' . $filename;
        if (!move_uploaded_file((string) $up['tmp_name'], $dest)) {
            fail('保存图片失败，请联系维护者', 500);
        }
        @chmod($dest, 0644);
        respond(['ok' => true, 'url' => '/uploads/' . $filename]);

    case 'password':
        requireAuth();
        $input = !empty($GLOBALS['jsonBody']) ? $GLOBALS['jsonBody'] : $_POST;
        $oldPw = isset($input['oldPassword']) ? (string) $input['oldPassword'] : '';
        $newPw = isset($input['newPassword']) ? (string) $input['newPassword'] : '';
        $hash = storedPasswordHash() ?? password_hash((string) $DEFAULT_PASSWORD, PASSWORD_DEFAULT);
        if ($oldPw === '' || !password_verify($oldPw, $hash)) {
            fail('当前密码不正确', 403);
        }
        if (strlen($newPw) < 8) {
            fail('新密码至少 8 位', 400);
        }
        if (!setPasswordHash($newPw)) {
            fail('写入新密码失败，请联系维护者', 500);
        }
        respond(['ok' => true]);

    default:
        fail('未知操作', 404);
}
