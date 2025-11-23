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
<?php
/**
 * COMANDOS DO BOT - Parte 2
 * Sistema de comandos melhorado com suporte a broadcast por resposta
 */

// ==================== COMANDOS DE USUÁRIO ====================

function cmd_start($chat_id, $name) {
    $user = get_user($chat_id);
    $admin_badge = $user['is_admin'] ? " 👑" : "";
    $safe_name = htmlspecialchars($name ?? 'Anônimo', ENT_QUOTES);

    $msg = "🔓 <b>SEGREDO A12+ Activation Lock Bypass Bot</b>$admin_badge\n\n";
    $msg .= "👤 Usuário: " . $safe_name . "\n";
    $msg .= "💳 Créditos: <b>$" . number_format($user['credits'], 2) . "</b>\n\n";

    $rentals = load_rentals();
    $plan_text = "📦 Plano: 🚫 Nenhum plano ativo";
    $id = strval($chat_id);
    if (isset($rentals[$id]) && !empty($rentals[$id]['expires'])) {
        $exp = strtotime($rentals[$id]['expires']);
        if ($exp > time()) {
            $remaining = $exp - time();
            $days_left = floor($remaining / 86400);
            $plan_text = "📦 Plano: ✅ ativo até " . date('d M Y H:i', $exp) . " ({$days_left}d)";
        }
    }
    $msg .= $plan_text . "\n\n";

    $msg .= "<b>📋 COMANDOS:</b>\n\n";
    $msg .= "🔹 /balance - Ver saldo\n";
    $msg .= "🔹 /buy - Comprar créditos\n";
    $msg .= "🔹 /addsn [IMEI/SN] - Activation Lock Bypass ($" . number_format(SERVICE_COST, 2) . ")\n";
    $msg .= "🔹 /orders - Histórico de pedidos\n";
    $msg .= "🔹 /mystats - Suas estatísticas\n";
    $msg .= "🔹 /history - Histórico de transações\n";
    $msg .= "🔹 /resgatar [CODIGO] - Resgatar gift\n";
    $msg .= "🔹 /help - Ajuda\n";

    if ($user['is_admin']) {
        $msg .= "\n👑 <b>ADMIN:</b>\n\n";
        $msg .= "🔸 /addcredits [id] [valor] - Adicionar créditos\n";
        $msg .= "🔸 /stats - Estatísticas globais\n";
        $msg .= "🔸 /users - Lista de usuários\n";
        $msg .= "🔸 /userinfo [id] - Detalhes do usuário\n";
        $msg .= "🔸 <b>/broadcast [msg]</b> - Broadcast texto\n";
        $msg .= "🔸 <b>RESPONDER mensagem</b> - Broadcast por resposta\n";
        $msg .= "🔸 /broadcast_status - Status do broadcast\n";
        $msg .= "🔸 /broadcast_cancel - Cancelar broadcast\n";
        $msg .= "🔸 /criar_gift [CODE] [mode] [param] [uses]\n";
        $msg .= "🔸 /criar_gifts [qty] [mode] [param] [uses]\n";
        $msg .= "🔸 /gifts_list - Listar gifts\n";
        $msg .= "🔸 /gifts_stats - Estatísticas de gifts\n";
        $msg .= "🔸 /removerplano [id] - Remover plano\n";
        $msg .= "🔸 /remover_gift [CODE] - Remover gift\n";
        $msg .= "🔸 /backup - Fazer backup manual\n\n";
        $msg .= "💡 <b>NOVO:</b> Responda qualquer mensagem (foto, vídeo, áudio, documento) para fazer broadcast!";
    }

    $msg .= "\n\n<b>🔓 SERVIÇO:</b>\n";
    $msg .= "[ SEGREDO A12+ ] Activation Lock Bypass\n";
    $msg .= "XR - 17 Pro Max / iPad Qualquer Modelo\n";
    $msg .= "⚡ RESPOSTA AUTOMÁTICA - INSTANTÂNEA\n\n";
    $msg .= "<b>📱 Aparelhos Suportados:</b>\n";
    $msg .= "✅ iPhone XR, XS, XS Max\n";
    $msg .= "✅ iPhone 11, 11 Pro, 11 Pro Max\n";
    $msg .= "✅ iPhone 12, 12 Mini, 12 Pro, 12 Pro Max\n";
    $msg .= "✅ iPhone 13, 13 Mini, 13 Pro, 13 Pro Max\n";
    $msg .= "✅ iPhone 14, 14 Plus, 14 Pro, 14 Pro Max\n";
    $msg .= "✅ iPhone 15, 15 Plus, 15 Pro, 15 Pro Max\n";
    $msg .= "✅ iPhone 16, 16 Plus, 16 Pro, 16 Pro Max\n";
    $msg .= "✅ iPhone 17, 17 Plus, 17 Pro, 17 Pro Max\n";
    $msg .= "✅ iPad - Todos os modelos\n\n";
    $msg .= "💰 Custo: $" . number_format(SERVICE_COST, 2) . " por serviço\n";
    $msg .= "⚠️ Só é cobrado em caso de sucesso\n\n";
    $msg .= "💳 <b>Precisa de créditos?</b>\n\n";
    $msg .= "👉 <b>Contato:</b> https://t.me/segredoupdates\n\n";
    $msg .= "👉 <b>LINK DOWNLOAD:</b> https://mega.nz/file/5eBSGaaL#58BvZ97wtz__ckWG7eAmdFHVdQVdSeh2tKyDNBVXcKs";

    send_message($chat_id, $msg);
}

function cmd_balance($chat_id) {
    $user = get_user($chat_id);
    $msg = "💰 <b>SEU SALDO</b>\n\n";
    $msg .= "💳 Disponível: <b>$" . number_format($user['credits'], 2) . "</b>\n";
    $msg .= "💸 Total gasto: $" . number_format($user['total_spent'], 2) . "\n";
    $msg .= "📦 Total de pedidos: <b>" . $user['total_orders'] . "</b>\n\n";
    $msg .= "💡 Custo por serviço: $" . number_format(SERVICE_COST, 2). "\n\n";
    $msg .= "👉 <b>Contato:</b> https://t.me/segredoupdates\n\n";
    send_message($chat_id, $msg);
}

