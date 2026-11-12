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

define('ALLOWED_FILES', ['site-config.json', 'works.json', 'news.json', 'members.json', 'stats.json', 'events.json', 'docs.json', 'servers.json', 'pcl.json']);
define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
define('MAX_UPLOAD_BYTES', 20 * 1024 * 1024);
define('WALL_MAX_NAME', 20);
define('WALL_MAX_CONTENT', 500);
define('WALL_RATE_SECONDS', 60);
define('WALL_MAX_PENDING', 300);

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

/** 只接受本站来源的写请求，降低 CSRF 风险（无 Origin 的非浏览器请求放行） */
function requireSameOrigin(): void
{
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? (string) $_SERVER['HTTP_ORIGIN'] : '';
    if ($origin === '') {
        return;
    }
    $parts = parse_url($origin);
    if (!is_array($parts) || empty($parts['host'])) {
        fail('非法请求来源', 403, 'BAD_ORIGIN');
    }
    $host = strtolower((string) $parts['host']);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if (in_array($host, ['www.yitmc.cn', 'yitmc.cn'], true)) {
        if ($scheme !== 'https') {
            fail('非法请求来源', 403, 'BAD_ORIGIN');
        }
        return;
    }
    if (in_array($host, ['localhost', '127.0.0.1'], true) && in_array($scheme, ['http', 'https'], true)) {
        return;
    }
    fail('非法请求来源', 403, 'BAD_ORIGIN');
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

/* ---------- 留言墙 ---------- */
/** 优先放到网站根目录之外；私密目录不可用时退到系统临时目录 */
function privateDataPath(string $name): string
{
    $privateDir = dirname(__DIR__) . '/yitmc-admin';
    if (!is_dir($privateDir)) {
        @mkdir($privateDir, 0755, true);
    }
    if (is_dir($privateDir) && is_writable($privateDir)) {
        return $privateDir . '/' . $name;
    }
    return sys_get_temp_dir() . '/yitmc-' . $name . '-' . md5(__DIR__) . '.json';
}

function wallPath(): string
{
    return privateDataPath('wall.json');
}

function wallDirWritable(): bool
{
    $dir = dirname(wallPath());
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return is_dir($dir) && is_writable($dir);
}

function wallEmpty(): array
{
    return ['messages' => [], 'rate' => []];
}

function normalizeWall(array $data): array
{
    if (!isset($data['messages']) || !is_array($data['messages'])) {
        $data['messages'] = [];
    }
    if (!isset($data['rate']) || !is_array($data['rate'])) {
        $data['rate'] = [];
    }
    return $data;
}

function readWall(): array
{
    $path = wallPath();
    if (!is_file($path)) {
        return wallEmpty();
    }
    $fh = @fopen($path, 'c+');
    if (!$fh) {
        return wallEmpty();
    }
    $data = wallEmpty();
    if (flock($fh, LOCK_SH)) {
        $raw = stream_get_contents($fh);
        $decoded = $raw !== false ? json_decode($raw, true) : null;
        if (is_array($decoded)) {
            $data = normalizeWall($decoded);
        }
        flock($fh, LOCK_UN);
    }
    fclose($fh);
    return $data;
}

function withWall(callable $callback)
{
    if (!wallDirWritable()) {
        fail('服务器留言数据目录不可写，请联系维护者检查目录权限', 500);
    }
    $fh = @fopen(wallPath(), 'c+');
    if (!$fh) {
        fail('无法打开留言数据，请稍后重试', 500);
    }
    if (!flock($fh, LOCK_EX)) {
        fclose($fh);
        fail('留言数据繁忙，请稍后重试', 503);
    }
    $raw = stream_get_contents($fh);
    $decoded = $raw !== false ? json_decode($raw, true) : null;
    $data = is_array($decoded) ? normalizeWall($decoded) : wallEmpty();
    $result = $callback($data);
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($encoded !== false) {
        rewind($fh);
        ftruncate($fh, 0);
        fwrite($fh, $encoded . "\n");
        fflush($fh);
    }
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

function charCount(string $text): int
{
    if (function_exists('mb_strlen')) {
        return (int) mb_strlen($text, 'UTF-8');
    }
    $count = preg_match_all('/./us', $text);
    return $count === false ? 0 : (int) $count;
}

function cleanUserText(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $clean = preg_replace('/[^\P{C}\n\t]/u', '', $text);
    if ($clean === null) {
        $clean = $text;
    }
    $clean = preg_replace("/\n{3,}/", "\n\n", $clean);
    return trim($clean === null ? $text : $clean);
}

function clientIpHash(): string
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
    return hash('sha256', $ip . '|yitmc-wall-v1');
}

function sortWallMessages(array $messages): array
{
    usort($messages, function ($a, $b) {
        $ta = (int) ($a['createdAt'] ?? 0);
        $tb = (int) ($b['createdAt'] ?? 0);
        if ($ta === $tb) {
            return (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0);
        }
        return $tb <=> $ta;
    });
    return $messages;
}

/* ---------- 资讯浏览量 ---------- */
function viewsPath(): string
{
    return privateDataPath('news-views.json');
}

function readNewsViews(): array
{
    $path = viewsPath();
    if (!is_file($path)) {
        return [];
    }
    $fh = @fopen($path, 'c+');
    if (!$fh) {
        return [];
    }
    $counts = [];
    if (flock($fh, LOCK_SH)) {
        $raw = stream_get_contents($fh);
        $decoded = $raw !== false ? json_decode($raw, true) : null;
        if (is_array($decoded)) {
            foreach ($decoded as $key => $value) {
                if (ctype_digit((string) $key)) {
                    $counts[(string) $key] = (int) $value;
                }
            }
        }
        flock($fh, LOCK_UN);
    }
    fclose($fh);
    return $counts;
}

function withNewsViews(callable $callback)
{
    $path = viewsPath();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        fail('服务器浏览量目录不可写，请联系维护者', 500);
    }
    $fh = @fopen($path, 'c+');
    if (!$fh) {
        fail('无法打开浏览量数据，请稍后重试', 500);
    }
    if (!flock($fh, LOCK_EX)) {
        fclose($fh);
        fail('浏览量数据繁忙，请稍后重试', 503);
    }
    $raw = stream_get_contents($fh);
    $decoded = $raw !== false ? json_decode($raw, true) : null;
    $data = [];
    if (is_array($decoded)) {
        foreach ($decoded as $key => $value) {
            if (ctype_digit((string) $key)) {
                $data[(string) $key] = (int) $value;
            }
        }
    }
    $result = $callback($data);
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($encoded !== false) {
        rewind($fh);
        ftruncate($fh, 0);
        fwrite($fh, $encoded . "\n");
        fflush($fh);
    }
    flock($fh, LOCK_UN);
    fclose($fh);
    return $result;
}

