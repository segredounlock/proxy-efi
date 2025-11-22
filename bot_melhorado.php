<?php
/**
 * SEGREDO A12+ Activation Lock Bypass Bot
 * VERSÃO MELHORADA - Sistema de Broadcast Avançado
 * 
 * ✅ NOVOS RECURSOS:
 * - Broadcast por resposta (foto, vídeo, áudio, documento, texto)
 * - Sistema de fila para evitar duplicação
 * - Detecção automática de tipo de mídia
 * - Progresso em tempo real aprimorado
 * - Proteção contra envios duplicados
 * - Log detalhado de broadcasts
 * 
 * Data: 22/11/2024
 * Versão: 4.0 MELHORADO
 */

error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(300);
date_default_timezone_set('America/Sao_Paulo');

// ==================== CONFIGURAÇÕES ====================
$config_file = __DIR__ . '/config.php';
if (file_exists($config_file)) {
    $config = require $config_file;
    define('BOT_TOKEN', $config['bot_token']);
    define('BOT_USERNAME', $config['bot_username']);
    define('ADMIN_IDS', $config['admin_ids']);
    define('DHRU_API_URL', $config['dhru_api_url']);
    define('DHRU_USERNAME', $config['dhru_username']);
    define('DHRU_API_KEY', $config['dhru_api_key']);
    define('DHRU_SERVICE_ID', $config['dhru_service_id']);
    define('SERVICE_COST', $config['service_cost']);
    define('GIFT_NOTIFY_CHAT_ID', $config['gift_notify_chat_id']);
    define('GIFT_REDEEM_COOLDOWN', $config['gift_redeem_cooldown']);
} else {
    define('BOT_TOKEN', '8573849766:AAErNoIGk0D3m4o66r65sifKombG9cZuGKA');
    define('BOT_USERNAME', '@Bypasa12_bot');
    define('ADMIN_IDS', [1901426549]);
    define('DHRU_API_URL', 'https://realmcloud.cfd/api_center/api_dhru.php');
    define('DHRU_USERNAME', 'iFastServer');
    define('DHRU_API_KEY', 'iFastServer_API_KEY_ADMIN_2025_SZ00');
    define('DHRU_SERVICE_ID', 1);
    define('SERVICE_COST', 30.00);
    define('GIFT_NOTIFY_CHAT_ID', -1001433615146);
    define('GIFT_REDEEM_COOLDOWN', 1800);
}

// Diretórios
define('DATA_DIR', __DIR__ . '/bot_data');
define('LOGS_DIR', __DIR__ . '/bot_logs');
define('BACKUP_DIR', DATA_DIR . '/backups');

// Arquivos de dados
define('USERS_FILE', DATA_DIR . '/users.json');
define('TRANSACTIONS_FILE', DATA_DIR . '/transactions.json');
define('ORDERS_FILE', DATA_DIR . '/orders.json');
define('GIFTS_FILE', DATA_DIR . '/gifts.json');
define('RENTALS_FILE', DATA_DIR . '/rentals.json');
define('RATE_LIMIT_FILE', DATA_DIR . '/rate_limit.json');
define('BROADCAST_LOCK_FILE', DATA_DIR . '/broadcast.lock');
define('BROADCAST_QUEUE_FILE', DATA_DIR . '/broadcast_queue.json');

// Arquivos de logs
define('LOG_DEBUG', LOGS_DIR . '/debug.log');
define('LOG_UPDATES', LOGS_DIR . '/updates.log');
define('LOG_HANDLER', LOGS_DIR . '/handler_trace.log');
define('LOG_MESSAGES', LOGS_DIR . '/send_message_resp.log');
define('LOG_PIN', LOGS_DIR . '/pin_attempts.log');
define('LOG_BROADCAST', LOGS_DIR . '/broadcast.log');
define('LOG_ERRORS', LOGS_DIR . '/errors.log');
define('LOG_API', LOGS_DIR . '/api_calls.log');

// Lock system
define('BROADCAST_LOCK_TIMEOUT', 600);

// Criar diretórios
foreach ([DATA_DIR, LOGS_DIR, BACKUP_DIR] as $dir) {
    if (!file_exists($dir)) @mkdir($dir, 0755, true);
}

// ==================== UTILITIES ====================
function db_read($file, $default = []) {
    if (!file_exists($file)) {
        db_write($file, $default);
        return $default;
    }
    $content = @file_get_contents($file);
    $data = @json_decode($content, true);
    return $data ?? $default;
}