function cmd_history($chat_id) {
    $txs = db_read(TRANSACTIONS_FILE, []);
    $user_txs = array_filter($txs, fn($t) => $t['chat_id'] == $chat_id);
    usort($user_txs, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
    $user_txs = array_slice($user_txs, 0, 15);
    if (empty($user_txs)) { 
        send_message($chat_id, "📜 Nenhum histórico de transações"); 
        return; 
    }
    $msg = "📜 <b>HISTÓRICO DE TRANSAÇÕES</b> (Últimas 15)\n\n";
    foreach ($user_txs as $tx) {
        $amount = $tx['amount'];
        $symbol = $amount >= 0 ? '+' : '';
        $emoji = $amount >= 0 ? '💚' : '💸';
        $type_names = [
            'credit_add' => '💳 Crédito adicionado',
            'order_success' => '✅ Bypass com sucesso',
            'order_failed' => '❌ Pedido falho',
            'gift_redeem_credit' => '🎁 Gift crédito',
            'gift_redeem_days' => '🎁 Gift dias'
        ];
        $type = $type_names[$tx['type']] ?? $tx['type'];
        $msg .= "$emoji <code>" . date('d M H:i', strtotime($tx['time'])) . "</code>\n";
        $msg .= "   $type: $symbol\$" . number_format(abs($amount), 2) . "\n";
        $msg .= "   Saldo: \$" . number_format($tx['meta']['balance'] ?? 0, 2) . "\n\n";
    }
    send_message($chat_id, $msg);
}

function cmd_buy($chat_id) {
    $user = get_user($chat_id);
    $msg = "💳 <b>COMPRAR CRÉDITOS</b>\n\n";
    $msg .= "💰 Seu saldo: <b>$" . number_format($user['credits'], 2) . "</b>\n\n";
    $msg .= "📦 <b>PLANOS:</b>\n\n";
    $msg .= "🔹 1 Dispositivo: \$30.00 → 1 Serviço\n";
    $msg .= "🔹 7 Dias: \$350.00 → ilimitado\n";
    $msg .= "🔹 15 Dias: \$500.00 → ilimitado\n";
    $msg .= "🔹 30 Dias: \$650.00 → ilimitado\n\n";
    $msg .= "👉 <b>Contato:</b> https://t.me/segredoupdates\n";
    send_message($chat_id, $msg);
}

function cmd_orders($chat_id) {
    $orders = get_user_orders($chat_id, 10);
    if (empty($orders)) { 
        send_message($chat_id, "📦 Nenhum pedido até o momento"); 
        return; 
    }
    $msg = "📦 <b>SEUS PEDIDOS</b> (Últimos 10)\n\n";
    foreach ($orders as $o) {
        $status_icon = $o['status'] === 'success' ? '✅' : '❌';
        $msg .= "$status_icon <code>" . $o['serial'] . "</code>\n";
        $msg .= "   🎫 Pedido: <code>" . $o['order_id'] . "</code>\n";
        $msg .= "   💰 Custo: \$" . number_format($o['cost'], 2) . "\n";
        $msg .= "   📅 " . date('d M, H:i', strtotime($o['time'])) . "\n\n";
    }
    send_message($chat_id, $msg);
}

function cmd_mystats($chat_id) {
    $user = get_user($chat_id);
    $orders = get_user_orders($chat_id, 999);
    $successful = count(array_filter($orders, fn($o) => $o['status'] === 'success'));
    $failed = count($orders) - $successful;
    $success_rate = count($orders) > 0 ? ($successful / count($orders)) * 100 : 0;
    $msg = "📊 <b>SUAS ESTATÍSTICAS</b>\n\n";
    $msg .= "👤 User ID: <code>" . $chat_id . "</code>\n";
    $msg .= "📅 Membro desde: " . date('d M, Y', strtotime($user['registered'])) . "\n\n";
    $msg .= "<b>💰 SALDO:</b>\n";
    $msg .= "💳 Atual: <b>$" . number_format($user['credits'], 2) . "</b>\n";
    $msg .= "💸 Total gasto: \$" . number_format($user['total_spent'], 2) . "\n\n";
    $msg .= "<b>📦 PEDIDOS:</b>\n";
    $msg .= "📱 Total de pedidos: <b>" . count($orders) . "</b>\n";
    $msg .= "✅ Sucesso: " . $successful . "\n";
    $msg .= "❌ Falhos: " . $failed . "\n";
    $msg .= "📈 Taxa de sucesso: " . number_format($success_rate, 1) . "%\n\n";
    $msg .= "🕐 Última atividade: " . date('d M, H:i', strtotime($user['last_seen']));
    send_message($chat_id, $msg);
}

function cmd_addsn($chat_id, $serial) {
    $rate = check_rate_limit($chat_id, 'addsn', 10);
    if (!$rate['allowed']) {
        send_message($chat_id, "⏳ Aguarde {$rate['wait']} segundo(s) antes de processar outro pedido.");
        return;
    }

    $user = get_user($chat_id);

    if (empty($serial)) {
        $msg = "❌ <b>Por favor, informe o SN/Serial</b>\n\n";
        $msg .= "📝 Uso:\n";
        $msg .= "<code>/addsn F17VH123ABCD</code>\n\n";
        $msg .= "💡 Envie o IMEI ou número de série do dispositivo";
        send_message($chat_id, $msg);
        return;
    }

    $validation = validate_serial($serial);
    if (!$validation['valid']) {
        send_message($chat_id, $validation['msg']);
        return;
    }

    $serial = $validation['serial'];

    if (is_duplicate_order($chat_id, $serial, 5)) {
        send_message($chat_id, "⚠️ <b>Pedido duplicado detectado</b>\n\nVocê já processou este serial nos últimos 5 minutos.\n\nSe houver algum problema, aguarde alguns minutos e tente novamente.");
        return;
    }

    $plan = is_plan_active($chat_id);
    if ($plan['active']) {
        $processing = "⏳ <b>PROCESSANDO PEDIDO (PLANO ATIVO)...</b>\n\n";
        $processing .= "🔓 Serviço: SEGREDO A12+ Activation Lock Bypass\n";
        $processing .= "📱 IMEI/SN: <code>$serial</code>\n";
        $processing .= "💰 Custo: <b>GRÁTIS (plano ativo)</b>\n\n";
        $processing .= "🔄 Conectando à API SEGREDO A12+...";
        send_message($chat_id, $processing);

        $result = process_order($serial);

        if ($result['success']) {
            add_order($chat_id, $serial, $result['order_id'], 'success', 0.0);
            add_transaction($chat_id, 'order_success', 0.0, ['serial'=>$serial, 'order_id'=>$result['order_id'], 'plan'=>true]);

            $msg = "✅ <b>PEDIDO REALIZADO COM SUCESSO (PLANO)</b>\n\n";
            $msg .= "🔓 Serviço: SEGREDO A12+ Activation Lock Bypass\n";
            $msg .= "📱 IMEI/SN: <code>$serial</code>\n";
            $msg .= "🎫 ID do Pedido: <code>" . $result['order_id'] . "</code>\n";
            $msg .= "💰 Cobrado: <b>R$0.00 (plano ativo)</b>\n";
            $msg .= "✨ <b>Processo de Bypass realizado</b>\n";
            $msg .= "⚡ Status: INSTANTÂNEO - RESPOSTA AUTOMÁTICA\n\n";
            send_message($chat_id, $msg);
        } else {
            add_order($chat_id, $serial, 'FAILED', 'failed', 0.0);
            add_transaction($chat_id, 'order_failed', 0.0, ['serial'=>$serial, 'error'=>$result['msg'], 'plan'=>true]);

            $msg = "❌ <b>PEDIDO RECUSADO (PLANO - SEM COBRANÇA)</b>\n\n";
            $msg .= "📱 IMEI/SN: <code>$serial</code>\n";
            $msg .= "❌ Erro: " . $result['msg'] . "\n\n";
            $msg .= "💳 Saldo: <b>$" . number_format($user['credits'], 2) . "</b>\n\n";
            $msg .= "✅ <i>Nenhuma cobrança aplicada</i>";
            send_message($chat_id, $msg);
        }
        return;
    }

    if ($user['credits'] < SERVICE_COST) {
        $msg = "❌ <b>SALDO INSUFICIENTE</b>\n\n";
        $msg .= "💳 Seu saldo: \$" . number_format($user['credits'], 2) . "\n";
        $msg .= "💵 Necessário: \$" . number_format(SERVICE_COST, 2) . "\n";
        $msg .= "⚠️ Falta: \$" . number_format(SERVICE_COST - $user['credits'], 2) . "\n\n";
        $msg .= "💳 <b>Comprar créditos:</b>\n";
        $msg .= "👉 Contato: https://t.me/segredoupdates\n\n";
        $msg .= "Ou use o comando /buy para mais informações";
        send_message($chat_id, $msg);
        return;
    }

    $processing = "⏳ <b>PROCESSANDO PEDIDO...</b>\n\n";
    $processing .= "🔓 Serviço: SEGREDO A12+ Activation Lock Bypass\n";
    $processing .= "📱 IMEI/SN: <code>$serial</code>\n";
    $processing .= "💰 Custo: \$" . number_format(SERVICE_COST, 2) . "\n\n";
    $processing .= "🔄 Conectando à API SEGREDO A12+...";
    send_message($chat_id, $processing);

    $result = process_order($serial);

    if ($result['success']) {
        charge_credits($chat_id, SERVICE_COST, 'order_success', [
            'serial' => $serial,
            'order_id' => $result['order_id']
        ]);

        add_order($chat_id, $serial, $result['order_id'], 'success', SERVICE_COST);
        $user = get_user($chat_id);

        $msg = "✅ <b>PEDIDO REALIZADO COM SUCESSO</b>\n\n";
        $msg .= "🔓 Serviço: SEGREDO A12+ Activation Lock Bypass\n";
        $msg .= "📱 IMEI/SN: <code>$serial</code>\n";
        $msg .= "🎫 ID do Pedido: <code>" . $result['order_id'] . "</code>\n";
        $msg .= "💰 Cobrado: \$" . number_format(SERVICE_COST, 2) . "\n";
        $msg .= "💳 Novo saldo: <b>$" . number_format($user['credits'], 2) . "</b>\n\n";
        $msg .= "✨ <b>Processo de Bypass realizado</b>\n";
        $msg .= "⚡ Status: INSTANTÂNEO - RESPOSTA AUTOMÁTICA\n\n";
        send_message($chat_id, $msg);
    } else {
        if ($result['chargeable'] && SERVICE_COST > 0) {
            charge_credits($chat_id, SERVICE_COST, 'order_failed', [
                'serial' => $serial,
                'error' => $result['msg']
            ]);

            $user = get_user($chat_id);

            $msg = "⚠️ <b>PEDIDO RECUSADO (COBRADO)</b>\n\n";
            $msg .= "📱 IMEI/SN: <code>$serial</code>\n";
            $msg .= "❌ Erro: " . $result['msg'] . "\n\n";
            $msg .= "💰 Cobrado: \$" . number_format(SERVICE_COST, 2) . "\n";
            $msg .= "💳 Novo saldo: \$" . number_format($user['credits'], 2) . "\n\n";
            $msg .= "⚠️ <i>Foi cobrado porque o pedido foi enviado para a API</i>";
        } else {
            $user = get_user($chat_id);

            $msg = "❌ <b>PEDIDO RECUSADO (SEM COBRANÇA)</b>\n\n";
            $msg .= "📱 IMEI/SN: <code>$serial</code>\n";
            $msg .= "❌ Erro: " . $result['msg'] . "\n\n";
            $msg .= "💳 Saldo: <b>$" . number_format($user['credits'], 2) . "</b>\n\n";
            $msg .= "✅ <i>Nenhuma cobrança aplicada</i>";
        }
        send_message($chat_id, $msg);
    }
}

function cmd_resgatar($chat_id, $code) {
    $rate = check_rate_limit($chat_id, 'resgatar', 2);
    if (!$rate['allowed']) {
        send_message($chat_id, "⏳ Aguarde {$rate['wait']} segundo(s) antes de tentar resgatar novamente.");
        return;
    }

    $code = strtoupper(trim($code ?? ''));
    if ($code === '') {
        send_message($chat_id, "❌ Uso: /resgatar [CODIGO]");
        return;
    }

    $cooldown = check_gift_cooldown($chat_id);
    if (!$cooldown['allowed']) {
        $minutes = ceil($cooldown['remaining'] / 60);
        $msg = "⏳ <b>Aguarde para resgatar outro gift</b>\n\n";
        $msg .= "⚠️ Você só pode resgatar 1 gift a cada 30 minutos\n";
        $msg .= "⏰ Tempo restante: <b>{$minutes} minuto(s)</b>\n\n";
        $msg .= "💡 Isso evita abuso do sistema de gifts";
        send_message($chat_id, $msg);
        return;
    }

    $gifts = load_gifts();
    if (!isset($gifts[$code])) {
        send_message($chat_id, "❌ Código inválido.");
        return;
    }

    $gift = $gifts[$code];
    if ($gift['uses'] <= 0) {
        send_message($chat_id, "❌ Gift sem usos restantes.");
        return;
    }

    $user = get_user($chat_id);
    $username_display = $user['username'] ? '@' . $user['username'] : 'Nenhum';
    $name_display = $user['name'] ?? 'Desconhecido';

    if ($gift['mode'] === 'credit') {
        $value = floatval($gift['param']);
        $new = add_credits($chat_id, $value, null);
        $gift['uses'] -= 1;
        $gifts[$code] = $gift;
        save_gifts($gifts);
        add_transaction($chat_id, 'gift_redeem_credit', $value, ['code'=>$code, 'balance'=>$new]);
        update_gift_redeem_time($chat_id);
        
        bot_log("GIFT_REDEEM_CREDIT: {$code} by {$chat_id} value:{$value}");

        $resp = send_message($chat_id, "✅ Gift resgatado! Créditos adicionados: $" . number_format($value,2) . "\n💳 Novo saldo: $" . number_format($new,2));
        if (isset($resp['ok']) && $resp['ok'] && isset($resp['result']['message_id'])) {
            $pinResp = @pin_message($chat_id, $resp['result']['message_id']);
            if (isset($pinResp['ok']) && $pinResp['ok']) {
                $preview = mb_substr(trim(strip_tags($resp['result']['text'] ?? '')), 0, 200);
                send_message($chat_id, "📌 <b>Mensagem fixada</b>\n\n" . ($preview ? $preview : "Mensagem de confirmação fixada.") );
            }
        }

        if (defined('GIFT_NOTIFY_CHAT_ID') && GIFT_NOTIFY_CHAT_ID) {
            $notify_msg = "🎁 <b>Gift Resgatado</b>\n\n";
            $notify_msg .= "👤 Usuário: <b>" . htmlspecialchars($name_display, ENT_QUOTES) . "</b> (" . htmlspecialchars($username_display, ENT_QUOTES) . ")\n";
            $notify_msg .= "🆔 Chat ID: <code>" . intval($chat_id) . "</code>\n";
            $notify_msg .= "🎫 Código: <code>" . htmlspecialchars($code, ENT_QUOTES) . "</code>\n";
            $notify_msg .= "🏷️ Tipo: <b>Créditos</b>\n";
            $notify_msg .= "💵 Valor: <b>$" . number_format($value,2) . "</b>\n";
            $notify_msg .= "💳 Novo saldo: <b>$" . number_format($new,2) . "</b>\n";
            $notify_msg .= "⏱️ Hora: " . date('d/m H:i') . "\n";
            send_message(GIFT_NOTIFY_CHAT_ID, $notify_msg);
        }

        return;
    }

    if ($gift['mode'] === 'auto') {
        $param = $gift['param'];
        if (preg_match('/^(\d+)\s*d$/i', $param, $m)) {
            $days = intval($m[1]);
        } elseif (is_numeric($param)) {
            $days = intval($param);
        } else {
            $days = 1;
        }

        $rentals = load_rentals();
        $now = time();
        $id = strval($chat_id);
        if (isset($rentals[$id]) && !empty($rentals[$id]['expires'])) {
            $current_expires = strtotime($rentals[$id]['expires']);
            $start = ($current_expires > $now) ? $current_expires : $now;
        } else {
            $start = $now;
        }
        $expires = $start + ($days * 86400);

        $rentals[$id] = [
            'chat_id' => $chat_id,
            'days' => $days,
            'start' => date('Y-m-d H:i:s', $start),
            'expires' => date('Y-m-d H:i:s', $expires),
            'expired_notified' => false
        ];
        save_rentals($rentals);

        $gift['uses'] -= 1;
        $gifts[$code] = $gift;
        save_gifts($gifts);

        add_transaction($chat_id, 'gift_redeem_days', 0, ['code'=>$code, 'days'=>$days, 'expires'=>date('Y-m-d H:i:s', $expires)]);
        update_gift_redeem_time($chat_id);
        
        bot_log("GIFT_REDEEM_DAYS: {$code} by {$chat_id} days:{$days}");

        $resp = send_message($chat_id, "✅ Gift resgatado! Plano ativado por {$days} dia(s).\n📅 Expira em: " . date('d M Y H:i', $expires));
        if (isset($resp['ok']) && $resp['ok'] && isset($resp['result']['message_id'])) {
            $pinResp = @pin_message($chat_id, $resp['result']['message_id']);
            if (isset($pinResp['ok']) && $pinResp['ok']) {
                $preview = mb_substr(trim(strip_tags($resp['result']['text'] ?? '')), 0, 200);
                send_message($chat_id, "📌 <b>Mensagem fixada</b>\n\n" . ($preview ? $preview : "Mensagem de confirmação fixada.") );
            }
        }

        if (defined('GIFT_NOTIFY_CHAT_ID') && GIFT_NOTIFY_CHAT_ID) {
            $notify_msg = "🎁 <b>Gift Resgatado</b>\n\n";
            $notify_msg .= "👤 Usuário: <b>" . htmlspecialchars($name_display, ENT_QUOTES) . "</b> (" . htmlspecialchars($username_display, ENT_QUOTES) . ")\n";
            $notify_msg .= "🆔 Chat ID: <code>" . intval($chat_id) . "</code>\n";
            $notify_msg .= "🎫 Código: <code>" . htmlspecialchars($code, ENT_QUOTES) . "</code>\n";
            $notify_msg .= "🏷️ Tipo: <b>Plano</b>\n";
            $notify_msg .= "📅 Duração: <b>" . intval($days) . " dia(s)</b>\n";
            $notify_msg .= "📅 Expira em: <b>" . date('d M Y H:i', $expires) . "</b>\n";
            $notify_msg .= "⏱️ Hora: " . date('d/m H:i') . "\n";
            send_message(GIFT_NOTIFY_CHAT_ID, $notify_msg);
        }

        return;
    }

    send_message($chat_id, "❌ Gift com formato desconhecido.");
}

// ==================== COMANDOS ADMIN (continuação) ====================
<?php
/**
 * COMANDOS ADMIN E HANDLER PRINCIPAL
 * Sistema de broadcast melhorado com suporte a respostas
 */

// ==================== COMANDOS ADMIN ====================

function cmd_addcredits($chat_id, $target_id, $amount) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) { 
        send_message($chat_id, "❌ Apenas administradores podem usar esse comando"); 
        return; 
    }
    if (empty($target_id) || empty($amount)) {
        $msg = "❌ <b>Uso correto:</b>\n\n";
        $msg .= "<code>/addcredits [chat_id] [amount]</code>\n\n";
        $msg .= "Exemplo:\n";
        $msg .= "<code>/addcredits 123456789 50.00</code>";
        send_message($chat_id, $msg);
        return;
    }
    $amount = floatval($amount);
    if ($amount <= 0) { 
        send_message($chat_id, "❌ O valor deve ser maior que 0"); 
        return; 
    }
    $new_balance = add_credits($target_id, $amount, $chat_id);
    $msg = "✅ <b>CRÉDITOS ADICIONADOS</b>\n\n";
    $msg .= "👤 Usuário: <code>$target_id</code>\n";
    $msg .= "💵 Valor: +\$" . number_format($amount, 2) . "\n";
    $msg .= "💰 Novo saldo: \$" . number_format($new_balance, 2);
    send_message($chat_id, $msg);
    
    $user_msg = "💰 <b>CRÉDITOS RECEBIDOS</b>\n\n";
    $user_msg .= "💵 Valor: +\$" . number_format($amount, 2) . "\n";
    $user_msg .= "💳 Novo saldo: <b>\$" . number_format($new_balance, 2) . "</b>\n\n";
    $user_msg .= "✅ Créditos adicionados pelo admin";
    send_message($target_id, $user_msg);
}

