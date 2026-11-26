<?php
declare(strict_types=1);

/**
 * Configuración segura para desarrollo local y despliegue.
 * Producción: definir MT_ENV=production, MT_ALLOWED_ORIGINS, DB_HOST, DB_NAME,
 * DB_USER y DB_PASS en el entorno del servidor. Nunca guardar secretos en Git.
 */
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$isProduction = getenv('MT_ENV') === 'production';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('expose_php', '0');
session_name('MTSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $https || $isProduction,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Permitted-Cross-Domain-Policies: none');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'");
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
header('Cross-Origin-Resource-Policy: same-site');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
if ($https || $isProduction) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

$defaultOrigins = 'http://localhost:5173,http://127.0.0.1:5173';
$originSetting = getenv('MT_ALLOWED_ORIGINS') ?: ($isProduction ? '' : $defaultOrigins);
$allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $originSetting))));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    if (!in_array($origin, $allowedOrigins, true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Origen no autorizado.']);
        exit;
    }
    header('Access-Control-Allow-Origin: '.$origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($isProduction && !$https) {
    http_response_code(400);
    echo json_encode(['error' => 'La aplicación requiere HTTPS.']);
    exit;
}

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'mt_tienda');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if ($isProduction && DB_PASS === '') {
    error_log('M&T: DB_PASS no está configurada en producción.');
    http_response_code(500);
    echo json_encode(['error' => 'Configuración de base de datos incompleta.']);
    exit;
}

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_TIMEOUT => 5
            ]
        );
    }
    return $pdo;
}

function input(): array {
    $length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($length > 2 * 1024 * 1024) out(['error' => 'La solicitud supera el tamaño permitido.'], 413);
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function out($data, int $status=200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function requireCsrf(): void {
    $safe = in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET','HEAD','OPTIONS'], true);
    if ($safe) return;
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $known = $_SESSION['csrf_token'] ?? '';
    if (!$known || !$sent || !hash_equals($known, $sent)) {
        out(['error'=>'Sesión vencida o solicitud no válida. Recarga la página e inténtalo de nuevo.'], 419);
    }
}

function audit(string $action, ?int $entityId=null, array $meta=[]): void {
    try {
        $u = $_SESSION['user'] ?? [];
        $q = db()->prepare('INSERT INTO audit_logs(user_id,action,entity_id,details,ip_address) VALUES(?,?,?,?,?)');
        $q->execute([
            $u['id'] ?? null,
            $action,
            $entityId,
            json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Throwable $e) {
        error_log('M&T audit failed: '.$e->getMessage());
    }
}

function requireRole(array $roles): array {
    $u = $_SESSION['user'] ?? null;
    if (!$u || !isset($u['id'], $u['profile']) || !in_array($u['profile'], $roles, true)) {
        out(['error'=>'No autorizado'], 403);
    }
    return $u;
}


function canTransitionOrder(string $current, string $next, string $role): bool {
    if ($role === 'Administrador') return $next !== 'Entregado' || $current === 'Cerca de tu calle';
    $transitions = [
        'Nuevo' => ['Pago confirmado', 'En preparación', 'Preparado', 'Cancelado'],
        'Pago confirmado' => ['En preparación', 'Cancelado'],
        'En preparación' => ['Empacado', 'Cancelado'],
        'Preparado' => ['Empacado', 'Cancelado'],
        'Empacado' => ['Enviado'],
        'Enviado' => ['En camino'],
        'En camino' => ['Llegando a tu zona', 'Cerca de tu calle'],
        'Llegando a tu zona' => ['Cerca de tu calle'],
        'Cerca de tu calle' => []
    ];
    if ($role === 'Repartidor' && !in_array($next, ['En camino', 'Llegando a tu zona', 'Cerca de tu calle'], true)) return false;
    return in_array($next, $transitions[$current] ?? [], true);
}

function productRow(array $p): array {
    return [
        'id'=>(int)$p['id'], 'name'=>$p['name'], 'category'=>$p['category'],
        'price'=>(float)$p['price'],
        'oldPrice'=>$p['old_price']===null?null:(float)$p['old_price'],
        'stock'=>(int)$p['stock'], 'minStock'=>(int)$p['min_stock'],
        'image'=>$p['image'], 'description'=>$p['description'],
        'rating'=>(float)$p['rating'], 'reviews'=>(int)$p['reviews'],
        'active'=>(bool)$p['active'], 'featured'=>(bool)$p['featured'],
        'images'=>[], 'attributes'=>[]
    ];
}

function orderRow(array $o): array {
    $q=db()->prepare('SELECT product_id,name,qty,price FROM order_items WHERE order_id=?');
    $q->execute([$o['id']]);
    $items=array_map(
        fn($i)=>[
            'productId'=>$i['product_id']===null?null:(int)$i['product_id'],
            'name'=>$i['name'], 'qty'=>(int)$i['qty'], 'price'=>(float)$i['price']
        ],
        $q->fetchAll()
    );
    return [
        'id'=>$o['folio'], 'customer'=>$o['customer'], 'email'=>$o['email'],
        'address'=>$o['address'], 'date'=>date('Y-m-d',strtotime($o['order_date'])),
        'total'=>(float)$o['total'], 'subtotal'=>(float)$o['subtotal'],
        'discount'=>(float)$o['discount'], 'coupon'=>$o['coupon'],
        'payment'=>$o['payment'], 'status'=>$o['status'], 'items'=>$items,
        'tracking'=>[], 'publicToken'=>$o['public_token']??null
    ];
}