function db_write($file, $data) {
    return @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// ==================== LOGGING ====================
function bot_log($msg, $file = LOG_DEBUG) {
    $line = date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

function log_error($msg, $context = []) {
    $line = date('Y-m-d H:i:s') . " ERROR: " . $msg;
    if (!empty($context)) {
        $line .= " | Context: " . json_encode($context);
    }
    $line .= PHP_EOL;
    @file_put_contents(LOG_ERRORS, $line, FILE_APPEND | LOCK_EX);
}

function log_api_call($endpoint, $params, $response) {
    $line = date('Y-m-d H:i:s') . " API_CALL\n";
    $line .= "Endpoint: {$endpoint}\n";
    $line .= "Params: " . json_encode($params) . "\n";
    $line .= "Response: " . json_encode($response) . "\n";
    $line .= str_repeat('-', 80) . "\n";
    @file_put_contents(LOG_API, $line, FILE_APPEND | LOCK_EX);
}

// ==================== BROADCAST LOCK SYSTEM ====================

function is_broadcast_running() {
    if (!file_exists(BROADCAST_LOCK_FILE)) {
        return false;
    }
    
    $lock_data = @json_decode(@file_get_contents(BROADCAST_LOCK_FILE), true);
    if (!$lock_data) {
        @unlink(BROADCAST_LOCK_FILE);
        return false;
    }
    
    $started = $lock_data['started'] ?? 0;
    $now = time();
    
    if (($now - $started) > BROADCAST_LOCK_TIMEOUT) {
        @unlink(BROADCAST_LOCK_FILE);
        bot_log("BROADCAST_LOCK: Timeout detectado, lock removido");
        return false;
    }
    
    return true;
}

function create_broadcast_lock($chat_id, $type = 'text', $broadcast_id = null) {
    $lock_data = [
        'admin_id' => $chat_id,
        'type' => $type,
        'started' => time(),
        'pid' => getmypid(),
        'broadcast_id' => $broadcast_id ?? uniqid('bc_')
    ];
    
    @file_put_contents(BROADCAST_LOCK_FILE, json_encode($lock_data), LOCK_EX);
    bot_log("BROADCAST_LOCK: Criado por admin {$chat_id} tipo:{$type} id:{$lock_data['broadcast_id']}");
    
    return $lock_data['broadcast_id'];
}

function remove_broadcast_lock() {
    if (file_exists(BROADCAST_LOCK_FILE)) {
        @unlink(BROADCAST_LOCK_FILE);
        bot_log("BROADCAST_LOCK: Removido");
    }
}

function get_broadcast_lock_info() {
    if (!file_exists(BROADCAST_LOCK_FILE)) {
        return null;
    }
    
    $lock_data = @json_decode(@file_get_contents(BROADCAST_LOCK_FILE), true);
    if (!$lock_data) {
        return null;
    }
    
    $elapsed = time() - ($lock_data['started'] ?? 0);
    $lock_data['elapsed'] = $elapsed;
    $lock_data['elapsed_formatted'] = gmdate("i:s", $elapsed);
    
    return $lock_data;
}

// ==================== BROADCAST QUEUE SYSTEM ====================

function create_broadcast_queue($admin_id, $content_type, $content_data) {
    $broadcast_id = uniqid('bc_');
    
    $queue_entry = [
        'id' => $broadcast_id,
        'admin_id' => $admin_id,
        'content_type' => $content_type,
        'content_data' => $content_data,
        'created_at' => time(),
        'status' => 'pending',
        'sent_to' => [],
        'failed_to' => [],
        'total' => 0,
        'sent' => 0,
        'failed' => 0
    ];
    
    $queue = db_read(BROADCAST_QUEUE_FILE, []);
    $queue[$broadcast_id] = $queue_entry;
    db_write(BROADCAST_QUEUE_FILE, $queue);
    
    bot_log("BROADCAST_QUEUE: Criado broadcast_id={$broadcast_id} tipo={$content_type}");
    
    return $broadcast_id;
}

function update_broadcast_queue($broadcast_id, $updates) {
    $queue = db_read(BROADCAST_QUEUE_FILE, []);
    
    if (isset($queue[$broadcast_id])) {
        $queue[$broadcast_id] = array_merge($queue[$broadcast_id], $updates);
        db_write(BROADCAST_QUEUE_FILE, $queue);
    }
}

function mark_user_as_sent($broadcast_id, $chat_id, $success = true) {
    $queue = db_read(BROADCAST_QUEUE_FILE, []);
    
    if (isset($queue[$broadcast_id])) {
        if ($success) {
            if (!in_array($chat_id, $queue[$broadcast_id]['sent_to'])) {
                $queue[$broadcast_id]['sent_to'][] = $chat_id;
                $queue[$broadcast_id]['sent']++;
            }
        } else {
            if (!in_array($chat_id, $queue[$broadcast_id]['failed_to'])) {
                $queue[$broadcast_id]['failed_to'][] = $chat_id;
                $queue[$broadcast_id]['failed']++;
            }
        }
        
        db_write(BROADCAST_QUEUE_FILE, $queue);
    }
}

function is_already_sent($broadcast_id, $chat_id) {
    $queue = db_read(BROADCAST_QUEUE_FILE, []);
    
    if (isset($queue[$broadcast_id])) {
        return in_array($chat_id, $queue[$broadcast_id]['sent_to']);
    }
    
    return false;
}

function cleanup_old_broadcasts() {
    $queue = db_read(BROADCAST_QUEUE_FILE, []);
    $cutoff = time() - (7 * 86400); // 7 dias
    
    foreach ($queue as $id => $entry) {
        if ($entry['created_at'] < $cutoff) {
            unset($queue[$id]);
        }
    }
    
    db_write(BROADCAST_QUEUE_FILE, $queue);
}

// ==================== RATE LIMITING ====================
function check_rate_limit($chat_id, $command, $limit_seconds = 3) {
    $user = get_user($chat_id);
    
    if ($user['is_admin']) {
        return ['allowed' => true, 'wait' => 0];
    }
    
    $limits = db_read(RATE_LIMIT_FILE, []);
    $key = "{$chat_id}_{$command}";
    
    if (isset($limits[$key])) {
        $elapsed = time() - $limits[$key];
        if ($elapsed < $limit_seconds) {
            return ['allowed' => false, 'wait' => $limit_seconds - $elapsed];
        }
    }
    
    $limits[$key] = time();
    
    $cutoff = time() - 3600;
    foreach ($limits as $k => $timestamp) {
        if ($timestamp < $cutoff) {
            unset($limits[$k]);
        }
    }
    
    db_write(RATE_LIMIT_FILE, $limits);
    return ['allowed' => true, 'wait' => 0];
}

// ==================== BACKUP ====================
function auto_backup() {
    if (!file_exists(BACKUP_DIR)) @mkdir(BACKUP_DIR, 0755, true);
    
    $timestamp = date('Y-m-d_H-i-s');
    $files = ['users.json', 'transactions.json', 'orders.json', 'gifts.json', 'rentals.json'];
    
    $backed_up = 0;
    foreach ($files as $file) {
        $source = DATA_DIR . '/' . $file;
        if (file_exists($source)) {
            $dest = BACKUP_DIR . '/' . $timestamp . '_' . $file;
            if (@copy($source, $dest)) {
                $backed_up++;
            }
        }
    }
    
    $backups = glob(BACKUP_DIR . '/*');
    if (count($backups) > 140) {
        usort($backups, fn($a, $b) => filemtime($a) - filemtime($b));
        foreach (array_slice($backups, 0, count($backups) - 140) as $old) {
            @unlink($old);
        }
    }
    
    bot_log("AUTO_BACKUP: {$backed_up} arquivos salvos em {$timestamp}");
    return $backed_up;
}

function check_auto_backup() {
    $marker_file = BACKUP_DIR . '/.last_backup';
    
    if (file_exists($marker_file)) {
        $last_backup = filemtime($marker_file);
        $hours_since = (time() - $last_backup) / 3600;
        
        if ($hours_since < 6) {
            return false;
        }
    }
    
    auto_backup();
    @touch($marker_file);
    return true;
}

// ==================== USER MANAGEMENT ====================
function get_user($chat_id) {
    $id = strval($chat_id);
    $users = db_read(USERS_FILE, []);
    if (!isset($users[$id])) {
        $users[$id] = [
            'chat_id' => (int)$chat_id,
            'credits' => 0.00,
            'registered' => date('Y-m-d H:i:s'),
            'last_seen' => date('Y-m-d H:i:s'),
            'total_spent' => 0.00,
            'total_orders' => 0,
            'username' => null,
            'name' => null,
            'is_admin' => in_array((int)$chat_id, ADMIN_IDS, true),
            'last_gift_redeem' => null
        ];
        db_write(USERS_FILE, $users);
        bot_log("NEW_USER: {$chat_id}");
    }
    return $users[$id];
}

function update_user($chat_id, $data) {
    $id = strval($chat_id);
    $users = db_read(USERS_FILE, []);
    if (isset($users[$id])) {
        $users[$id] = array_merge($users[$id], $data);
        db_write(USERS_FILE, $users);
    }
}

function add_credits($chat_id, $amount, $admin_id = null) {
    $id = strval($chat_id);
    $users = db_read(USERS_FILE, []);
    if (isset($users[$id])) {
        $users[$id]['credits'] = round($users[$id]['credits'] + floatval($amount), 2);
        db_write(USERS_FILE, $users);

        add_transaction($chat_id, 'credit_add', floatval($amount), [
            'by_admin' => $admin_id,
            'balance' => $users[$id]['credits']
        ]);

        bot_log("CREDITS_ADD: {$amount} to {$chat_id} by admin {$admin_id}");
        return $users[$id]['credits'];
    }
    return 0;
}

function charge_credits($chat_id, $amount, $type, $meta = []) {
    $id = strval($chat_id);
    $users = db_read(USERS_FILE, []);
    if (!isset($users[$id]) || $users[$id]['credits'] < floatval($amount)) {
        return false;
    }

    $users[$id]['credits'] = round($users[$id]['credits'] - floatval($amount), 2);
    $users[$id]['total_spent'] = round($users[$id]['total_spent'] + floatval($amount), 2);
    if ($type === 'order_success') {
        $users[$id]['total_orders']++;
    }

    db_write(USERS_FILE, $users);

    add_transaction($chat_id, $type, -floatval($amount), array_merge($meta, [
        'balance' => $users[$id]['credits']
    ]));

    bot_log("CREDITS_CHARGE: {$amount} from {$chat_id} for {$type}");
    return true;
}

// ==================== TRANSACTIONS & ORDERS ====================
function add_transaction($chat_id, $type, $amount, $meta = []) {
    $txs = db_read(TRANSACTIONS_FILE, []);
    $txs[] = [
        'id' => uniqid('tx_'),
        'chat_id' => (int)$chat_id,
        'type' => $type,
        'amount' => floatval($amount),
        'time' => date('Y-m-d H:i:s'),
        'meta' => $meta
    ];
    if (count($txs) > 1000) $txs = array_slice($txs, -1000);
    db_write(TRANSACTIONS_FILE, $txs);
}

function add_order($chat_id, $serial, $order_id, $status, $cost = SERVICE_COST) {
    $orders = db_read(ORDERS_FILE, []);
    $orders[] = [
        'id' => uniqid('ord_'),
        'chat_id' => (int)$chat_id,
        'serial' => $serial,
        'order_id' => $order_id,
        'status' => $status,
        'time' => date('Y-m-d H:i:s'),
        'cost' => floatval($cost)
    ];
    db_write(ORDERS_FILE, $orders);
    bot_log("ORDER_ADD: {$serial} by {$chat_id} status:{$status} cost:{$cost}");
}

function get_user_orders($chat_id, $limit = 10) {
    $orders = db_read(ORDERS_FILE, []);
    $user_orders = array();
    foreach ($orders as $o) {
        if ((int)$o['chat_id'] === (int)$chat_id) $user_orders[] = $o;
    }
    usort($user_orders, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    return array_slice($user_orders, 0, $limit);
}

function is_duplicate_order($chat_id, $serial, $window_minutes = 5) {
    $orders = db_read(ORDERS_FILE, []);
    $cutoff = time() - ($window_minutes * 60);
    
    foreach ($orders as $o) {
        if ((int)$o['chat_id'] === (int)$chat_id && 
            strtoupper($o['serial']) === strtoupper($serial) && 
            strtotime($o['time']) > $cutoff) {
            bot_log("DUPLICATE_ORDER_DETECTED: {$serial} by {$chat_id}");
            return true;
        }
    }
    return false;
}

function get_stats() {
    $users = db_read(USERS_FILE, []);
    $orders = db_read(ORDERS_FILE, []);
    $total_credits = 0;
    $total_spent = 0;
    foreach ($users as $u) {
        $total_credits += floatval($u['credits']);
        $total_spent += floatval($u['total_spent']);
    }
    return [
        'users' => count($users),
        'credits' => round($total_credits, 2),
        'spent' => round($total_spent, 2),
        'orders' => count($orders)
    ];
}

// ==================== GIFTS ====================
function load_gifts() {
    return db_read(GIFTS_FILE, []);
}

function save_gifts($gifts) {
    return db_write(GIFTS_FILE, $gifts);
}

function generate_gift_code($length = 12) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, $max)];
    }
    return substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
}