function cmd_stats($chat_id) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) { 
        send_message($chat_id, "❌ Apenas administradores podem usar esse comando"); 
        return; 
    }
    $stats = get_stats();
    $msg = "📊 <b>ESTATÍSTICAS DO BOT</b>\n\n";
    $msg .= "👥 Total de usuários: <b>" . $stats['users'] . "</b>\n";
    $msg .= "💰 Créditos no sistema: \$" . number_format($stats['credits'], 2) . "\n";
    $msg .= "💸 Total gasto: \$" . number_format($stats['spent'], 2) . "\n";
    $msg .= "📦 Total de pedidos: <b>" . $stats['orders'] . "</b>\n";
    $msg .= "💵 Receita: \$" . number_format($stats['spent'], 2) . "\n\n";
    $msg .= "👑 Admins registrados: " . count(ADMIN_IDS);
    send_message($chat_id, $msg);
}

function cmd_users($chat_id) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) { 
        send_message($chat_id, "❌ Apenas administradores podem usar esse comando"); 
        return; 
    }
    $users = db_read(USERS_FILE, []);
    $users = array_slice($users, 0, 15);
    $msg = "👥 <b>USUÁRIOS</b> (Primeiros 15)\n\n";
    foreach ($users as $u) {
        $name = $u['name'] ?? 'Desconhecido';
        $admin_badge = $u['is_admin'] ? ' 👑' : '';
        $msg .= "$name$admin_badge\n";
        $msg .= "   ID: <code>" . $u['chat_id'] . "</code>\n";
        $msg .= "   💰 \$" . number_format($u['credits'], 2);
        $msg .= " | 📦 " . $u['total_orders'] . "\n\n";
    }
    send_message($chat_id, $msg);
}

function cmd_userinfo($chat_id, $target_id) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) { 
        send_message($chat_id, "❌ Apenas administradores podem usar esse comando"); 
        return; 
    }
    if (empty($target_id)) { 
        send_message($chat_id, "❌ Uso correto: <code>/userinfo [chat_id]</code>"); 
        return; 
    }
    $target = get_user($target_id);
    $orders = get_user_orders($target_id, 999);
    $successful = count(array_filter($orders, fn($o) => $o['status'] === 'success'));
    $failed = count($orders) - $successful;
    $msg = "👤 <b>INFORMAÇÕES DO USUÁRIO</b>\n\n";
    $msg .= "🆔 Chat ID: <code>" . $target_id . "</code>\n";
    $msg .= "👤 Nome: " . ($target['name'] ?? 'Desconhecido') . "\n";
    $msg .= "📱 Username: " . ($target['username'] ? '@' . $target['username'] : 'Nenhum') . "\n";
    $msg .= "👑 Admin: " . ($target['is_admin'] ? 'Sim' : 'Não') . "\n\n";
    $msg .= "<b>💰 SALDO:</b>\n";
    $msg .= "💳 Créditos: \$" . number_format($target['credits'], 2) . "\n";
    $msg .= "💸 Total gasto: \$" . number_format($target['total_spent'], 2) . "\n\n";
    $msg .= "<b>📦 PEDIDOS:</b>\n";
    $msg .= "📱 Total: " . count($orders) . "\n";
    $msg .= "✅ Sucesso: " . $successful . "\n";
    $msg .= "❌ Falha: " . $failed . "\n\n";
    $rentals = load_rentals();
    if (isset($rentals[$target_id]) && !empty($rentals[$target_id]['expires'])) {
        $exp = $rentals[$target_id]['expires'];
        $msg .= "<b>📅 Plano ativo até:</b> " . date('d M Y H:i', strtotime($exp)) . "\n\n";
    } else {
        $msg .= "<b>📅 Plano ativo até:</b> Nenhum\n\n";
    }
    $msg .= "📅 Registrado em: " . date('d M, Y', strtotime($target['registered'])) . "\n";
    $msg .= "🕐 Último acesso: " . date('d M, H:i', strtotime($target['last_seen']));
    send_message($chat_id, $msg);
}

// ==================== BROADCAST COMMANDS ====================

