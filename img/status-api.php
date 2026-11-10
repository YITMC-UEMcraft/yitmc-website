<?php
/**
 * YITMC 官网服务器状态代理（同源接口）
 *
 * 作用：由官网服务器（而非访客浏览器）直查 MC 服务器状态并缓存，
 * 避免访客浏览器直连海外探测 API（mcstatus.io / mcsrvstat）时的
 * 超时、限流与误报「离线」问题。
 *
 * 部署：放在 publicDir（img/）内随 dist 整包一起上传到网站根目录
 * （需 PHP 7.4+，允许 outbound TCP + dns_get_record；
 * 缓存写入系统临时目录，无需额外权限）。
 * 用法：GET /status-api.php?host=srvl.yitmc.cn[&port=20043]
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

/* ---------------- 配置 ---------------- */
// 仅允许查询这些域名（后缀匹配 / 精确匹配），防止被当作任意主机探测代理
$ALLOW_SUFFIX = ['.yitmc.cn'];
$ALLOW_EXACT = ['unioncompute.top', 'lobby.mualliance.cn'];
$CACHE_TTL = 60;        // 成功结果缓存秒数
$STALE_TTL = 900;       // 探测失败时，可用旧结果的最长期限（秒）
$CONNECT_TIMEOUT = 4.0; // MC 连接超时
// TPS 数据源：MC 服务器上的 tps-export 定时任务把 /tps 命令结果写进
// Dynmap 网页目录（tps.json），这里按主机名挂上读取链路；未配置的主机不返回 tps
$TPS_SOURCES = [
    'srvl.yitmc.cn' => ['url' => 'http://43.143.245.143/tps.json', 'vhost' => 'srvlmap.yitmc.cn'],
];
/* ------------------------------------- */