function check_gift_cooldown($chat_id) {
    $user = get_user($chat_id);
    
    if ($user['is_admin']) {
        return ['allowed' => true, 'remaining' => 0];
    }
    
    if (empty($user['last_gift_redeem'])) {
        return ['allowed' => true, 'remaining' => 0];
    }
    
    $last_redeem = strtotime($user['last_gift_redeem']);
    $now = time();
    $elapsed = $now - $last_redeem;
    
    if ($elapsed >= GIFT_REDEEM_COOLDOWN) {
        return ['allowed' => true, 'remaining' => 0];
    }
    
    $remaining = GIFT_REDEEM_COOLDOWN - $elapsed;
    return ['allowed' => false, 'remaining' => $remaining];
}

function update_gift_redeem_time($chat_id) {
    update_user($chat_id, ['last_gift_redeem' => date('Y-m-d H:i:s')]);
}

// ==================== RENTALS ====================
function load_rentals() {
    return db_read(RENTALS_FILE, []);
}

function save_rentals($rentals) {
    return db_write(RENTALS_FILE, $rentals);
}

function is_plan_active($chat_id) {
    $rentals = load_rentals();
    $id = strval($chat_id);
    if (!isset($rentals[$id]) || empty($rentals[$id]['expires'])) {
        return ['active' => false, 'expires' => null];
    }
    $exp = strtotime($rentals[$id]['expires']);
    if ($exp > time()) return ['active' => true, 'expires' => $exp];
    return ['active' => false, 'expires' => $exp];
}