function cmd_broadcast_text($chat_id, $full_text) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) { 
        send_message($chat_id, "❌ Apenas administradores podem usar este comando."); 
        return; 
    }
    
    if (is_broadcast_running()) {
        $lock_info = get_broadcast_lock_info();
        $msg = "⚠️ <b>JÁ HÁ BROADCAST EM ANDAMENTO</b>\n\n";
        
        if ($lock_info) {
            $msg .= "👤 Iniciado por: <code>" . $lock_info['admin_id'] . "</code>\n";
            $msg .= "📢 Tipo: " . $lock_info['type'] . "\n";
            $msg .= "⏱️ Tempo: " . $lock_info['elapsed_formatted'] . "\n\n";
        }
        
        $msg .= "⏳ Aguarde a conclusão ou use:\n";
        $msg .= "• /broadcast_status - Ver status\n";
        $msg .= "• /broadcast_cancel - Cancelar broadcast";
        
        send_message($chat_id, $msg);
        bot_log("BROADCAST_BLOCKED: Admin {$chat_id} tentou broadcast com outro em andamento");
        return;
    }
    
    $parts = preg_split('/\s+/', trim($full_text));
    array_shift($parts);
    $message = trim(implode(' ', $parts));
    
    if ($message === '') {
        send_message($chat_id, "❌ Uso incorreto.\n\nExemplo:\n<code>/broadcast Promoção especial!</code>");
        return;
    }
    
    $initial_msg = "📢 <b>BROADCAST INICIADO</b>\n\n";
    $initial_msg .= "📊 Preparando envio para todos os usuários...\n";
    $initial_msg .= "💬 Tipo: Texto\n\n";
    $initial_msg .= "⏳ Aguarde...";
    
    $init_resp = send_message($chat_id, $initial_msg);
    $status_msg_id = $init_resp['result']['message_id'] ?? null;
    
    $content_data = ['text' => $message];
    execute_broadcast($chat_id, 'text', $content_data, $status_msg_id);
}

function cmd_broadcast_reply($chat_id, $replied_message) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) { 
        return; 
    }
    
    if (is_broadcast_running()) {
        $msg = "⚠️ <b>JÁ HÁ BROADCAST EM ANDAMENTO</b>\n\n";
        $msg .= "⏳ Aguarde ou use /broadcast_cancel";
        send_message($chat_id, $msg);
        return;
    }
    
    // Detectar tipo de conteúdo
    $content_type = null;
    $content_data = [];
    
    if (isset($replied_message['text'])) {
        $content_type = 'text';
        $content_data = ['text' => $replied_message['text']];
        
    } elseif (isset($replied_message['photo'])) {
        $content_type = 'photo';
        $photos = $replied_message['photo'];
        $photo = end($photos); // Pegar a maior resolução
        $content_data = [
            'photo' => $photo['file_id'],
            'caption' => $replied_message['caption'] ?? null
        ];
        
    } elseif (isset($replied_message['video'])) {
        $content_type = 'video';
        $content_data = [
            'video' => $replied_message['video']['file_id'],
            'caption' => $replied_message['caption'] ?? null
        ];
        
    } elseif (isset($replied_message['audio'])) {
        $content_type = 'audio';
        $content_data = [
            'audio' => $replied_message['audio']['file_id'],
            'caption' => $replied_message['caption'] ?? null
        ];
        
    } elseif (isset($replied_message['voice'])) {
        $content_type = 'voice';
        $content_data = [
            'voice' => $replied_message['voice']['file_id'],
            'caption' => $replied_message['caption'] ?? null
        ];
        
    } elseif (isset($replied_message['document'])) {
        $content_type = 'document';
        $content_data = [
            'document' => $replied_message['document']['file_id'],
            'caption' => $replied_message['caption'] ?? null
        ];
    }
    
    if (!$content_type) {
        $msg = "❌ <b>TIPO DE MÍDIA NÃO SUPORTADO</b>\n\n";
        $msg .= "📢 Tipos suportados:\n";
        $msg .= "• Texto\n";
        $msg .= "• Foto\n";
        $msg .= "• Vídeo\n";
        $msg .= "• Áudio\n";
        $msg .= "• Voz\n";
        $msg .= "• Documento";
        send_message($chat_id, $msg);
        return;
    }
    
    $type_names = [
        'text' => '📝 Texto',
        'photo' => '📷 Foto',
        'video' => '🎥 Vídeo',
        'audio' => '🎵 Áudio',
        'voice' => '🎤 Mensagem de voz',
        'document' => '📄 Documento'
    ];
    
    $type_display = $type_names[$content_type] ?? $content_type;
    
    $initial_msg = "📢 <b>BROADCAST INICIADO POR RESPOSTA</b>\n\n";
    $initial_msg .= "📊 Preparando envio para todos os usuários...\n";
    $initial_msg .= "💬 Tipo: {$type_display}\n\n";
    $initial_msg .= "⏳ Aguarde...";
    
    $init_resp = send_message($chat_id, $initial_msg);
    $status_msg_id = $init_resp['result']['message_id'] ?? null;
    
    bot_log("BROADCAST_REPLY: Admin {$chat_id} iniciando broadcast tipo {$content_type}");
    
    execute_broadcast($chat_id, $content_type, $content_data, $status_msg_id);
}

function cmd_broadcast_status($chat_id) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) {
        send_message($chat_id, "❌ Apenas administradores podem usar este comando.");
        return;
    }
    
    if (!is_broadcast_running()) {
        send_message($chat_id, "✅ <b>Status: Nenhum broadcast em andamento</b>");
        return;
    }
    
    $lock_info = get_broadcast_lock_info();
    
    if (!$lock_info) {
        send_message($chat_id, "⚠️ Lock detectado mas sem informações");
        return;
    }
    
    $msg = "📊 <b>BROADCAST EM ANDAMENTO</b>\n\n";
    $msg .= "🆔 ID: <code>" . ($lock_info['broadcast_id'] ?? 'N/A') . "</code>\n";
    $msg .= "👤 Admin: <code>" . $lock_info['admin_id'] . "</code>\n";
    $msg .= "📢 Tipo: " . $lock_info['type'] . "\n";
    $msg .= "⏱️ Tempo decorrido: " . $lock_info['elapsed_formatted'] . "\n";
    $msg .= "🔢 PID: " . $lock_info['pid'] . "\n\n";
    $msg .= "💡 Use /broadcast_cancel para forçar cancelamento";
    
    send_message($chat_id, $msg);
}

function cmd_broadcast_cancel($chat_id) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) {
        send_message($chat_id, "❌ Apenas administradores podem usar este comando.");
        return;
    }
    
    if (!is_broadcast_running()) {
        send_message($chat_id, "ℹ️ Nenhum broadcast em andamento.");
        return;
    }
    
    $lock_info = get_broadcast_lock_info();
    remove_broadcast_lock();
    
    $msg = "✅ <b>Broadcast Cancelado</b>\n\n";
    if ($lock_info) {
        $msg .= "🆔 ID: <code>" . ($lock_info['broadcast_id'] ?? 'N/A') . "</code>\n";
        $msg .= "👤 Admin: <code>" . $lock_info['admin_id'] . "</code>\n";
        $msg .= "📢 Tipo: " . $lock_info['type'] . "\n";
        $msg .= "⏱️ Duração: " . $lock_info['elapsed_formatted'] . "\n";
    }
    $msg .= "\n⚠️ Lock removido manualmente";
    
    send_message($chat_id, $msg);
    bot_log("BROADCAST_CANCEL: Forçado por admin {$chat_id}");
}

// ==================== GIFT COMMANDS ====================

function cmd_criar_gift($chat_id, $code, $mode, $param, $uses = 1) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) {
        send_message($chat_id, "❌ Apenas administradores podem usar este comando.");
        return;
    }
    $code = trim(strtoupper($code));
    if ($code === '') { 
        send_message($chat_id, "❌ Código inválido."); 
        return; 
    }

    $gifts = load_gifts();
    $entry = [
        'code' => $code,
        'mode' => $mode,
        'param' => $param,
        'uses' => intval($uses),
        'created_at' => date('Y-m-d H:i:s')
    ];
    $gifts[$code] = $entry;
    save_gifts($gifts);

    bot_log("GIFT_CREATED: {$code} mode:{$mode} param:{$param} uses:{$uses} by admin:{$chat_id}");
    send_message($chat_id, "✅ Gift criado: <code>$code</code> | modo: $mode | param: $param | uses: ".$entry['uses']);
}

function cmd_criar_gifts($chat_id, $quantidade, $mode, $param, $uses = 1) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) {
        send_message($chat_id, "❌ Apenas administradores podem usar este comando.");
        return;
    }
    
    $quantidade = intval($quantidade);
    if ($quantidade <= 0 || $quantidade > 100) {
        send_message($chat_id, "❌ Quantidade deve ser entre 1 e 100");
        return;
    }
    
    if (empty($mode) || empty($param)) {
        $msg = "❌ <b>Uso correto:</b>\n\n";
        $msg .= "<code>/criar_gifts [quantidade] [mode] [param] [uses]</code>\n\n";
        $msg .= "<b>Exemplos:</b>\n";
        $msg .= "• <code>/criar_gifts 10 credit 50.00 1</code>\n";
        $msg .= "  → Cria 10 gifts de $50 com 1 uso cada\n\n";
        $msg .= "• <code>/criar_gifts 5 auto 7d 1</code>\n";
        $msg .= "  → Cria 5 gifts de 7 dias com 1 uso cada\n\n";
        $msg .= "<b>Modos:</b>\n";
        $msg .= "• credit = Adiciona créditos\n";
        $msg .= "• auto = Adiciona dias de plano (ex: 7d, 15d, 30d)";
        send_message($chat_id, $msg);
        return;
    }
    
    $uses = intval($uses) > 0 ? intval($uses) : 1;
    
    $gifts = load_gifts();
    $created = [];
    $failed = 0;
    
    send_message($chat_id, "⏳ Criando {$quantidade} gifts...\n\nAguarde...");
    
    for ($i = 0; $i < $quantidade; $i++) {
        $code = generate_gift_code();
        
        $attempts = 0;
        while (isset($gifts[$code]) && $attempts < 10) {
            $code = generate_gift_code();
            $attempts++;
        }
        
        if ($attempts >= 10) {
            $failed++;
            continue;
        }
        
        $entry = [
            'code' => $code,
            'mode' => $mode,
            'param' => $param,
            'uses' => $uses,
            'created_at' => date('Y-m-d H:i:s'),
            'batch' => true
        ];
        
        $gifts[$code] = $entry;
        $created[] = $code;
    }
    
    save_gifts($gifts);
    bot_log("BATCH_GIFTS_CREATED: {$quantidade} gifts | mode:{$mode} param:{$param} uses:{$uses} by admin:{$chat_id}");
    
    $msg = "✅ <b>GIFTS CRIADOS COM SUCESSO</b>\n\n";
    $msg .= "📦 Quantidade: <b>" . count($created) . "</b>\n";
    if ($failed > 0) {
        $msg .= "⚠️ Falhas: {$failed}\n";
    }
    $msg .= "🎁 Modo: <b>{$mode}</b>\n";
    $msg .= "💰 Valor: <b>{$param}</b>\n";
    $msg .= "🔢 Usos por gift: <b>{$uses}</b>\n\n";
    
    $display_limit = min(20, count($created));
    $msg .= "<b>📋 Códigos gerados:</b>\n\n";
    
    for ($i = 0; $i < $display_limit; $i++) {
        $msg .= "<code>" . $created[$i] . "</code>\n";
    }
    
    if (count($created) > $display_limit) {
        $msg .= "\n<i>... e mais " . (count($created) - $display_limit) . " códigos</i>\n";
    }
    
    $msg .= "\n💡 Use /gifts_list para ver todos os gifts";
    
    send_message($chat_id, $msg);
    
    if (count($created) > 20) {
        $file_content = "GIFTS CRIADOS - " . date('Y-m-d H:i:s') . "\n";
        $file_content .= "Modo: {$mode} | Param: {$param} | Uses: {$uses}\n";
        $file_content .= "Total: " . count($created) . "\n\n";
        $file_content .= implode("\n", $created);
        
        $filename = DATA_DIR . '/gifts_batch_' . time() . '.txt';
        file_put_contents($filename, $file_content);
        
        send_message($chat_id, "📄 Arquivo com todos os códigos salvo em:\n<code>$filename</code>");
    }
}