function emit(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(string $reason, int $code = 400): void {
    emit(['online' => false, 'error' => $reason], $code);
}

/* ---------------- 输入校验 ---------------- */
/** 仅接受公网 IPv4 解析结果（本服白名单域均为 v4；拒绝 v6 与私有/保留段，防 SSRF） */
function isPublicIPv4(string $ip): bool {
    if (strpos($ip, ':') !== false) return false;
    $l = ip2long($ip);
    if ($l === false) return false;
    $blocked = [
        ['0.0.0.0', 8], ['10.0.0.0', 8], ['100.64.0.0', 10], ['127.0.0.0', 8],
        ['169.254.0.0', 16], ['172.16.0.0', 12], ['192.0.0.0', 24], ['192.168.0.0', 16],
        ['198.18.0.0', 15], ['224.0.0.0', 4], ['240.0.0.0', 4],
    ];
    foreach ($blocked as $range) {
        $base = ip2long($range[0]);
        $mask = -1 << (32 - $range[1]);
        if (($l & $mask) === ($base & $mask)) return false;
    }
    return true;
}

$host = isset($_GET['host']) ? trim((string) $_GET['host']) : '';
$portIn = isset($_GET['port']) ? (int) $_GET['port'] : 0;

if ($host === '' || strlen($host) > 253 || !preg_match('/^[a-z0-9.-]+$/i', $host)) {
    fail('invalid host');
}
$host = strtolower($host);
$allowed = in_array($host, $ALLOW_EXACT, true);
if (!$allowed) {
    foreach ($ALLOW_SUFFIX as $suffix) {
        if (substr($host, -strlen($suffix)) === $suffix) { $allowed = true; break; }
    }
}
if (!$allowed) fail('host not allowed', 403);

/* 解析出的 IP 必须是公网地址（拒绝内网/环回/保留段，防 SSRF） */
$ip = gethostbyname($host);
if ($ip === $host) emit(['online' => false, 'error' => 'dns']);
if (!isPublicIPv4($ip)) fail('reserved address', 403);

/* ---------------- 端口：显式指定 > SRV 记录 > 25565 ---------------- */
$srvHost = '';
$port = 0;
if ($portIn > 0) {
    $port = $portIn;
} else {
    $recs = @dns_get_record('_minecraft._tcp.' . $host, DNS_SRV);
    if (is_array($recs) && count($recs) > 0) {
        usort($recs, static function ($a, $b) { return ($a['pri'] ?? 0) <=> ($b['pri'] ?? 0); });
        $srvHost = (string) ($recs[0]['target'] ?? '');
        $port = (int) ($recs[0]['port'] ?? 0);
    }
    if ($port <= 0) $port = 25565;
}
if ($port < 1 || $port > 65535) fail('invalid port');

/* ---------------- TPS 读取（来自 MC 服务器 tps-export 定时任务） ---------------- */
function fetch_tps(string $url, string $vhost): ?array {
    $ctx = stream_context_create(['http' => [
        'method'  => 'GET',
        'header'  => "Host: {$vhost}\r\nUser-Agent: yitmc-status-api\r\n",
        'timeout' => 2.5,
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || strlen($raw) > 4096) return null;
    $j = json_decode($raw, true);
    if (!is_array($j) || !isset($j['tps1m']) || !is_numeric($j['tps1m'])) return null;
    $num = static function ($v): ?float {
        return is_numeric($v) ? (float) $v : null;
    };
    $out = [
        'tps1m'  => (float) $j['tps1m'],
        'tps5m'  => $num($j['tps5m'] ?? null),
        'tps15m' => $num($j['tps15m'] ?? null),
        'mspt'   => $num($j['mspt'] ?? null),
        'age'    => null,
    ];
    if (isset($j['ts']) && is_numeric($j['ts'])) {
        $out['age'] = max(0, time() - (int) $j['ts']);
    }
    return $out;
}

/* ---------------- 缓存 ---------------- */
$cacheKey = hash('sha256', $host . ':' . $port);
$cacheFile = sys_get_temp_dir() . '/yitmc-mcstatus-' . $cacheKey . '.json';
$now = time();
$fresh = null;
$stale = null;
if (is_file($cacheFile)) {
    $cached = json_decode((string) @file_get_contents($cacheFile), true);
    if (is_array($cached) && isset($cached['ts'])) {
        if ($now - (int) $cached['ts'] < $CACHE_TTL) $fresh = $cached;
        elseif ($now - (int) $cached['ts'] < $STALE_TTL) $stale = $cached;
    }
}
if (is_array($fresh)) {
    unset($fresh['ts']);
    emit($fresh);
}

/* ---------------- MC 状态查询（原生协议） ---------------- */
function writeVarint(int $n): string {
    $out = '';
    while (true) {
        if (($n & ~0x7f) === 0) { $out .= chr($n); return $out; }
        $out .= chr(($n & 0x7f) | 0x80);
        $n >>= 7;
    }
}
function readVarint($sock): ?int {
    $n = 0; $shift = 0;
    for ($i = 0; $i < 5; $i++) {
        $b = fgetc($sock);
        if ($b === false) return null;
        $b = ord($b);
        $n |= ($b & 0x7f) << $shift;
        if (($b & 0x80) === 0) return $n;
        $shift += 7;
    }
    return null;
}
function readExact($sock, int $n): ?string {
    if ($n <= 0 || $n > 2 * 1024 * 1024) return null;
    $data = '';
    while (strlen($data) < $n) {
        $chunk = fread($sock, $n - strlen($data));
        if ($chunk === false || $chunk === '') return null;
        $data .= $chunk;
    }
    return $data;
}

function probe(string $targetHost, string $handshakeHost, int $port, float $timeout, string $srvHost): ?array {
    $lat0 = microtime(true);
    $sock = @fsockopen('tcp://' . $targetHost, $port, $errno, $errstr, $timeout);
    if ($sock === false) return null;
    stream_set_timeout($sock, (int) ceil($timeout));

    // handshake: id=0x00, protocol=767 (1.21.1), addr, port, next_state=1
    $hs = "\x00" . writeVarint(767) . writeVarint(strlen($handshakeHost)) . $handshakeHost
        . pack('n', $port) . "\x01";
    fwrite($sock, writeVarint(strlen($hs)) . $hs);
    // status request: id=0x00, empty
    fwrite($sock, writeVarint(1) . "\x00");

    $len = readVarint($sock);
    $body = $len !== null ? readExact($sock, $len) : null;
    fclose($sock);
    if ($body === null || $body === '') return null;

    // 解析 body: packet_id varint + string varint + json
    $pos = 0;
    $readV = static function (string $s, int &$p): ?int {
        $n = 0; $shift = 0;
        for ($i = 0; $i < 5; $i++) {
            if ($p >= strlen($s)) return null;
            $b = ord($s[$p++]);
            $n |= ($b & 0x7f) << $shift;
            if (($b & 0x80) === 0) return $n;
            $shift += 7;
        }
        return null;
    };
    $id = $readV($body, $pos);
    if ($id !== 0) return null;
    $slen = $readV($body, $pos);
    if ($slen === null || $slen <= 0 || $pos + $slen > strlen($body)) return null;
    $json = json_decode(substr($body, $pos, $slen), true);
    if (!is_array($json)) return null;

    $latency = (int) round((microtime(true) - $lat0) * 1000);
    $desc = $json['description'] ?? '';
    if (is_array($desc)) {
        $parts = [];
        if (isset($desc['extra']) && is_array($desc['extra'])) {
            foreach ($desc['extra'] as $e) $parts[] = is_array($e) ? (string) ($e['text'] ?? '') : (string) $e;
        }
        if (isset($desc['text']) && is_string($desc['text'])) $parts[] = $desc['text'];
        $desc = implode('', $parts);
    }
    $players = is_array($json['players'] ?? null) ? $json['players'] : [];
    $sample = is_array($players['sample'] ?? null) ? $players['sample'] : [];
    $names = [];
    foreach ($sample as $s) $names[] = is_array($s) ? (string) ($s['name'] ?? '') : (string) $s;

    return [
        'online'        => true,
        'version'       => (string) (((is_array($json['version'] ?? null) ? $json['version']['name'] : null)) ?? ''),
        'playersOnline' => (int) ($players['online'] ?? 0),
        'playersMax'    => (int) ($players['max'] ?? 0),
        'playerList'    => $names,
        'motd'          => (string) $desc,
        'favicon'       => is_string($json['favicon'] ?? null) ? $json['favicon'] : '',
        'srvHost'       => $srvHost,
        'srvPort'       => $port,
        'latency'       => $latency,
    ];
}

/* SRV 目标也必须是公网地址 */
$connectTarget = $srvHost !== '' ? $srvHost : $host;
if ($connectTarget !== $ip) {
    $tip = gethostbyname($connectTarget);
    if ($tip === $connectTarget || !isPublicIPv4($tip)) {
        fail('reserved srv address', 403);
    }
}

$result = probe($connectTarget, $host, $port, $CONNECT_TIMEOUT, $srvHost);

if (is_array($result)) {
    if (isset($TPS_SOURCES[$host])) {
        $tps = fetch_tps($TPS_SOURCES[$host]['url'], $TPS_SOURCES[$host]['vhost']);
        if ($tps !== null) $result['tps'] = $tps;
    }
    $result['ts'] = $now;
    @file_put_contents($cacheFile, json_encode($result));
    unset($result['ts']);
    emit($result);
}

/* 探测失败：回退最近一次成功结果（带 stale 标记），避免误报离线 */
if (is_array($stale)) {
    $out = $stale;
    unset($out['ts']);
    $out['stale'] = true;
    emit($out);
}
emit(['online' => false]);