function remover_plano($chat_id) {
    $rentals = load_rentals();
    $id = strval($chat_id);
    if (!isset($rentals[$id])) return false;
    unset($rentals[$id]);
    save_rentals($rentals);
    bot_log("PLAN_REMOVED: {$chat_id}");
    return true;
}

function check_plan_expiry_notify($chat_id) {
    $id = strval($chat_id);
    $rentals = load_rentals();

    if (!isset($rentals[$id]) || empty($rentals[$id]['expires'])) {
        return false;
    }

    $exp_ts = strtotime($rentals[$id]['expires']);

    if ($exp_ts > time()) {
        return false;
    }

    if (!empty($rentals[$id]['expired_notified'])) {
        return false;
    }

    $rentals[$id]['expired_notified'] = true;
    save_rentals($rentals);

    $user_msg = "⚠️ <b>Plano Expirado</b>\n\n" .
                "Seu plano expirou em <b>" . date('d/m H:i', $exp_ts) . "</b>.\n" .
                "Para continuar usando os serviços sem gastar créditos:\n" .
                "👉 renove seu plano ou compre créditos (/buy).";

    send_message($chat_id, $user_msg);

    $admin_msg = "📢 <b>PLANO EXPIRADO</b>\n\n" .
                 "Usuário <code>$chat_id</code> teve seu plano expirado.\n" .
                 "⏰ Expirado em: <b>" . date('d/m H:i', $exp_ts) . "</b>\n\n" .
                 "Notificação automática.";

    $admins = array_unique(ADMIN_IDS);
    foreach ($admins as $admin) {
        if ((int)$admin === (int)$chat_id) continue;
        send_message($admin, $admin_msg);
    }

    bot_log("PLAN_EXPIRED: {$chat_id} notified");
    return true;
}