function cmd_gifts_list($chat_id) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) { 
        send_message($chat_id, "❌ Apenas administradores."); 
        return; 
    }
    $gifts = load_gifts();
    if (empty($gifts)) { 
        send_message($chat_id, "⚠️ Nenhum gift ativo."); 
        return; 
    }
    
    uasort($gifts, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    $total = count($gifts);
    $msg = "🎁 <b>Gifts ativos: {$total}</b>\n\n";
    
    $count = 0;
    foreach ($gifts as $g) {
        if ($count >= 30) break;
        
        $batch_icon = !empty($g['batch']) ? '📦' : '✏️';
        $msg .= "{$batch_icon} <code>".$g['code']."</code>\n";
        $msg .= "   └ ".$g['mode']." | ".$g['param']." | uses: ".$g['uses']."\n";
        $count++;
    }
    
    if ($total > 30) {
        $msg .= "\n<i>... e mais " . ($total - 30) . " gifts</i>";
    }
    
    send_message($chat_id, $msg);
}

function cmd_gifts_stats($chat_id) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) {
        send_message($chat_id, "❌ Apenas administradores.");
        return;
    }
    
    $gifts = load_gifts();
    $total = count($gifts);
    $credit_gifts = 0;
    $plan_gifts = 0;
    $total_value = 0;
    $total_uses = 0;
    $batch_gifts = 0;
    
    foreach ($gifts as $g) {
        if ($g['mode'] === 'credit') {
            $credit_gifts++;
            $total_value += floatval($g['param']) * $g['uses'];
        } else {
            $plan_gifts++;
        }
        $total_uses += $g['uses'];
        if (!empty($g['batch'])) {
            $batch_gifts++;
        }
    }
    
    $msg = "📊 <b>ESTATÍSTICAS DE GIFTS</b>\n\n";
    $msg .= "🎁 Total de gifts: <b>{$total}</b>\n";
    $msg .= "💵 Gifts de crédito: {$credit_gifts}\n";
    $msg .= "📅 Gifts de plano: {$plan_gifts}\n";
    $msg .= "📦 Criados em lote: {$batch_gifts}\n";
    $msg .= "🔢 Total de usos disponíveis: {$total_uses}\n";
    $msg .= "💰 Valor total em créditos: $" . number_format($total_value, 2);
    
    send_message($chat_id, $msg);
}

function cmd_remover_gift($chat_id, $code) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) { 
        send_message($chat_id, "❌ Apenas administradores podem usar esse comando"); 
        return; 
    }
    $code = strtoupper(trim($code));
    $gifts = load_gifts();
    if (!isset($gifts[$code])) { 
        send_message($chat_id, "❌ Gift não encontrado."); 
        return; 
    }
    unset($gifts[$code]);
    save_gifts($gifts);
    bot_log("GIFT_REMOVED: {$code} by admin:{$chat_id}");
    send_message($chat_id, "✅ Gift removido: <code>$code</code>");
}

function cmd_remover_plano($chat_id, $target_id) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) {
        send_message($chat_id, "❌ Apenas administradores podem usar este comando.");
        return;
    }
    if (empty($target_id)) {
        send_message($chat_id, "Uso correto:\n/removerplano [chat_id]");
        return;
    }
    if (remover_plano($target_id)) {
        send_message($chat_id, "✅ Plano removido do usuário $target_id");
        send_message($target_id, "⚠️ Seu plano foi removido pelo administrador.");
    } else {
        send_message($chat_id, "❌ Esse usuário não possui plano ativo.");
    }
}

function cmd_backup($chat_id) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) {
        send_message($chat_id, "❌ Apenas administradores podem usar este comando.");
        return;
    }
    
    send_message($chat_id, "⏳ Iniciando backup manual...");
    
    $count = auto_backup();
    
    if ($count > 0) {
        $msg = "✅ <b>BACKUP CONCLUÍDO</b>\n\n";
        $msg .= "📦 Arquivos salvos: <b>{$count}</b>\n";
        $msg .= "📁 Localização: <code>" . BACKUP_DIR . "</code>\n";
        $msg .= "⏱️ Data/Hora: " . date('d/m/Y H:i:s') . "\n\n";
        $msg .= "💡 Backups automáticos ocorrem a cada 6 horas";
        send_message($chat_id, $msg);
    } else {
        send_message($chat_id, "❌ Falha ao realizar backup.");
    }
}

// ==================== MAIN HANDLER ====================

// Limpar broadcasts antigos
cleanup_old_broadcasts();

$raw = file_get_contents('php://input');
@file_put_contents(LOG_UPDATES, date('Y-m-d H:i:s') . " RAW_UPDATE:\n" . $raw . "\n\n", FILE_APPEND | LOCK_EX);

$update = json_decode($raw, true);
@file_put_contents(LOG_HANDLER, date('Y-m-d H:i:s') . " HANDLER_ENTER:\n" . var_export($update, true) . "\n\n", FILE_APPEND | LOCK_EX);

$chat_id = $update['message']['chat']['id'] ?? null;
$text = $update['message']['text'] ?? '';
$username = $update['message']['from']['username'] ?? null;
$name = $update['message']['from']['first_name'] ?? null;
$reply_to_message = $update['message']['reply_to_message'] ?? null;

if ($chat_id) {
    check_auto_backup();
    
    get_user($chat_id);
    update_user($chat_id, [
        'username' => $username,
        'name' => $name,
        'last_seen' => date('Y-m-d H:i:s')
    ]);
    
    @check_plan_expiry_notify($chat_id);

    // DETECTAR BROADCAST POR RESPOSTA
    if ($reply_to_message && !empty($text) && strpos($text, '/') !== 0) {
        $user = get_user($chat_id);
        
        // Verificar se a mensagem respondida é do próprio bot
        $is_bot_message = isset($reply_to_message['from']['is_bot']) && $reply_to_message['from']['is_bot'];
        
        // Verificar se é mensagem de broadcast concluído
        $is_broadcast_complete = isset($reply_to_message['text']) && 
                                  (strpos($reply_to_message['text'], 'BROADCAST CONCLUÍDO') !== false ||
                                   strpos($reply_to_message['text'], 'BROADCAST EM ANDAMENTO') !== false ||
                                   strpos($reply_to_message['text'], 'BROADCAST CANCELADO') !== false);
        
        // Só processar se for admin E não for resposta a mensagem do bot E não for mensagem de status
        if ($user['is_admin'] && !$is_bot_message && !$is_broadcast_complete) {
            bot_log("BROADCAST_REPLY_DETECTED: Admin {$chat_id} respondendo mensagem");
            cmd_broadcast_reply($chat_id, $reply_to_message);
            http_response_code(200);
            exit;
        } elseif ($user['is_admin'] && ($is_bot_message || $is_broadcast_complete)) {
            bot_log("BROADCAST_REPLY_BLOCKED: Admin tentou responder mensagem do bot (loop prevention)");
        }
    }

    $parts = preg_split('/\s+/', trim($text));
    $cmd = strtolower($parts[0] ?? '');
    $arg1 = $parts[1] ?? null;
    $arg2 = $parts[2] ?? null;
    $arg3 = $parts[3] ?? null;
    $arg4 = $parts[4] ?? null;

    @file_put_contents(LOG_HANDLER, date('Y-m-d H:i:s') . " DETECTED_CMD: {$cmd} ARGS: " . json_encode([$arg1,$arg2,$arg3,$arg4]) . "\n\n", FILE_APPEND | LOCK_EX);

    switch ($cmd) {
        case '/start':
        case '/help':
            cmd_start($chat_id, $name);
            break;
        case '/balance':
            cmd_balance($chat_id);
            break;
        case '/buy':
            cmd_buy($chat_id);
            break;
        case '/addsn':
            cmd_addsn($chat_id, $arg1);
            break;
        case '/orders':
            cmd_orders($chat_id);
            break;
        case '/mystats':
            cmd_mystats($chat_id);
            break;
        case '/history':
            cmd_history($chat_id);
            break;
        case '/resgatar':
            cmd_resgatar($chat_id, $arg1);
            break;
        case '/addcredits':
            cmd_addcredits($chat_id, $arg1, $arg2);
            break;
        case '/stats':
            cmd_stats($chat_id);
            break;
        case '/users':
            cmd_users($chat_id);
            break;
        case '/userinfo':
            cmd_userinfo($chat_id, $arg1);
            break;
        case '/broadcast':
            cmd_broadcast_text($chat_id, $text);
            break;
        case '/broadcast_status':
            cmd_broadcast_status($chat_id);
            break;
        case '/broadcast_cancel':
            cmd_broadcast_cancel($chat_id);
            break;
        case '/criar_gift':
            cmd_criar_gift($chat_id, $arg1 ?? '', $arg2 ?? '', $arg3 ?? '', $arg4 ?? 1);
            break;
        case '/criar_gifts':
            cmd_criar_gifts($chat_id, $arg1 ?? '', $arg2 ?? '', $arg3 ?? '', $arg4 ?? 1);
            break;
        case '/gifts_list':
            cmd_gifts_list($chat_id);
            break;
        case '/gifts_stats':
            cmd_gifts_stats($chat_id);
            break;
        case '/removerplano':
            cmd_remover_plano($chat_id, $arg1);
            break;
        case '/remover_gift':
            cmd_remover_gift($chat_id, $arg1);
            break;
        case '/backup':
            cmd_backup($chat_id);
            break;
        default:
            if (strpos($text, '/') === 0) {
                send_message($chat_id, "❌ Comando desconhecido\n\nUse /help para ver a lista de comandos disponíveis");
            }
    }
}

