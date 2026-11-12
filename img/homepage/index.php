<?php
/**
 * YITMC · PCL2 自定义主页（动态生成 PCL2 XAML）
 *
 * PCL2 → 设置 → 个性化 → 自定义主页 → 填入本页 URL 即可。
 * PCL2 的版本缓存机制：启动器先请求 {主页URL}version（本文件内按路径识别），
 * 版本号变化才重新下载完整主页，因此支持：
 *   - /homepage/version   返回状态数据的生成时间戳（纯文本）
 *   - /homepage/          返回完整 XAML
 *
 * 数据源：minetools.eu（状态+延迟）为主，mcstatus.io 兜底；
 * 5 台服务器并行查询（curl_multi），状态缓存 60 秒；
 * /version 命中时先立即返回旧版本号，再在后台静默刷新缓存。
 *
 * 安全：本文件不含任何用户输入；所有请求目标均为下方硬编码的
 * 公网服务器，发起请求前逐个校验 host（拒绝内网/环回/保留地址）。
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Shanghai');

/* ==================== 配置区 ==================== */

const HTTP_TIMEOUT = 5;   // 单个上游 API 超时（秒）
const CACHE_TTL = 60;     // 状态缓存（秒）
const MAX_MOTD_LINES = 3; // MOTD 最多显示行数
const MINETOOLS = 'https://api.minetools.eu/ping/';
const MCSTATUS = 'https://api.mcstatus.io/v2/status/java/';
const CACHE_FILE = 'yitmc-pcl-status.json';

// port=0 表示自动 SRV 解析，failPort 为 SRV 查询失败时的兜底端口；id 用于端口缓存
// 实际列表优先读取后台维护的 /data/servers.json，缺失时回落到这里的默认值
$DEFAULT_SERVERS = [
    ['id' => 'srvl',     'name' => '燕通联合服务器',   'note' => '社团主服',        'host' => 'srvl.yitmc.cn',     'display' => 'srvl.yitmc.cn',     'port' => 0,     'failPort' => 20043],
    ['id' => 'building', 'name' => '复原工程建筑服',   'note' => '校园复刻工程',    'host' => 'building.yitmc.cn', 'display' => 'building.yitmc.cn', 'port' => 0,     'failPort' => 19000],
    ['id' => 'parkour',  'name' => '跑酷服务器',       'note' => '跑酷',            'host' => 'parkour.uemcraft.cn','display' => 'parkour.uemcraft.cn','port' => 0,   'failPort' => 25565],
    ['id' => 'jjgl',     'name' => '津高联联合服务器', 'note' => '原版生存',        'host' => 'unioncompute.top',  'display' => 'unioncompute.***',  'port' => 26149, 'failPort' => 26149],
    ['id' => 'mua',      'name' => 'MUA Lobby',        'note' => 'MUA 高校联盟大厅', 'host' => 'lobby.mualliance.cn','display' => 'lobby.mualliance.cn','port' => 0,    'failPort' => 25565],
];

/** 后台维护的数据文件路径（homepage/ 的上一级是网站根目录） */
function pclDataFile(string $name): string
{
    return dirname(__DIR__) . '/data/' . $name;
}

function loadServers(): array
{
    global $DEFAULT_SERVERS;
    $file = pclDataFile('servers.json');
    if (!is_file($file)) {
        return $DEFAULT_SERVERS;
    }
    $json = json_decode((string) @file_get_contents($file), true);
    $items = is_array($json) ? ($json['servers'] ?? $json) : [];
    if (!is_array($items)) {
        return $DEFAULT_SERVERS;
    }
    $out = [];
    foreach ($items as $item) {
        if (!is_array($item) || empty($item['address'])) {
            continue;
        }
        $address = (string) $item['address'];
        $display = trim((string) ($item['displayAddress'] ?? ''));
        if ($display === '') {
            $display = $address;
        }
        $fallbackPort = (int) ($item['fallbackPort'] ?? 0);
        if ($fallbackPort <= 0) {
            $fallbackPort = (int) ($item['port'] ?? 0);
        }
        if ($fallbackPort <= 0) {
            $fallbackPort = 25565;
        }
        $out[] = [
            'id' => (string) ($item['id'] ?? ('srv' . count($out))),
            'name' => (string) ($item['name'] ?? $address),
            'note' => (string) ($item['note'] ?? ''),
            'host' => $address,
            'display' => $display,
            'port' => (int) ($item['port'] ?? 0),
            'failPort' => $fallbackPort,
        ];
    }
    return $out ?: $DEFAULT_SERVERS;
}