// ==================== VALIDATION ====================
function validate_serial($serial) {
    $serial = strtoupper(trim($serial));
    $valid_lengths = [8, 10, 11, 12, 15];
    $len = strlen($serial);
    if (!in_array($len, $valid_lengths)) {
        return ['valid' => false, 'msg' => '❌ O serial deve ter 8, 10, 11, 12 ou 15 caracteres'];
    }
    if (!preg_match('/^[A-Z0-9]+$/', $serial)) {
        return ['valid' => false, 'msg' => '❌ O serial só pode conter letras e números'];
    }
    if (strpos($serial, 'O') !== false || strpos($serial, 'I') !== false) {
        return ['valid' => false, 'msg' => '❌ Serial Apple inválido (contém O ou I)'];
    }
    if (preg_match('/(.)\1{4,}/', $serial)) {
        return ['valid' => false, 'msg' => '❌ Padrão de serial inválido'];
    }
    $blacklist = ['00000000', '11111111', 'TESTTEST', 'AAAAAAAA', 'SAMPLE12', 'DEMO1234', 'FAKE1234'];
    foreach ($blacklist as $bad) {
        if (strpos($serial, $bad) !== false) {
            return ['valid' => false, 'msg' => '❌ Serial bloqueado (blacklist)'];
        }
    }
    if (!preg_match('/[A-Z]/', $serial) || !preg_match('/[0-9]/', $serial)) {
        return ['valid' => false, 'msg' => '❌ O serial deve conter letras e números'];
    }
    return ['valid' => true, 'serial' => $serial];
}