http_response_code(200);
?>

// ==================== FUNÇÕES DE MENU ====================

function create_main_menu($is_admin = false) {
    $keyboard = [
        [
            ['text' => '💰 Ver Saldo', 'callback_data' => 'menu_balance'],
            ['text' => '📦 Meus Pedidos', 'callback_data' => 'menu_orders']
        ],
        [
            ['text' => '💳 Comprar Créditos', 'callback_data' => 'menu_buy'],
            ['text' => '📊 Minhas Stats', 'callback_data' => 'menu_mystats']
        ],
        [
            ['text' => '🎁 Resgatar Gift', 'callback_data' => 'menu_gift'],
            ['text' => '📜 Histórico', 'callback_data' => 'menu_history']
        ]
    ];
    
    if ($is_admin) {
        $keyboard[] = [
            ['text' => '👑 MENU ADMIN', 'callback_data' => 'menu_admin']
        ];
    }
    
    $keyboard[] = [
        ['text' => '❓ Ajuda', 'callback_data' => 'menu_help'],
        ['text' => '🔄 Atualizar', 'callback_data' => 'menu_refresh']
    ];
    
    return ['inline_keyboard' => $keyboard];
}

function create_admin_menu() {
    return [
        'inline_keyboard' => [
            [
                ['text' => '📢 Broadcast', 'callback_data' => 'admin_broadcast_menu'],
                ['text' => '📊 Estatísticas', 'callback_data' => 'admin_stats']
            ],
            [
                ['text' => '👥 Usuários', 'callback_data' => 'admin_users'],
                ['text' => '🎁 Gifts', 'callback_data' => 'admin_gifts_menu']
            ],
            [
                ['text' => '💳 Adicionar Créditos', 'callback_data' => 'admin_add_credits'],
                ['text' => '💾 Backup', 'callback_data' => 'admin_backup']
            ],
            [
                ['text' => '⬅️ Voltar ao Menu', 'callback_data' => 'menu_main']
            ]
        ]
    ];
}

function create_broadcast_menu() {
    return [
        'inline_keyboard' => [
            [
                ['text' => '📝 Broadcast de Texto', 'callback_data' => 'bc_text']
            ],
            [
                ['text' => '📷 Broadcast de Foto', 'callback_data' => 'bc_photo'],
                ['text' => '🎥 Broadcast de Vídeo', 'callback_data' => 'bc_video']
            ],
            [
                ['text' => '🎵 Broadcast de Áudio', 'callback_data' => 'bc_audio'],
                ['text' => '📄 Broadcast de Documento', 'callback_data' => 'bc_document']
            ],
            [
                ['text' => '📊 Ver Status', 'callback_data' => 'bc_status'],
                ['text' => '🛑 Cancelar Ativo', 'callback_data' => 'bc_cancel']
            ],
            [
                ['text' => '⬅️ Voltar', 'callback_data' => 'menu_admin']
            ]
        ]
    ];
}

function create_gifts_menu() {
    return [
        'inline_keyboard' => [
            [
                ['text' => '➕ Criar Gift', 'callback_data' => 'gift_create'],
                ['text' => '📦 Criar Lote', 'callback_data' => 'gift_batch']
            ],
            [
                ['text' => '📋 Lista de Gifts', 'callback_data' => 'gift_list'],
                ['text' => '📊 Estatísticas', 'callback_data' => 'gift_stats']
            ],
            [
                ['text' => '🗑️ Remover Gift', 'callback_data' => 'gift_remove']
            ],
            [
                ['text' => '⬅️ Voltar', 'callback_data' => 'menu_admin']
            ]
        ]
    ];
}

function create_confirmation_menu($action, $data = []) {
    return [
        'inline_keyboard' => [
            [
                ['text' => '✅ Confirmar', 'callback_data' => "confirm_{$action}"],
                ['text' => '❌ Cancelar', 'callback_data' => 'cancel_action']
            ]
        ]
    ];
}

// ==================== HANDLER DE CALLBACKS ====================

function handle_callback_query($callback) {
    $chat_id = $callback['message']['chat']['id'];
    $message_id = $callback['message']['message_id'];
    $data = $callback['data'];
    $callback_id = $callback['id'];
    
    // Responder ao callback para remover loading
    answer_callback_query($callback_id);
    
    $user = get_user($chat_id);
    
    // Registrar callback
    bot_log("CALLBACK: {$chat_id} -> {$data}");
    
    // Processar callbacks
    switch ($data) {
        // Menu Principal
        case 'menu_main':
            show_main_menu($chat_id, $message_id);
            break;
            
        case 'menu_balance':
            show_balance($chat_id, $message_id);
            break;
            
        case 'menu_orders':
            show_orders($chat_id, $message_id);
            break;
            
        case 'menu_buy':
            show_buy($chat_id, $message_id);
            break;
            
        case 'menu_mystats':
            show_mystats($chat_id, $message_id);
            break;
            
        case 'menu_gift':
            show_gift_input($chat_id, $message_id);
            break;
            
        case 'menu_history':
            show_history($chat_id, $message_id);
            break;
            
        case 'menu_help':
            show_help($chat_id, $message_id);
            break;
            
        case 'menu_refresh':
            show_main_menu($chat_id, $message_id);
            break;
            
        // Menu Admin
        case 'menu_admin':
            if ($user['is_admin']) {
                show_admin_menu($chat_id, $message_id);
            } else {
                answer_callback_query($callback_id, '❌ Acesso negado', true);
            }
            break;
            
        case 'admin_broadcast_menu':
            if ($user['is_admin']) {
                show_broadcast_menu($chat_id, $message_id);
            }
            break;
            
        case 'admin_stats':
            if ($user['is_admin']) {
                show_admin_stats($chat_id, $message_id);
            }
            break;
            
        case 'admin_users':
            if ($user['is_admin']) {
                show_admin_users($chat_id, $message_id);
            }
            break;
            
        case 'admin_gifts_menu':
            if ($user['is_admin']) {
                show_gifts_menu($chat_id, $message_id);
            }
            break;
            
        case 'admin_backup':
            if ($user['is_admin']) {
                do_backup($chat_id, $message_id);
            }
            break;
            
        // Broadcast
        case 'bc_text':
            if ($user['is_admin']) {
                show_broadcast_text_input($chat_id, $message_id);
            }
            break;
            
        case 'bc_photo':
            if ($user['is_admin']) {
                show_broadcast_photo_input($chat_id, $message_id);
            }
            break;
            
        case 'bc_video':
            if ($user['is_admin']) {
                show_broadcast_video_input($chat_id, $message_id);
            }
            break;
            
        case 'bc_status':
            if ($user['is_admin']) {
                show_broadcast_status($chat_id, $message_id);
            }
            break;
            
        case 'bc_cancel':
            if ($user['is_admin']) {
                cancel_broadcast_interactive($chat_id, $message_id, $callback_id);
            }
            break;
            
        // Gifts Admin
        case 'gift_list':
            if ($user['is_admin']) {
                show_gifts_list($chat_id, $message_id);
            }
            break;
            
        case 'gift_stats':
            if ($user['is_admin']) {
                show_gifts_stats($chat_id, $message_id);
            }
            break;
            
        default:
            // Callback desconhecido
            answer_callback_query($callback_id, '⚠️ Ação não reconhecida', true);
            break;
    }
}

function answer_callback_query($callback_id, $text = null, $show_alert = false) {
    $data = ['callback_query_id' => $callback_id];
    
    if ($text) {
        $data['text'] = $text;
        $data['show_alert'] = $show_alert;
    }
    
    return telegram_api_request('answerCallbackQuery', $data);
}

// ==================== SHOW FUNCTIONS ====================

function show_main_menu($chat_id, $message_id = null) {
    $user = get_user($chat_id);
    $safe_name = htmlspecialchars($user['name'] ?? 'Usuário', ENT_QUOTES);
    
    $msg = "🏠 <b>MENU PRINCIPAL</b>\n\n";
    $msg .= "👤 Olá, <b>{$safe_name}</b>!\n";
    $msg .= "💳 Saldo: <b>\$" . number_format($user['credits'], 2) . "</b>\n";
    $msg .= "📦 Pedidos: <b>" . $user['total_orders'] . "</b>\n\n";
    
    // Verificar plano
    $rentals = load_rentals();
    $plan_id = strval($chat_id);
    if (isset($rentals[$plan_id]) && !empty($rentals[$plan_id]['expires'])) {
        $exp = strtotime($rentals[$plan_id]['expires']);
        if ($exp > time()) {
            $days_left = floor(($exp - time()) / 86400);
            $msg .= "✨ <b>Plano ativo!</b> Expira em {$days_left}d\n\n";
        }
    }
    
    $msg .= "Selecione uma opção abaixo:";
    
    $keyboard = create_main_menu($user['is_admin']);
    
    if ($message_id) {
        edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
    } else {
        send_message_with_keyboard($chat_id, $msg, $keyboard);
    }
}