/* ---------- 图片库 ---------- */
function listUploads(): array
{
    $dir = uploadDir();
    if (!is_dir($dir)) {
        return [];
    }
    $items = [];
    $entries = @scandir($dir);
    if (!is_array($entries)) {
        return [];
    }
    foreach ($entries as $name) {
        if ($name === '' || $name[0] === '.') {
            continue;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXT, true)) {
            continue;
        }
        $path = $dir . '/' . $name;
        if (!is_file($path)) {
            continue;
        }
        $items[] = [
            'name' => $name,
            'url' => '/uploads/' . $name,
            'size' => (int) filesize($path),
            'mtime' => (int) filemtime($path),
        ];
    }
    usort($items, function ($a, $b) {
        return $b['mtime'] <=> $a['mtime'];
    });
    return $items;
}

function imageReferences(string $url): array
{
    $refs = [];
    foreach (ALLOWED_FILES as $name) {
        $path = dataDir() . '/' . $name;
        if (!is_file($path)) {
            continue;
        }
        $raw = @file_get_contents($path);
        if ($raw !== false && strpos($raw, $url) !== false) {
            $refs[] = $name;
        }
    }
    return $refs;
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

    /* ===== 留言墙公开接口 ===== */
    case 'wall_list':
        $input = !empty($GLOBALS['jsonBody']) ? $GLOBALS['jsonBody'] : $_POST;
        $page = max(1, (int) ($input['page'] ?? 1));
        $limit = min(20, max(1, (int) ($input['limit'] ?? 10)));
        $data = readWall();
        $approved = [];
        foreach ($data['messages'] as $message) {
            if (is_array($message) && ($message['status'] ?? '') === 'approved') {
                $approved[] = $message;
            }
        }
        $approved = sortWallMessages($approved);
        $total = count($approved);
        $pages = max(1, (int) ceil($total / $limit));
        if ($page > $pages) {
            $page = $pages;
        }
        $slice = array_slice($approved, ($page - 1) * $limit, $limit);
        $messages = [];
        foreach ($slice as $message) {
            $messages[] = [
                'id' => (int) ($message['id'] ?? 0),
                'name' => (string) ($message['name'] ?? ''),
                'content' => (string) ($message['content'] ?? ''),
                'createdAt' => (int) ($message['createdAt'] ?? 0),
            ];
        }
        respond(['ok' => true, 'messages' => $messages, 'total' => $total, 'page' => $page, 'pages' => $pages]);

    case 'wall_post':
        requireSameOrigin();
        $input = !empty($GLOBALS['jsonBody']) ? $GLOBALS['jsonBody'] : $_POST;
        // 蜜罐字段：正常用户不会填写
        if (trim((string) ($input['website'] ?? '')) !== '') {
            respond(['ok' => true, 'status' => 'pending']);
        }
        $name = cleanUserText((string) ($input['name'] ?? ''));
        $name = preg_replace('/\s+/u', ' ', str_replace("\n", ' ', $name));
        $name = trim($name === null ? '' : $name);
        $content = cleanUserText((string) ($input['content'] ?? ''));
        $nameLength = charCount($name);
        $contentLength = charCount($content);
        if ($nameLength < 2 || $nameLength > WALL_MAX_NAME) {
            fail('昵称长度需在 2–20 个字符之间', 400);
        }
        if ($contentLength < 1 || $contentLength > WALL_MAX_CONTENT) {
            fail('留言内容需在 1–500 个字符之间', 400);
        }
        $ipHash = clientIpHash();
        $now = time();
        $result = withWall(function (array &$data) use ($name, $content, $ipHash, $now) {
            foreach ($data['rate'] as $key => $timestamp) {
                if ($now - (int) $timestamp > 86400) {
                    unset($data['rate'][$key]);
                }
            }
            $last = (int) ($data['rate'][$ipHash] ?? 0);
            if ($last > 0 && $now - $last < WALL_RATE_SECONDS) {
                return ['ok' => false, 'retry' => WALL_RATE_SECONDS - ($now - $last)];
            }
            $pending = 0;
            $maxId = 0;
            foreach ($data['messages'] as $message) {
                if (!is_array($message)) {
                    continue;
                }
                if (($message['status'] ?? '') === 'pending') {
                    $pending++;
                }
                $maxId = max($maxId, (int) ($message['id'] ?? 0));
            }
            if ($pending >= WALL_MAX_PENDING) {
                return ['ok' => false, 'busy' => true];
            }
            $data['messages'][] = [
                'id' => $maxId + 1,
                'name' => $name,
                'content' => $content,
                'status' => 'pending',
                'createdAt' => $now,
            ];
            $data['rate'][$ipHash] = $now;
            return ['ok' => true];
        });
        if (empty($result['ok'])) {
            if (!empty($result['retry'])) {
                fail('留言太频繁，请 ' . (int) $result['retry'] . ' 秒后再试', 429);
            }
            fail('待审核留言较多，请稍后再试', 503);
        }
        respond(['ok' => true, 'status' => 'pending']);

    /* ===== 资讯浏览量 ===== */
    case 'news_views':
        respond(['ok' => true, 'counts' => (object) readNewsViews()]);

    case 'news_view':
        requireSameOrigin();
        $input = !empty($GLOBALS['jsonBody']) ? $GLOBALS['jsonBody'] : $_POST;
        $id = trim((string) ($input['id'] ?? ''));
        if ($id === '' || !ctype_digit($id)) {
            fail('参数错误', 400);
        }
        $now = time();
        if (!isset($_SESSION['yitmc_news_views']) || !is_array($_SESSION['yitmc_news_views'])) {
            $_SESSION['yitmc_news_views'] = [];
        }
        foreach ($_SESSION['yitmc_news_views'] as $key => $timestamp) {
            if ($now - (int) $timestamp > 86400) {
                unset($_SESSION['yitmc_news_views'][$key]);
            }
        }
        if (isset($_SESSION['yitmc_news_views'][$id]) && $now - (int) $_SESSION['yitmc_news_views'][$id] < 21600) {
            $counts = readNewsViews();
            respond(['ok' => true, 'count' => (int) ($counts[$id] ?? 0), 'counted' => false]);
        }
        $count = withNewsViews(function (array &$data) use ($id) {
            $data[$id] = (int) ($data[$id] ?? 0) + 1;
            return $data[$id];
        });
        $_SESSION['yitmc_news_views'][$id] = $now;
        respond(['ok' => true, 'count' => (int) $count, 'counted' => true]);

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

    /* ===== 留言墙管理 ===== */
    case 'wall_admin_list':
        requireAuth();
        $input = !empty($GLOBALS['jsonBody']) ? $GLOBALS['jsonBody'] : $_POST;
        $status = (string) ($input['status'] ?? 'all');
        if (!in_array($status, ['all', 'pending', 'approved', 'hidden'], true)) {
            $status = 'all';
        }
        $page = max(1, (int) ($input['page'] ?? 1));
        $limit = min(50, max(1, (int) ($input['limit'] ?? 20)));
        $data = readWall();
        $filtered = [];
        foreach ($data['messages'] as $message) {
            if (!is_array($message)) {
                continue;
            }
            if ($status === 'all' || ($message['status'] ?? '') === $status) {
                $filtered[] = $message;
            }
        }
        $filtered = sortWallMessages($filtered);
        $total = count($filtered);
        $pages = max(1, (int) ceil($total / $limit));
        if ($page > $pages) {
            $page = $pages;
        }
        $slice = array_slice($filtered, ($page - 1) * $limit, $limit);
        $messages = [];
        foreach ($slice as $message) {
            $messages[] = [
                'id' => (int) ($message['id'] ?? 0),
                'name' => (string) ($message['name'] ?? ''),
                'content' => (string) ($message['content'] ?? ''),
                'status' => (string) ($message['status'] ?? 'pending'),
                'createdAt' => (int) ($message['createdAt'] ?? 0),
            ];
        }
        respond(['ok' => true, 'messages' => $messages, 'total' => $total, 'page' => $page, 'pages' => $pages]);

    case 'wall_counts':
        requireAuth();
        $data = readWall();
        $counts = ['pending' => 0, 'approved' => 0, 'hidden' => 0];
        foreach ($data['messages'] as $message) {
            if (!is_array($message)) {
                continue;
            }
            $status = (string) ($message['status'] ?? 'pending');
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }
        respond(['ok' => true, 'counts' => $counts]);

    case 'wall_moderate':
        requireAuth();
        requireSameOrigin();
        $input = !empty($GLOBALS['jsonBody']) ? $GLOBALS['jsonBody'] : $_POST;
        $id = (int) ($input['id'] ?? 0);
        $status = (string) ($input['status'] ?? '');
        if ($id <= 0 || !in_array($status, ['approved', 'hidden', 'delete'], true)) {
            fail('参数错误', 400);
        }
        $found = withWall(function (array &$data) use ($id, $status) {
            foreach ($data['messages'] as $index => $message) {
                if (!is_array($message) || (int) ($message['id'] ?? 0) !== $id) {
                    continue;
                }
                if ($status === 'delete') {
                    array_splice($data['messages'], $index, 1);
                } else {
                    $data['messages'][$index]['status'] = $status;
                }
                return true;
            }
            return false;
        });
        if (!$found) {
            fail('留言不存在', 404);
        }
        respond(['ok' => true]);

    /* ===== 图片库 ===== */
    case 'images_list':
        requireAuth();
        respond(['ok' => true, 'images' => array_slice(listUploads(), 0, 500)]);

    case 'images_delete':
        requireAuth();
        requireSameOrigin();
        $input = !empty($GLOBALS['jsonBody']) ? $GLOBALS['jsonBody'] : $_POST;
        $name = basename((string) ($input['name'] ?? ''));
        if ($name === '' || $name[0] === '.' || !preg_match('/^[A-Za-z0-9._-]+$/', $name)) {
            fail('文件名无效', 400);
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXT, true)) {
            fail('只能删除图片文件', 400);
        }
        $base = realpath(uploadDir());
        $path = realpath(uploadDir() . '/' . $name);
        if ($base === false || $path === false || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0) {
            fail('图片不存在', 404);
        }
        $refs = imageReferences('/uploads/' . $name);
        if ($refs) {
            fail('该图片仍被以下数据引用：' . implode('、', $refs) . '，请先替换后再删除', 409);
        }
        if (!@unlink($path)) {
            fail('删除失败，请检查目录权限', 500);
        }
        respond(['ok' => true]);

    case 'save':
        requireAuth();
        requireSameOrigin();
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
        requireSameOrigin();
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
        requireSameOrigin();
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