// ==================== API ====================
function process_order($serial) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' .
           '<PARAMETERS>' .
           '<ID>' . DHRU_SERVICE_ID . '</ID>' .
           '<IMEI>' . $serial . '</IMEI>' .
           '</PARAMETERS>';

    $post = [
        'username' => DHRU_USERNAME,
        'apiaccesskey' => DHRU_API_KEY,
        'action' => 'placeimeiorder',
        'parameters' => $xml
    ];

    $ch = curl_init(DHRU_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    log_api_call(DHRU_API_URL, $post, ['http_code' => $http_code, 'response' => $response]);

    if ($http_code != 200 || empty($response)) {
        return ['success' => false, 'chargeable' => false, 'msg' => '❌ Erro de conexão com a API'];
    }

    $data = @json_decode($response, true);

    if (isset($data['SUCCESS'][0])) {
        $order_id = $data['SUCCESS'][0]['REFERENCEID'] ?? 'PENDING';
        return [
            'success' => true,
            'chargeable' => true,
            'order_id' => $order_id,
            'msg' => '✅ Pedido enviado com sucesso'
        ];
    }

    if (isset($data['ERROR'][0])) {
        $error = $data['ERROR'][0]['MESSAGE'] ?? 'Erro desconhecido';
        $no_charge = ['Duplicate Order', 'Invalid IMEI', 'Service not found', 'Authentication Failed'];
        $chargeable = !in_array($error, $no_charge);
        return [
            'success' => false,
            'chargeable' => $chargeable,
            'msg' => '❌ ' . $error
        ];
    }

    return ['success' => false, 'chargeable' => false, 'msg' => '❌ Resposta desconhecida da API'];
}

// ==================== TELEGRAM API ====================

function telegram_api_request($method, $data = []) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/{$method}";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 10
    ]);

    $resp = curl_exec($ch);
    $json = @json_decode($resp, true);
    curl_close($ch);

    return $json;
}

function send_message($chat_id, $text, $parse = 'HTML', $reply_markup = null) {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $parse,
        'disable_web_page_preview' => true
    ];
    
    if ($reply_markup) {
        $data['reply_markup'] = json_encode($reply_markup);
    }

    $json = telegram_api_request('sendMessage', $data);

    $log_msg = date('Y-m-d H:i:s') . " SEND_MESSAGE TO {$chat_id}: " . substr($text,0,200) . 
               "\nRESPONSE: " . var_export($json, true) . "\n\n";
    @file_put_contents(LOG_MESSAGES, $log_msg, FILE_APPEND | LOCK_EX);

    return $json;
}

function send_photo($chat_id, $photo, $caption = null, $parse = 'HTML') {
    $data = [
        'chat_id' => $chat_id,
        'photo' => $photo,
        'parse_mode' => $parse
    ];
    
    if ($caption) {
        $data['caption'] = $caption;
    }

    return telegram_api_request('sendPhoto', $data);
}

function send_video($chat_id, $video, $caption = null, $parse = 'HTML') {
    $data = [
        'chat_id' => $chat_id,
        'video' => $video,
        'parse_mode' => $parse
    ];
    
    if ($caption) {
        $data['caption'] = $caption;
    }

    return telegram_api_request('sendVideo', $data);
}

function send_audio($chat_id, $audio, $caption = null, $parse = 'HTML') {
    $data = [
        'chat_id' => $chat_id,
        'audio' => $audio,
        'parse_mode' => $parse
    ];
    
    if ($caption) {
        $data['caption'] = $caption;
    }

    return telegram_api_request('sendAudio', $data);
}

function send_document($chat_id, $document, $caption = null, $parse = 'HTML') {
    $data = [
        'chat_id' => $chat_id,
        'document' => $document,
        'parse_mode' => $parse
    ];
    
    if ($caption) {
        $data['caption'] = $caption;
    }

    return telegram_api_request('sendDocument', $data);
}

function send_voice($chat_id, $voice, $caption = null, $parse = 'HTML') {
    $data = [
        'chat_id' => $chat_id,
        'voice' => $voice,
        'parse_mode' => $parse
    ];
    
    if ($caption) {
        $data['caption'] = $caption;
    }

    return telegram_api_request('sendVoice', $data);
}

function copy_message($chat_id, $from_chat_id, $message_id) {
    return telegram_api_request('copyMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id
    ]);
}

function forward_message($chat_id, $from_chat_id, $message_id) {
    return telegram_api_request('forwardMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id
    ]);
}

function edit_message_text($chat_id, $message_id, $text, $parse = 'HTML') {
    return telegram_api_request('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => $parse
    ]);
}

function pin_message($chat_id, $message_id, $disable_notification = false) {
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'disable_notification' => $disable_notification ? true : false
    ];
    
    $json = telegram_api_request('pinChatMessage', $data);
    
    $log_msg = date('Y-m-d H:i:s') . " PIN_ATTEMPT: chat={$chat_id} msg={$message_id} " .
               "resp=" . var_export($json, true) . "\n";
    @file_put_contents(LOG_PIN, $log_msg, FILE_APPEND | LOCK_EX);
    
    return $json;
}