function show_balance($chat_id, $message_id) {
    $user = get_user($chat_id);
    
    $msg = "💰 <b>SEU SALDO</b>\n\n";
    $msg .= "💳 Disponível: <b>\$" . number_format($user['credits'], 2) . "</b>\n";
    $msg .= "💸 Total gasto: \$" . number_format($user['total_spent'], 2) . "\n";
    $msg .= "📦 Total de pedidos: <b>" . $user['total_orders'] . "</b>\n\n";
    $msg .= "💡 Custo por serviço: \$" . number_format(SERVICE_COST, 2);
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '💳 Comprar Créditos', 'callback_data' => 'menu_buy']
            ],
            [
                ['text' => '⬅️ Voltar ao Menu', 'callback_data' => 'menu_main']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_orders($chat_id, $message_id) {
    $orders = get_user_orders($chat_id, 10);
    
    if (empty($orders)) {
        $msg = "📦 <b>SEUS PEDIDOS</b>\n\n";
        $msg .= "Você ainda não fez nenhum pedido.\n\n";
        $msg .= "Use /addsn [SERIAL] para fazer seu primeiro unlock!";
    } else {
        $msg = "📦 <b>SEUS PEDIDOS</b> (Últimos 10)\n\n";
        
        foreach ($orders as $o) {
            $status_icon = $o['status'] === 'success' ? '✅' : '❌';
            $msg .= "$status_icon <code>" . $o['serial'] . "</code>\n";
            $msg .= "   🎫 <code>" . $o['order_id'] . "</code>\n";
            $msg .= "   💰 \$" . number_format($o['cost'], 2) . "\n";
            $msg .= "   📅 " . date('d M, H:i', strtotime($o['time'])) . "\n\n";
        }
    }
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📊 Ver Estatísticas', 'callback_data' => 'menu_mystats']
            ],
            [
                ['text' => '⬅️ Voltar ao Menu', 'callback_data' => 'menu_main']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_buy($chat_id, $message_id) {
    $user = get_user($chat_id);
    
    $msg = "💳 <b>COMPRAR CRÉDITOS</b>\n\n";
    $msg .= "💰 Seu saldo atual: <b>\$" . number_format($user['credits'], 2) . "</b>\n\n";
    $msg .= "📦 <b>PLANOS DISPONÍVEIS:</b>\n\n";
    $msg .= "🔹 1 Dispositivo: \$30.00\n";
    $msg .= "   → 1 Serviço de unlock\n\n";
    $msg .= "🔹 7 Dias: \$350.00\n";
    $msg .= "   → Serviços ilimitados por 7 dias\n\n";
    $msg .= "🔹 15 Dias: \$500.00\n";
    $msg .= "   → Serviços ilimitados por 15 dias\n\n";
    $msg .= "🔹 30 Dias: \$650.00\n";
    $msg .= "   → Serviços ilimitados por 30 dias\n\n";
    $msg .= "💬 <b>Para comprar, entre em contato:</b>\n";
    $msg .= "👉 https://t.me/segredoupdates";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📱 Contatar Suporte', 'url' => 'https://t.me/segredoupdates']
            ],
            [
                ['text' => '⬅️ Voltar ao Menu', 'callback_data' => 'menu_main']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_mystats($chat_id, $message_id) {
    $user = get_user($chat_id);
    $orders = get_user_orders($chat_id, 999);
    $successful = count(array_filter($orders, fn($o) => $o['status'] === 'success'));
    $failed = count($orders) - $successful;
    $success_rate = count($orders) > 0 ? ($successful / count($orders)) * 100 : 0;
    
    $msg = "📊 <b>SUAS ESTATÍSTICAS</b>\n\n";
    $msg .= "👤 User ID: <code>" . $chat_id . "</code>\n";
    $msg .= "📅 Membro desde: " . date('d M, Y', strtotime($user['registered'])) . "\n\n";
    $msg .= "<b>💰 FINANCEIRO:</b>\n";
    $msg .= "💳 Saldo atual: <b>\$" . number_format($user['credits'], 2) . "</b>\n";
    $msg .= "💸 Total gasto: \$" . number_format($user['total_spent'], 2) . "\n\n";
    $msg .= "<b>📦 PEDIDOS:</b>\n";
    $msg .= "📱 Total: <b>" . count($orders) . "</b>\n";
    $msg .= "✅ Sucesso: " . $successful . "\n";
    $msg .= "❌ Falhos: " . $failed . "\n";
    $msg .= "📈 Taxa: " . number_format($success_rate, 1) . "%\n\n";
    $msg .= "🕐 Última atividade: " . date('d M, H:i', strtotime($user['last_seen']));
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📦 Ver Pedidos', 'callback_data' => 'menu_orders'],
                ['text' => '📜 Histórico', 'callback_data' => 'menu_history']
            ],
            [
                ['text' => '⬅️ Voltar ao Menu', 'callback_data' => 'menu_main']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_history($chat_id, $message_id) {
    $txs = db_read(TRANSACTIONS_FILE, []);
    $user_txs = array_filter($txs, fn($t) => $t['chat_id'] == $chat_id);
    usort($user_txs, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
    $user_txs = array_slice($user_txs, 0, 10);
    
    if (empty($user_txs)) {
        $msg = "📜 <b>HISTÓRICO</b>\n\n";
        $msg .= "Nenhuma transação registrada.";
    } else {
        $msg = "📜 <b>HISTÓRICO DE TRANSAÇÕES</b>\n";
        $msg .= "<i>(Últimas 10)</i>\n\n";
        
        foreach ($user_txs as $tx) {
            $amount = $tx['amount'];
            $symbol = $amount >= 0 ? '+' : '';
            $emoji = $amount >= 0 ? '💚' : '💸';
            
            $type_names = [
                'credit_add' => 'Crédito adicionado',
                'order_success' => 'Unlock realizado',
                'order_failed' => 'Pedido falho',
                'gift_redeem_credit' => 'Gift resgatado',
                'gift_redeem_days' => 'Plano ativado'
            ];
            
            $type = $type_names[$tx['type']] ?? $tx['type'];
            
            $msg .= "$emoji <code>" . date('d/m H:i', strtotime($tx['time'])) . "</code>\n";
            $msg .= "   $type\n";
            $msg .= "   $symbol\$" . number_format(abs($amount), 2) . "\n";
            $msg .= "   Saldo: \$" . number_format($tx['meta']['balance'] ?? 0, 2) . "\n\n";
        }
    }
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '💰 Ver Saldo', 'callback_data' => 'menu_balance']
            ],
            [
                ['text' => '⬅️ Voltar ao Menu', 'callback_data' => 'menu_main']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_gift_input($chat_id, $message_id) {
    $msg = "🎁 <b>RESGATAR GIFT</b>\n\n";
    $msg .= "Para resgatar um código de gift, use:\n\n";
    $msg .= "<code>/resgatar CODIGO-AQUI</code>\n\n";
    $msg .= "Exemplo:\n";
    $msg .= "<code>/resgatar ABCD-1234-EFGH</code>\n\n";
    $msg .= "⚠️ <b>Atenção:</b>\n";
    $msg .= "• Cada gift só pode ser usado 1 vez\n";
    $msg .= "• Aguarde 30 minutos entre resgates\n";
    $msg .= "• Gifts podem adicionar créditos ou dias de plano";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '⬅️ Voltar ao Menu', 'callback_data' => 'menu_main']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_help($chat_id, $message_id) {
    $msg = "❓ <b>AJUDA</b>\n\n";
    $msg .= "<b>📱 Como fazer unlock:</b>\n";
    $msg .= "1. Use /addsn [SERIAL]\n";
    $msg .= "2. Exemplo: /addsn F17VH123ABCD\n";
    $msg .= "3. Aguarde o processamento\n";
    $msg .= "4. Pronto! Unlock realizado\n\n";
    
    $msg .= "<b>💳 Precisa de créditos?</b>\n";
    $msg .= "• Use o botão 'Comprar Créditos'\n";
    $msg .= "• Entre em contato: @segredoupdates\n\n";
    
    $msg .= "<b>🎁 Tem um gift?</b>\n";
    $msg .= "• Use: /resgatar CODIGO\n\n";
    
    $msg .= "<b>❓ Dúvidas?</b>\n";
    $msg .= "• Suporte: @segredoupdates\n";
    $msg .= "• Disponível 24/7";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📱 Suporte', 'url' => 'https://t.me/segredoupdates']
            ],
            [
                ['text' => '⬅️ Voltar ao Menu', 'callback_data' => 'menu_main']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

// ==================== ADMIN FUNCTIONS ====================

function show_admin_menu($chat_id, $message_id) {
    $stats = get_stats();
    
    $msg = "👑 <b>MENU ADMINISTRATIVO</b>\n\n";
    $msg .= "📊 <b>Estatísticas Rápidas:</b>\n";
    $msg .= "👥 Usuários: <b>" . $stats['users'] . "</b>\n";
    $msg .= "💰 Créditos: \$" . number_format($stats['credits'], 2) . "\n";
    $msg .= "📦 Pedidos: <b>" . $stats['orders'] . "</b>\n\n";
    $msg .= "Selecione uma opção:";
    
    $keyboard = create_admin_menu();
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_broadcast_menu($chat_id, $message_id) {
    // Verificar se há broadcast ativo
    $active = is_broadcast_running();
    
    $msg = "📢 <b>MENU DE BROADCAST</b>\n\n";
    
    if ($active) {
        $lock_info = get_broadcast_lock_info();
        $msg .= "⚠️ <b>Broadcast em andamento!</b>\n\n";
        if ($lock_info) {
            $msg .= "🆔 ID: <code>" . ($lock_info['broadcast_id'] ?? 'N/A') . "</code>\n";
            $msg .= "📢 Tipo: " . $lock_info['type'] . "\n";
            $msg .= "⏱️ Tempo: " . $lock_info['elapsed_formatted'] . "\n\n";
        }
        $msg .= "Use os botões abaixo para gerenciar.";
    } else {
        $msg .= "✅ <b>Nenhum broadcast ativo</b>\n\n";
        $msg .= "<b>Métodos disponíveis:</b>\n\n";
        $msg .= "📝 <b>Texto:</b> Use o botão ou /broadcast\n";
        $msg .= "📷 <b>Mídia:</b> Responda qualquer mensagem\n\n";
        $msg .= "Selecione um tipo ou use status/cancelar:";
    }
    
    $keyboard = create_broadcast_menu();
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_broadcast_text_input($chat_id, $message_id) {
    $msg = "📝 <b>BROADCAST DE TEXTO</b>\n\n";
    $msg .= "Para enviar um broadcast de texto, use:\n\n";
    $msg .= "<code>/broadcast Sua mensagem aqui</code>\n\n";
    $msg .= "Exemplo:\n";
    $msg .= "<code>/broadcast 🎉 Promoção especial hoje!</code>\n\n";
    $msg .= "⚠️ A mensagem será enviada para todos os usuários.";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '⬅️ Voltar', 'callback_data' => 'admin_broadcast_menu']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_broadcast_photo_input($chat_id, $message_id) {
    $msg = "📷 <b>BROADCAST DE FOTO</b>\n\n";
    $msg .= "<b>Como fazer:</b>\n";
    $msg .= "1. Envie uma foto para o bot\n";
    $msg .= "2. Responda essa foto com qualquer texto\n";
    $msg .= "3. O bot detecta e inicia broadcast automático!\n\n";
    $msg .= "💡 <b>Dica:</b> Você pode adicionar legenda na foto";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '⬅️ Voltar', 'callback_data' => 'admin_broadcast_menu']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_broadcast_video_input($chat_id, $message_id) {
    $msg = "🎥 <b>BROADCAST DE VÍDEO</b>\n\n";
    $msg .= "<b>Como fazer:</b>\n";
    $msg .= "1. Envie um vídeo para o bot\n";
    $msg .= "2. Responda esse vídeo com qualquer texto\n";
    $msg .= "3. Broadcast iniciado automaticamente!\n\n";
    $msg .= "💡 <b>Dica:</b> Vídeos até 50MB";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '⬅️ Voltar', 'callback_data' => 'admin_broadcast_menu']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_broadcast_status($chat_id, $message_id) {
    if (!is_broadcast_running()) {
        $msg = "✅ <b>STATUS DO BROADCAST</b>\n\n";
        $msg .= "Nenhum broadcast em andamento.";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '⬅️ Voltar', 'callback_data' => 'admin_broadcast_menu']
                ]
            ]
        ];
    } else {
        $lock_info = get_broadcast_lock_info();
        
        $msg = "📊 <b>BROADCAST EM ANDAMENTO</b>\n\n";
        
        if ($lock_info) {
            $msg .= "🆔 ID: <code>" . ($lock_info['broadcast_id'] ?? 'N/A') . "</code>\n";
            $msg .= "👤 Admin: <code>" . $lock_info['admin_id'] . "</code>\n";
            $msg .= "📢 Tipo: <b>" . $lock_info['type'] . "</b>\n";
            $msg .= "⏱️ Tempo: <b>" . $lock_info['elapsed_formatted'] . "</b>\n";
            $msg .= "🔢 PID: <code>" . $lock_info['pid'] . "</code>\n\n";
            $msg .= "Use o botão abaixo para cancelar se necessário.";
        }
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🛑 Cancelar Broadcast', 'callback_data' => 'bc_cancel']
                ],
                [
                    ['text' => '🔄 Atualizar', 'callback_data' => 'bc_status']
                ],
                [
                    ['text' => '⬅️ Voltar', 'callback_data' => 'admin_broadcast_menu']
                ]
            ]
        ];
    }
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function cancel_broadcast_interactive($chat_id, $message_id, $callback_id) {
    if (!is_broadcast_running()) {
        answer_callback_query($callback_id, 'ℹ️ Nenhum broadcast ativo', true);
        show_broadcast_menu($chat_id, $message_id);
        return;
    }
    
    $lock_info = get_broadcast_lock_info();
    remove_broadcast_lock();
    
    $msg = "✅ <b>Broadcast Cancelado</b>\n\n";
    if ($lock_info) {
        $msg .= "🆔 ID: <code>" . ($lock_info['broadcast_id'] ?? 'N/A') . "</code>\n";
        $msg .= "📢 Tipo: " . $lock_info['type'] . "\n";
        $msg .= "⏱️ Duração: " . $lock_info['elapsed_formatted'] . "\n\n";
    }
    $msg .= "⚠️ Lock removido com sucesso.";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '⬅️ Voltar ao Menu', 'callback_data' => 'admin_broadcast_menu']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
    answer_callback_query($callback_id, '✅ Broadcast cancelado!');
    
    bot_log("BROADCAST_CANCEL_INTERACTIVE: por admin {$chat_id}");
}

function show_admin_stats($chat_id, $message_id) {
    $stats = get_stats();
    
    $msg = "📊 <b>ESTATÍSTICAS DO BOT</b>\n\n";
    $msg .= "<b>GERAL:</b>\n";
    $msg .= "👥 Total de usuários: <b>" . $stats['users'] . "</b>\n";
    $msg .= "💰 Créditos no sistema: \$" . number_format($stats['credits'], 2) . "\n";
    $msg .= "💸 Total gasto: \$" . number_format($stats['spent'], 2) . "\n";
    $msg .= "📦 Total de pedidos: <b>" . $stats['orders'] . "</b>\n";
    $msg .= "💵 Receita total: \$" . number_format($stats['spent'], 2) . "\n\n";
    $msg .= "👑 Admins: " . count(ADMIN_IDS) . "\n\n";
    $msg .= "📅 Atualizado: " . date('d/m H:i');
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '👥 Ver Usuários', 'callback_data' => 'admin_users']
            ],
            [
                ['text' => '🔄 Atualizar', 'callback_data' => 'admin_stats']
            ],
            [
                ['text' => '⬅️ Voltar', 'callback_data' => 'menu_admin']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_admin_users($chat_id, $message_id) {
    $users = db_read(USERS_FILE, []);
    $users_array = array_values($users);
    usort($users_array, fn($a, $b) => strtotime($b['last_seen']) - strtotime($a['last_seen']));
    $users_array = array_slice($users_array, 0, 10);
    
    $msg = "👥 <b>USUÁRIOS RECENTES</b>\n";
    $msg .= "<i>(Últimos 10 ativos)</i>\n\n";
    
    foreach ($users_array as $u) {
        $name = $u['name'] ?? 'Desconhecido';
        $admin_badge = $u['is_admin'] ? ' 👑' : '';
        
        $msg .= "<b>$name</b>$admin_badge\n";
        $msg .= "   ID: <code>" . $u['chat_id'] . "</code>\n";
        $msg .= "   💰 \$" . number_format($u['credits'], 2);
        $msg .= " | 📦 " . $u['total_orders'] . "\n";
        $msg .= "   🕐 " . date('d/m H:i', strtotime($u['last_seen'])) . "\n\n";
    }
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📊 Ver Stats', 'callback_data' => 'admin_stats']
            ],
            [
                ['text' => '⬅️ Voltar', 'callback_data' => 'menu_admin']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_gifts_menu($chat_id, $message_id) {
    $gifts = load_gifts();
    $total = count($gifts);
    $active = count(array_filter($gifts, fn($g) => $g['uses'] > 0));
    
    $msg = "🎁 <b>GERENCIAR GIFTS</b>\n\n";
    $msg .= "📊 <b>Estatísticas:</b>\n";
    $msg .= "🎁 Total de gifts: <b>{$total}</b>\n";
    $msg .= "✅ Ativos: <b>{$active}</b>\n";
    $msg .= "❌ Esgotados: <b>" . ($total - $active) . "</b>\n\n";
    $msg .= "Selecione uma opção:";
    
    $keyboard = create_gifts_menu();
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_gifts_list($chat_id, $message_id) {
    $gifts = load_gifts();
    
    if (empty($gifts)) {
        $msg = "📋 <b>LISTA DE GIFTS</b>\n\n";
        $msg .= "Nenhum gift cadastrado.";
    } else {
        uasort($gifts, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        $gifts_array = array_slice($gifts, 0, 15);
        
        $msg = "📋 <b>GIFTS ATIVOS</b>\n";
        $msg .= "<i>(Primeiros 15)</i>\n\n";
        
        foreach ($gifts_array as $g) {
            $batch_icon = !empty($g['batch']) ? '📦' : '✏️';
            $msg .= "{$batch_icon} <code>".$g['code']."</code>\n";
            $msg .= "   └ ".$g['mode']." | ".$g['param'];
            $msg .= " | uses: ".$g['uses']."\n\n";
        }
    }
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📊 Ver Stats', 'callback_data' => 'gift_stats']
            ],
            [
                ['text' => '⬅️ Voltar', 'callback_data' => 'admin_gifts_menu']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function show_gifts_stats($chat_id, $message_id) {
    $gifts = load_gifts();
    $total = count($gifts);
    $credit_gifts = 0;
    $plan_gifts = 0;
    $total_value = 0;
    $total_uses = 0;
    
    foreach ($gifts as $g) {
        if ($g['mode'] === 'credit') {
            $credit_gifts++;
            $total_value += floatval($g['param']) * $g['uses'];
        } else {
            $plan_gifts++;
        }
        $total_uses += $g['uses'];
    }
    
    $msg = "📊 <b>ESTATÍSTICAS DE GIFTS</b>\n\n";
    $msg .= "🎁 Total: <b>{$total}</b>\n";
    $msg .= "💵 Crédito: {$credit_gifts}\n";
    $msg .= "📅 Plano: {$plan_gifts}\n";
    $msg .= "🔢 Usos disponíveis: {$total_uses}\n";
    $msg .= "💰 Valor total: \$" . number_format($total_value, 2);
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '📋 Ver Lista', 'callback_data' => 'gift_list']
            ],
            [
                ['text' => '⬅️ Voltar', 'callback_data' => 'admin_gifts_menu']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

function do_backup($chat_id, $message_id) {
    $msg = "⏳ <b>Iniciando backup...</b>\n\n";
    $msg .= "Aguarde...";
    
    edit_message_text($chat_id, $message_id, $msg);
    
    $count = auto_backup();
    
    if ($count > 0) {
        $msg = "✅ <b>BACKUP CONCLUÍDO</b>\n\n";
        $msg .= "📦 Arquivos salvos: <b>{$count}</b>\n";
        $msg .= "📁 Local: <code>" . BACKUP_DIR . "</code>\n";
        $msg .= "⏱️ " . date('d/m/Y H:i:s');
    } else {
        $msg = "❌ <b>FALHA NO BACKUP</b>\n\n";
        $msg .= "Verifique os logs para mais detalhes.";
    }
    
    $keyboard = [
        'inline_keyboard' => [
            [
                ['text' => '⬅️ Voltar', 'callback_data' => 'menu_admin']
            ]
        ]
    ];
    
    edit_message_with_keyboard($chat_id, $message_id, $msg, $keyboard);
}

// ==================== HELPER FUNCTIONS ====================

function send_message_with_keyboard($chat_id, $text, $keyboard, $parse = 'HTML') {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $parse,
        'reply_markup' => json_encode($keyboard)
    ];
    
    return telegram_api_request('sendMessage', $data);
}

function edit_message_with_keyboard($chat_id, $message_id, $text, $keyboard, $parse = 'HTML') {
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => $parse,
        'reply_markup' => json_encode($keyboard)
    ];
    
    return telegram_api_request('editMessageText', $data);
}

// ==================== MAIN HANDLER COM CALLBACKS ====================

// Processar callback queries
if (isset($update['callback_query'])) {
    handle_callback_query($update['callback_query']);
    http_response_code(200);
    exit;
}

// Processar comandos normais
if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'] ?? '';
    
    // Comando /menu
    if ($text === '/menu' || $text === '/start') {
        show_main_menu($chat_id);
        http_response_code(200);
        exit;
    }
}

http_response_code(200);
?>