function loadPclConfig(): array
{
    $defaults = [
        'title' => '燕京理工学院 MC 玩家创作协会',
        'intro' => '欢迎加入 YITMC！点击下方服务器卡片中的地址即可复制，状态数据每次启动自动更新。',
        'newsCount' => 2,
        'links' => [
            ['label' => '访问官网', 'url' => 'https://www.yitmc.cn/'],
            ['label' => '加入QQ群', 'url' => 'https://qm.qq.com/q/942717135'],
            ['label' => '社团皮肤站', 'url' => 'https://skin.yitmc.cn/'],
        ],
        'cards' => [],
        'vars' => [],
    ];
    $file = pclDataFile('pcl.json');
    if (!is_file($file)) {
        return $defaults;
    }
    $json = json_decode((string) @file_get_contents($file), true);
    if (!is_array($json)) {
        return $defaults;
    }
    $out = $defaults;
    if (isset($json['title']) && is_string($json['title']) && trim($json['title']) !== '') {
        $out['title'] = trunc($json['title'], 80);
    }
    if (isset($json['intro']) && is_string($json['intro']) && trim($json['intro']) !== '') {
        $out['intro'] = trunc($json['intro'], 200);
    }
    if (isset($json['newsCount'])) {
        $out['newsCount'] = max(0, min(5, (int) $json['newsCount']));
    }
    if (isset($json['links']) && is_array($json['links'])) {
        $links = [];
        foreach ($json['links'] as $link) {
            if (!is_array($link)) {
                continue;
            }
            $label = trim((string) ($link['label'] ?? ''));
            $url = trim((string) ($link['url'] ?? ''));
            if ($label === '' || !preg_match('#^https?://#i', $url)) {
                continue;
            }
            $links[] = ['label' => trunc($label, 20), 'url' => trunc($url, 500)];
            if (count($links) >= 4) {
                break;
            }
        }
        $out['links'] = $links;
    }

    if (isset($json['cards']) && is_array($json['cards'])) {
        $cards = [];
        foreach ($json['cards'] as $card) {
            if (!is_array($card)) {
                continue;
            }
            $rows = [];
            $rawRows = isset($card['rows']) && is_array($card['rows']) ? $card['rows'] : [];
            foreach ($rawRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $type = (string) ($row['type'] ?? 'text');
                if ($type === 'text') {
                    $text = trim((string) ($row['text'] ?? ''));
                    if ($text === '') {
                        continue;
                    }
                    $rows[] = [
                        'type' => 'text',
                        'text' => trunc($text, 500),
                        'fontSize' => max(10, min(24, (int) ($row['fontSize'] ?? 13))),
                    ];
                } elseif ($type === 'list_item') {
                    $title = trim((string) ($row['title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }
                    $url = trim((string) ($row['url'] ?? ''));
                    $rows[] = [
                        'type' => 'list_item',
                        'logo' => trunc(trim((string) ($row['logo'] ?? '')), 200),
                        'title' => trunc($title, 60),
                        'info' => trunc(trim((string) ($row['info'] ?? '')), 100),
                        'url' => preg_match('#^https?://#i', $url) ? trunc($url, 500) : '',
                    ];
                } elseif ($type === 'hint') {
                    $text = trim((string) ($row['text'] ?? ''));
                    if ($text === '') {
                        continue;
                    }
                    $theme = (string) ($row['theme'] ?? 'Blue');
                    if (!in_array($theme, ['Blue', 'Red', 'Yellow', 'Green'], true)) {
                        $theme = 'Blue';
                    }
                    $rows[] = ['type' => 'hint', 'theme' => $theme, 'text' => trunc($text, 200)];
                } elseif ($type === 'divider') {
                    $rows[] = ['type' => 'divider', 'text' => trunc(trim((string) ($row['text'] ?? '')), 40)];
                }
                if (count($rows) >= 20) {
                    break;
                }
            }

            $buttons = [];
            $rawButtons = isset($card['buttons']) && is_array($card['buttons']) ? $card['buttons'] : [];
            foreach ($rawButtons as $button) {
                if (!is_array($button)) {
                    continue;
                }
                $label = trim((string) ($button['label'] ?? ''));
                $url = trim((string) ($button['url'] ?? ''));
                if ($label === '' || !preg_match('#^https?://#i', $url)) {
                    continue;
                }
                $buttons[] = ['label' => trunc($label, 20), 'url' => trunc($url, 500)];
                if (count($buttons) >= 4) {
                    break;
                }
            }

            $title = trunc(trim((string) ($card['title'] ?? '')), 60);
            if ($title === '' && !$rows && !$buttons) {
                continue;
            }
            $cards[] = [
                'id' => trunc(trim((string) ($card['id'] ?? '')), 40),
                'title' => $title,
                'enabled' => !array_key_exists('enabled', $card) || !empty($card['enabled']),
                'rows' => $rows,
                'buttons' => $buttons,
            ];
            if (count($cards) >= 10) {
                break;
            }
        }
        $out['cards'] = $cards;
    }

    if (isset($json['vars']) && is_array($json['vars'])) {
        $vars = [];
        foreach ($json['vars'] as $var) {
            if (!is_array($var)) {
                continue;
            }
            $key = trim((string) ($var['key'] ?? ''));
            if (!preg_match('/^[a-zA-Z0-9_]{1,32}$/', $key)) {
                continue;
            }
            $vars[$key] = trunc(trim((string) ($var['value'] ?? '')), 200);
            if (count($vars) >= 30) {
                break;
            }
        }
        $out['vars'] = $vars;
    }
    return $out;
}

$SERVERS = loadServers();
$PCL = loadPclConfig();

/* ================================================ */

function cachePath(): string
{
    return sys_get_temp_dir() . '/' . CACHE_FILE;
}

/** 状态数据的生成时间戳（PCL2 /version 端点用） */
function cacheGen(): int
{
    $file = cachePath();
    if (!is_file($file)) {
        return 0;
    }
    $j = json_decode((string) @file_get_contents($file), true);
    return is_array($j) && isset($j['gen']) ? (int) $j['gen'] : 0;
}

function cacheFresh(): bool
{
    $file = cachePath();
    return is_file($file) && time() - (int) filemtime($file) < CACHE_TTL;
}

/**
 * 主机名校验：合法公网域名格式，且显式拒绝环回/私有/保留地址形态。
 * 注意：本端点所有 HTTP 请求的实际目标都是下方硬编码的公网 API 主机
 * （apiHostCheck 做含 DNS 的完整校验）；此函数校验的是拼进 URL 路径的
 * MC 服务器主机名（同样来自硬编码配置），不发起 DNS 解析以保证速度。
 */
function isPublicHost(string $host): bool
{
    if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/i', $host)) {
        return false;
    }
    if (preg_match('/^(localhost|.*\.local|.*\.localhost|.*\.lan|.*\.internal|.*\.home\.arpa)$/i', $host)) {
        return false;
    }
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        // IP 字面量：只允许公网
        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
    return true;
}

/** 实际发起 HTTP 请求的 API 主机（硬编码常量）：完整校验，含解析 IP 范围检查 */
function apiHostCheck(string $host): bool
{
    if (!isPublicHost($host)) {
        return false;
    }
    $ip = @gethostbyname($host);
    if ($ip === $host) {
        return false;
    }
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

/** SRV 解析，失败返回 0 */
function resolveSrvPort(string $host): int
{
    if (!isPublicHost($host)) {
        return 0;
    }
    $rec = @dns_get_record($host, DNS_SRV);
    if (is_array($rec) && isset($rec[0]['port'], $rec[0]['target'])) {
        $target = (string) $rec[0]['target'];
        if (isPublicHost(rtrim($target, '.'))) {
            return (int) $rec[0]['port'];
        }
    }
    return 0;
}

/* ---------- HTTP ---------- */

/** GET 一个 https URL（原生流实现，通用兜底） */
function httpGetStream(string $url): ?string
{
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => HTTP_TIMEOUT,
            'follow_location' => 0,
            'max_redirects' => 0,
            'header' => "User-Agent: YITMC-PCL-Homepage/1.0\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    return is_string($body) && $body !== '' ? $body : null;
}

/** 并行 GET 多个 https URL；cURL 不可用时退化为串行 */
function httpGetAll(array $urls): array
{
    if (!function_exists('curl_multi_init')) {
        $out = [];
        foreach ($urls as $key => $url) {
            $out[$key] = httpGetStream($url);
        }
        return $out;
    }

    $mh = curl_multi_init();
    $handles = [];
    foreach ($urls as $key => $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => HTTP_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }

    do {
        $mrc = curl_multi_exec($mh, $active);
        if ($active) {
            curl_multi_select($mh, 1.0);
        }
    } while ($active && $mrc === CURLM_OK);

    $bodies = [];
    foreach ($handles as $key => $ch) {
        $body = curl_multi_getcontent($ch);
        $bodies[$key] = is_string($body) && $body !== '' ? $body : null;
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $bodies;
}

/* ---------- 状态查询 ---------- */

/** 解析 minetools 响应（在线时含 version/latency；离线或失败返回 {"error": ...}） */
function parseMinetools(?string $body): ?array
{
    if ($body === null) {
        return null;
    }
    $j = json_decode($body, true);
    if (!is_array($j) || isset($j['error']) || !isset($j['version']['name'])) {
        return null;
    }
    $latency = isset($j['latency']) && is_numeric((string) $j['latency']) ? (int) round((float) $j['latency']) : null;
    $desc = $j['description'] ?? '';
    if (is_array($desc)) {
        $desc = $desc['text'] ?? json_encode($desc, JSON_UNESCAPED_UNICODE);
    }
    return [
        'online' => true,
        'version' => (string) $j['version']['name'],
        'players' => (int) ($j['players']['online'] ?? 0),
        'max' => (int) ($j['players']['max'] ?? 0),
        'motd' => (string) $desc,
        'latency' => ($latency !== null && $latency > 0) ? $latency : null,
    ];
}

/** 解析 mcstatus.io 响应（兜底；无延迟数据） */
function parseMcstatus(?string $body): ?array
{
    if ($body === null) {
        return null;
    }
    $j = json_decode($body, true);
    if (!is_array($j) || !isset($j['online'])) {
        return null;
    }
    $motd = $j['motd']['raw'] ?? '';
    return [
        'online' => (bool) $j['online'],
        'version' => (string) ($j['version']['name_clean'] ?? ''),
        'players' => (int) ($j['players']['online'] ?? 0),
        'max' => (int) ($j['players']['max'] ?? 0),
        'motd' => is_string($motd) ? $motd : '',
        'latency' => null,
    ];
}

function offlineStatus(): array
{
    return ['online' => false, 'version' => '', 'players' => 0, 'max' => 0, 'motd' => '', 'latency' => null];
}

/** 并行查询全部服务器：第一轮 minetools，失败者第二轮 mcstatus 兜底。
 *  返回 [statuses, ports]；ports 缓存后下次刷新无需再做 SRV/DNS 查询 */
function queryAll(array $servers, array $cachedPorts = []): array
{
    // 请求目标 API 主机做含 DNS 的完整校验（常量，失败即全部离线）
    if (!apiHostCheck('api.minetools.eu') || !apiHostCheck('api.mcstatus.io')) {
        return [array_map(fn() => offlineStatus(), $servers), $cachedPorts];
    }

    $targets = [];
    foreach ($servers as $i => $cfg) {
        $port = $cfg['port'];
        if ($port === 0) {
            // 端口缓存（SRV 极少变化）：命中则零 DNS 开销
            $port = $cachedPorts[$cfg['id']] ?? 0;
            if ($port === 0) {
                $port = resolveSrvPort($cfg['host']);
                if ($port === 0) {
                    $port = $cfg['failPort'];
                }
            }
        }
        $targets[$i] = ['id' => $cfg['id'], 'host' => $cfg['host'], 'port' => $port];
    }

    // 第一轮：minetools（一条请求同时拿到状态和延迟）
    $urls = [];
    foreach ($targets as $i => $t) {
        $urls[$i] = isPublicHost($t['host'])
            ? MINETOOLS . rawurlencode($t['host']) . '/' . $t['port']
            : '';
    }
    $bodies = httpGetAll(array_filter($urls, fn($u) => $u !== ''));
    $statuses = [];
    $needFallback = [];
    foreach ($targets as $i => $t) {
        $st = ($urls[$i] !== '') ? parseMinetools($bodies[$i] ?? null) : null;
        if ($st === null) {
            $needFallback[$i] = $t;
            $statuses[$i] = null;
        } else {
            $statuses[$i] = $st;
        }
    }

    // 第二轮：mcstatus.io 兜底确认（含真实离线判定）
    if ($needFallback) {
        $fbUrls = [];
        foreach ($needFallback as $i => $t) {
            $addr = $t['port'] === 25565 ? $t['host'] : $t['host'] . ':' . $t['port'];
            $fbUrls[$i] = isPublicHost($t['host'])
                ? MCSTATUS . rawurlencode($addr)
                : '';
        }
        $fbBodies = httpGetAll(array_filter($fbUrls, fn($u) => $u !== ''));
        foreach ($needFallback as $i => $t) {
            $st = ($fbUrls[$i] !== '') ? parseMcstatus($fbBodies[$i] ?? null) : null;
            $statuses[$i] = $st ?? offlineStatus();
        }
    }

    $ports = [];
    foreach ($targets as $i => $t) {
        $statuses[$i]['port'] = $t['port'];
        $ports[$t['id']] = $t['port'];
    }
    return [$statuses, $ports];
}

function cachePorts(): array
{
    $j = json_decode((string) @file_get_contents(cachePath()), true);
    return is_array($j) && isset($j['ports']) && is_array($j['ports']) ? $j['ports'] : [];
}

function refreshCache(array $servers): void
{
    [$statuses, $ports] = queryAll($servers, cachePorts());
    $payload = ['gen' => time(), 'ports' => $ports, 'statuses' => $statuses];
    @file_put_contents(cachePath(), json_encode($payload, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/** 拿到当前状态（带 60 秒缓存） */
function queryCached(array $servers): array
{
    if (cacheFresh()) {
        $j = json_decode((string) @file_get_contents(cachePath()), true);
        if (is_array($j) && isset($j['statuses']) && count($j['statuses']) === count($servers)) {
            return $j['statuses'];
        }
    }
    refreshCache($servers);
    $j = json_decode((string) @file_get_contents(cachePath()), true);
    return is_array($j) && isset($j['statuses']) ? $j['statuses'] : array_map(fn() => offlineStatus(), $servers);
}

/* ---------- /version 端点（PCL2 缓存校验） ---------- */

$path = rtrim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'), '/');
if (substr($path, -8) === '/version') {
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    if (cacheFresh()) {
        echo (string) max(cacheGen(), 1);
        exit;
    }
    if (function_exists('fastcgi_finish_request')) {
        // 先把当前版本号发给启动器（命中其本地缓存，零等待），
        // 请求结束后在后台刷新状态缓存，下次启动器启动即可看到新版本号
        echo (string) max(cacheGen(), 1);
        fastcgi_finish_request();
        refreshCache($SERVERS);
        exit;
    }
    // 无 FPM（CLI 等）：同步刷新后输出
    refreshCache($SERVERS);
    echo (string) max(cacheGen(), 1);
    exit;
}

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

/* ---------- XAML 生成 ---------- */

function xesc(?string $s): string
{
    $s = htmlspecialchars($s ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
    return str_replace(["\r\n", "\n", "\r"], '&#10;', $s);
}

/** 去掉 § 颜色代码，只留纯文本；截断到指定行数 */
function plainMotd(string $motd): string
{
    $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $motd));
    $clean = [];
    foreach ($lines as $line) {
        $line = trim(preg_replace('/§./u', '', $line));
        if ($line !== '') {
            $clean[] = $line;
        }
        if (count($clean) >= MAX_MOTD_LINES) {
            break;
        }
    }
    return implode("\n", $clean);
}

/** UTF-8 字符数（服务器可能未装 mbstring，用 PCRE 实现） */
function uLen(string $s): int
{
    $n = preg_match_all('/./us', $s);
    return $n === false ? strlen($s) : $n;
}

function uSub(string $s, int $start, int $len): string
{
    $chars = preg_split('//us', $s, -1, PREG_SPLIT_NO_EMPTY);
    return is_array($chars) ? implode('', array_slice($chars, $start, $len)) : $s;
}

function trunc(string $s, int $max): string
{
    $s = trim($s);
    return uLen($s) > $max ? uSub($s, 0, $max - 1) . '…' : $s;
}

/** 替换 PCL 卡片里的 {%key%} 变量 */
function pclSubst(string $text, array $vars): string
{
    return preg_replace_callback('/\{%([a-zA-Z0-9_]+)%\}/', static function ($m) use ($vars) {
        return array_key_exists($m[1], $vars) ? (string) $vars[$m[1]] : '';
    }, $text) ?? $text;
}

/** 方块图标名或完整图片 URL */
function pclLogo(string $logo): string
{
    $logo = trim($logo);
    if ($logo === '') {
        return 'pack://application:,,,/images/Blocks/GoldBlock.png';
    }
    if (preg_match('#^https?://#i', $logo)) {
        return $logo;
    }
    $name = preg_replace('/[^A-Za-z0-9_]/', '', $logo) ?? '';
    if ($name === '') {
        $name = 'GoldBlock';
    }
    return 'pack://application:,,,/images/Blocks/' . $name . '.png';
}

function renderPclCard(array $card, array $vars): string
{
    $title = pclSubst((string) ($card['title'] ?? ''), $vars);
    if (trim($title) === '') {
        $title = 'YITMC';
    }
    $xaml = '    <local:MyCard Title="' . xesc($title) . '" Margin="0,0,0,15" CanSwap="True">' . "\n";
    $xaml .= '        <StackPanel Margin="25,40,23,15">' . "\n";

    foreach ($card['rows'] as $row) {
        $type = (string) ($row['type'] ?? '');
        if ($type === 'text') {
            $text = trim(pclSubst((string) ($row['text'] ?? ''), $vars));
            if ($text === '') {
                continue;
            }
            $size = max(10, min(24, (int) ($row['fontSize'] ?? 13)));
            $xaml .= '            <TextBlock TextWrapping="Wrap" FontSize="' . $size . '" Foreground="{DynamicResource ColorBrush4}" Text="' . xesc($text) . '" />' . "\n";
        } elseif ($type === 'list_item') {
            $itemTitle = trim(pclSubst((string) ($row['title'] ?? ''), $vars));
            if ($itemTitle === '') {
                continue;
            }
            $info = pclSubst((string) ($row['info'] ?? ''), $vars);
            $url = trim(pclSubst((string) ($row['url'] ?? ''), $vars));
            $extra = '';
            if (preg_match('#^https?://#i', $url)) {
                $extra = ' Type="Clickable" EventType="打开网页" EventData="' . xesc($url) . '"';
            }
            $xaml .= '            <local:MyListItem Margin="-5,2,-5,2" Logo="' . xesc(pclLogo((string) ($row['logo'] ?? ''))) . '" Title="' . xesc($itemTitle) . '" Info="' . xesc($info) . '"' . $extra . ' />' . "\n";
        } elseif ($type === 'hint') {
            $text = trim(pclSubst((string) ($row['text'] ?? ''), $vars));
            if ($text === '') {
                continue;
            }
            $theme = (string) ($row['theme'] ?? 'Blue');
            if (!in_array($theme, ['Blue', 'Red', 'Yellow', 'Green'], true)) {
                $theme = 'Blue';
            }
            $xaml .= '            <local:MyHint Margin="0,6,0,0" Theme="' . $theme . '" Text="' . xesc($text) . '" />' . "\n";
        } elseif ($type === 'divider') {
            $text = trim(pclSubst((string) ($row['text'] ?? ''), $vars));
            if ($text === '') {
                $text = '────────────';
            }
            $xaml .= '            <TextBlock Margin="0,6,0,0" FontSize="10" HorizontalAlignment="Center" Foreground="{DynamicResource ColorBrush4}" Text="' . xesc($text) . '" />' . "\n";
        }
    }

    if (!empty($card['buttons'])) {
        $xaml .= '            <StackPanel Orientation="Horizontal" Margin="0,10,0,0">' . "\n";
        foreach ($card['buttons'] as $button) {
            $label = trim(pclSubst((string) ($button['label'] ?? ''), $vars));
            $url = trim(pclSubst((string) ($button['url'] ?? ''), $vars));
            if ($label === '' || !preg_match('#^https?://#i', $url)) {
                continue;
            }
            $xaml .= '                <local:MyButton Text="' . xesc($label) . '" Padding="13,7,13,8" Margin="0,0,10,0" EventType="打开网页" EventData="' . xesc($url) . '" />' . "\n";
        }
        $xaml .= '            </StackPanel>' . "\n";
    }

    $xaml .= '        </StackPanel>' . "\n";
    $xaml .= '    </local:MyCard>' . "\n";
    return $xaml;
}

function serverCard(array $cfg, array $st): string
{
    $online = $st['online'];
    $color = $online ? '#4CAF50' : '#E53935';
    $statusText = $online ? '在线' : '离线';
    $addrText = $cfg['display'] . ($cfg['port'] > 0 ? ':' . $cfg['port'] : '');
    // 津高联：展示打码地址，复制的也是打码形式（与其余成员服一致）
    $copyText = $cfg['display'] === 'unioncompute.***'
        ? 'unioncompute.*** -p ' . $cfg['port']
        : $addrText;

    $xaml = '    <local:MyCard Title="' . xesc($cfg['name']) . '" Margin="0,0,0,15" CanSwap="True">' . "\n";
    $xaml .= '        <StackPanel Margin="25,40,23,15">' . "\n";
    $xaml .= '            <StackPanel Orientation="Horizontal" Margin="0,0,0,8">' . "\n";
    $xaml .= '                <TextBlock Text="● " Foreground="' . $color . '" FontSize="14" VerticalAlignment="Center" />' . "\n";
    $xaml .= '                <TextBlock Text="' . $statusText . '" Foreground="' . $color . '" FontSize="14" FontWeight="Bold" VerticalAlignment="Center" />' . "\n";
    $xaml .= '            </StackPanel>' . "\n";
    $xaml .= '            <local:MyListItem Margin="-5,2,-5,2" Logo="pack://application:,,,/images/Blocks/CommandBlock.png" Title="地址" Info="' . xesc($addrText) . '" Type="Clickable" EventType="复制文本" EventData="' . xesc($copyText) . '" />' . "\n";
    $xaml .= '            <local:MyListItem Margin="-5,2,-5,2" Logo="pack://application:,,,/images/Blocks/GoldBlock.png" Title="版本" Info="' . xesc($online ? trunc($st['version'], 60) : '--') . '" />' . "\n";
    $xaml .= '            <local:MyListItem Margin="-5,2,-5,2" Logo="pack://application:,,,/images/Blocks/Grass.png" Title="玩家" Info="' . ($online ? xesc($st['players'] . ' / ' . $st['max']) : '--') . '" />' . "\n";
    $xaml .= '            <local:MyListItem Margin="-5,2,-5,2" Logo="pack://application:,,,/images/Blocks/RedstoneBlock.png" Title="延迟" Info="' . ($online ? xesc($st['latency'] !== null ? $st['latency'] . 'ms' : '--') : '--') . '" />' . "\n";

    $motd = $online ? plainMotd($st['motd']) : '';
    if ($motd !== '') {
        $xaml .= '            <TextBlock TextWrapping="Wrap" Margin="0,6,0,0" FontSize="12" Foreground="{DynamicResource ColorBrush4}" Text="' . xesc($motd) . '" />' . "\n";
    }

    if (!$online) {
        $xaml .= '            <local:MyHint Margin="0,8,0,0" Theme="Yellow" Text="服务器暂时离线，请稍后再试" />' . "\n";
    } elseif ($cfg['note'] !== '') {
        $xaml .= '            <local:MyHint Margin="0,8,0,0" Theme="Blue" Text="' . xesc($cfg['note']) . '" />' . "\n";
    }

    $xaml .= '        </StackPanel>' . "\n";
    $xaml .= '    </local:MyCard>' . "\n";
    return $xaml;
}

/* ---------- 主流程 ---------- */

$statuses = queryCached($SERVERS);

$xaml = "<StackPanel>\n";

// 公告卡：链接 + 最近动态标题（直接读服务器上后台维护的 news.json）
$newsFile = dirname(__DIR__) . '/data/news.json';
$latestNews = [];
if (is_file($newsFile)) {
    $news = json_decode((string) @file_get_contents($newsFile), true);
    $items = is_array($news) ? ($news['news'] ?? $news) : [];
    if (is_array($items)) {
        usort($items, fn($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
        $latestNews = array_slice($items, 0, (int) $PCL['newsCount']);
    }
}

// 自定义卡片可用变量
$pclVars = [
    'year' => date('Y'),
    'month' => date('m'),
    'day' => date('d'),
    'hour' => date('H'),
    'minute' => date('i'),
    'date' => date('Y-m-d'),
    'time' => date('H:i'),
    'weekday' => ['日', '一', '二', '三', '四', '五', '六'][(int) date('w')],
];
foreach ($latestNews as $index => $item) {
    $n = $index + 1;
    if ($n > 5) {
        break;
    }
    $pclVars['news_' . $n . '_title'] = trunc((string) ($item['title'] ?? ''), 60);
    $pclVars['news_' . $n . '_date'] = (string) ($item['date'] ?? '');
    $pclVars['news_' . $n . '_url'] = 'https://www.yitmc.cn/news/' . rawurlencode((string) ($item['id'] ?? ''));
}
foreach ($PCL['vars'] as $key => $value) {
    $pclVars[$key] = (string) $value;
}

$xaml .= '    <local:MyCard Title="' . xesc(pclSubst($PCL['title'], $pclVars)) . '" Margin="0,0,0,15" CanSwap="True">' . "\n";
$xaml .= '        <StackPanel Margin="25,40,23,15">' . "\n";
$xaml .= '            <TextBlock TextWrapping="Wrap" FontSize="13" Foreground="{DynamicResource ColorBrush4}" Text="' . xesc(pclSubst($PCL['intro'], $pclVars)) . '" />' . "\n";
foreach ($latestNews as $item) {
    $title = trunc((string) ($item['title'] ?? ''), 40);
    $date = (string) ($item['date'] ?? '');
    $xaml .= '            <local:MyListItem Margin="-5,2,-5,2" Logo="pack://application:,,,/images/Blocks/GoldBlock.png" Title="' . xesc($title) . '" Info="' . xesc($date) . '" />' . "\n";
}
if (!empty($PCL['links'])) {
    $xaml .= '            <StackPanel Orientation="Horizontal" Margin="0,10,0,0">' . "\n";
    foreach ($PCL['links'] as $link) {
        $label = trim(pclSubst($link['label'], $pclVars));
        $url = trim(pclSubst($link['url'], $pclVars));
        if ($label === '' || !preg_match('#^https?://#i', $url)) {
            continue;
        }
        $xaml .= '                <local:MyButton Text="' . xesc($label) . '" Padding="13,7,13,8" Margin="0,0,10,0" EventType="打开网页" EventData="' . xesc($url) . '" />' . "\n";
    }
    $xaml .= '            </StackPanel>' . "\n";
}
$xaml .= '            <TextBlock Margin="0,8,0,0" FontSize="11" Foreground="{DynamicResource ColorBrush4}" Text="状态更新于 ' . xesc(date('Y-m-d H:i:s')) . '" />' . "\n";
$xaml .= '        </StackPanel>' . "\n";
$xaml .= '    </local:MyCard>' . "\n";

// 后台配置的自定义卡片（提示、规则、公告等）
foreach ($PCL['cards'] as $card) {
    if (empty($card['enabled'])) {
        continue;
    }
    $xaml .= renderPclCard($card, $pclVars);
}

foreach ($SERVERS as $i => $cfg) {
    $xaml .= serverCard($cfg, $statuses[$i]);
}

$xaml .= "</StackPanel>\n";

echo $xaml;