// ==================== BROADCAST MELHORADO ====================

function send_broadcast_content($chat_id, $content_type, $content_data) {
    try {
        switch ($content_type) {
            case 'text':
                return send_message($chat_id, $content_data['text']);
                
            case 'photo':
                return send_photo(
                    $chat_id, 
                    $content_data['photo'], 
                    $content_data['caption'] ?? null
                );
                
            case 'video':
                return send_video(
                    $chat_id, 
                    $content_data['video'], 
                    $content_data['caption'] ?? null
                );
                
            case 'audio':
                return send_audio(
                    $chat_id, 
                    $content_data['audio'], 
                    $content_data['caption'] ?? null
                );
                
            case 'voice':
                return send_voice(
                    $chat_id, 
                    $content_data['voice'], 
                    $content_data['caption'] ?? null
                );
                
            case 'document':
                return send_document(
                    $chat_id, 
                    $content_data['document'], 
                    $content_data['caption'] ?? null
                );
                
            case 'copy':
                return copy_message(
                    $chat_id,
                    $content_data['from_chat_id'],
                    $content_data['message_id']
                );
                
            case 'forward':
                return forward_message(
                    $chat_id,
                    $content_data['from_chat_id'],
                    $content_data['message_id']
                );
                
            default:
                return ['ok' => false, 'description' => 'Tipo de conteúdo desconhecido'];
        }
    } catch (Exception $e) {
        return ['ok' => false, 'description' => $e->getMessage()];
    }
}

function execute_broadcast($admin_id, $content_type, $content_data, $status_msg_id = null) {
    $users = db_read(USERS_FILE, []);
    if (empty($users)) {
        return ['success' => false, 'message' => 'Nenhum usuário registrado'];
    }
    
    // Criar broadcast na fila
    $broadcast_id = create_broadcast_queue($admin_id, $content_type, $content_data);
    
    // Criar lock
    create_broadcast_lock($admin_id, $content_type, $broadcast_id);
    
    $total = count($users);
    $sent = 0;
    $failed = 0;
    $errors = [];
    
    // Log inicial
    $log_msg = "========== BROADCAST INICIADO ==========\n";
    $log_msg .= "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
    $log_msg .= "Admin: {$admin_id}\n";
    $log_msg .= "Broadcast ID: {$broadcast_id}\n";
    $log_msg .= "Tipo: {$content_type}\n";
    $log_msg .= "Total de usuários: {$total}\n";
    $log_msg .= "PID: " . getmypid() . "\n";
    $log_msg .= "========================================\n\n";
    @file_put_contents(LOG_BROADCAST, $log_msg, FILE_APPEND | LOCK_EX);
    
    // Atualizar fila
    update_broadcast_queue($broadcast_id, ['status' => 'running', 'total' => $total]);
    
    $processed = 0;
    $last_update = 0;
    
    foreach ($users as $u) {
        $target_chat_id = $u['chat_id'];
        
        // Verificar se lock ainda existe
        if (!is_broadcast_running()) {
            bot_log("BROADCAST_ABORTED: Lock removido durante execução");
            
            $abort_msg = "⚠️ <b>BROADCAST CANCELADO</b>\n\n";
            $abort_msg .= "📊 Progresso antes do cancelamento:\n";
            $abort_msg .= "✅ Enviados: {$sent}\n";
            $abort_msg .= "❌ Falhas: {$failed}\n";
            $abort_msg .= "⏸️ Interrompido em: {$processed}/{$total}";
            
            if ($status_msg_id) {
                edit_message_text($admin_id, $status_msg_id, $abort_msg);
            }
            
            update_broadcast_queue($broadcast_id, ['status' => 'cancelled']);
            
            return ['success' => false, 'message' => 'Broadcast cancelado'];
        }
        
        // Verificar se já foi enviado (proteção contra duplicação)
        if (is_already_sent($broadcast_id, $target_chat_id)) {
            bot_log("BROADCAST_SKIP: Mensagem já enviada para {$target_chat_id}");
            $processed++;
            continue;
        }
        
        // Enviar conteúdo
        $resp = send_broadcast_content($target_chat_id, $content_type, $content_data);
        
        if (isset($resp['ok']) && $resp['ok']) {
            $sent++;
            mark_user_as_sent($broadcast_id, $target_chat_id, true);
            $log_entry = "✅ ENVIADO para {$target_chat_id}\n";
        } else {
            $failed++;
            $error_desc = $resp['description'] ?? 'Erro desconhecido';
            $errors[] = "{$target_chat_id}: {$error_desc}";
            mark_user_as_sent($broadcast_id, $target_chat_id, false);
            $log_entry = "❌ FALHOU para {$target_chat_id}: {$error_desc}\n";
        }
        
        @file_put_contents(LOG_BROADCAST, $log_entry, FILE_APPEND | LOCK_EX);
        
        $processed++;
        
        // Atualizar progresso a cada 10 usuários ou 5 segundos
        $now = time();
        if ($processed % 10 === 0 || ($now - $last_update >= 5)) {
            $percent = round(($processed / $total) * 100);
            $progress_bar = str_repeat('▓', floor($percent / 5)) . str_repeat('░', 20 - floor($percent / 5));
            
            $progress_msg = "📢 <b>BROADCAST EM ANDAMENTO</b>\n\n";
            $progress_msg .= "📊 Progresso: <b>{$processed}/{$total}</b> ({$percent}%)\n";
            $progress_msg .= "{$progress_bar}\n\n";
            $progress_msg .= "✅ Enviados: <b>{$sent}</b>\n";
            $progress_msg .= "❌ Falhas: <b>{$failed}</b>\n\n";
            $progress_msg .= "⏳ Processando...";
            
            if ($status_msg_id) {
                edit_message_text($admin_id, $status_msg_id, $progress_msg);
            }
            
            // Atualizar fila
            update_broadcast_queue($broadcast_id, [
                'sent' => $sent,
                'failed' => $failed
            ]);
            
            $last_update = $now;
        }
        
        // Pequeno delay para evitar flood
        usleep(150000); // 150ms
    }
    
    // Remover lock
    remove_broadcast_lock();
    
    // Atualizar fila
    update_broadcast_queue($broadcast_id, [
        'status' => 'completed',
        'sent' => $sent,
        'failed' => $failed,
        'completed_at' => time()
    ]);
    
    $success_rate = $total > 0 ? round(($sent / $total) * 100, 1) : 0;
    
    $final_msg = "📢 <b>BROADCAST CONCLUÍDO</b>\n\n";
    $final_msg .= "🆔 ID: <code>{$broadcast_id}</code>\n";
    $final_msg .= "📊 <b>ESTATÍSTICAS:</b>\n";
    $final_msg .= "━━━━━━━━━━━━━━━━━━━━\n";
    $final_msg .= "👥 Total de usuários: <b>{$total}</b>\n";
    $final_msg .= "✅ Enviados com sucesso: <b>{$sent}</b>\n";
    $final_msg .= "❌ Falhas: <b>{$failed}</b>\n";
    $final_msg .= "📈 Taxa de sucesso: <b>{$success_rate}%</b>\n";
    $final_msg .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    
    if ($failed > 0 && count($errors) > 0) {
        $final_msg .= "⚠️ <b>ERROS DETECTADOS:</b>\n\n";
        $error_limit = min(5, count($errors));
        for ($i = 0; $i < $error_limit; $i++) {
            $final_msg .= "• <code>" . htmlspecialchars($errors[$i], ENT_QUOTES) . "</code>\n";
        }
        if (count($errors) > $error_limit) {
            $final_msg .= "\n<i>... e mais " . (count($errors) - $error_limit) . " erros</i>\n";
        }
        $final_msg .= "\n💡 Verifique: bot_logs/broadcast.log\n\n";
    }
    
    $final_msg .= "⏱️ Concluído em: " . date('d/m/Y H:i:s');
    
    if ($status_msg_id) {
        edit_message_text($admin_id, $status_msg_id, $final_msg);
    } else {
        send_message($admin_id, $final_msg);
    }
    
    // Log final
    $log_final = "\n========== BROADCAST FINALIZADO ==========\n";
    $log_final .= "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
    $log_final .= "Broadcast ID: {$broadcast_id}\n";
    $log_final .= "Total: {$total} | Enviados: {$sent} | Falhas: {$failed}\n";
    $log_final .= "Taxa de sucesso: {$success_rate}%\n";
    $log_final .= "==========================================\n\n";
    @file_put_contents(LOG_BROADCAST, $log_final, FILE_APPEND | LOCK_EX);
    
    bot_log("BROADCAST_COMPLETED: id={$broadcast_id} admin={$admin_id} total={$total} sent={$sent} failed={$failed}");
    
    return ['success' => true, 'broadcast_id' => $broadcast_id, 'sent' => $sent, 'failed' => $failed];
}

// ==================== COMANDOS BÁSICOS (continuação no próximo arquivo) ====================
