<?php
/**
 * SEGREDO A12+ Activation Lock Bypass Bot
 * VERSÃO FINAL COMPLETA - COM TODAS MELHORIAS
 * 
 * ✅ RECURSOS INCLUÍDOS:
 * - Sistema de broadcast aprimorado com estatísticas
 * - Sistema de LOCK para evitar broadcasts duplicados
 * - Rate limiting global
 * - Validação de duplicação de pedidos
 * - Backup automático a cada 6 horas
 * - Logs organizados em bot_logs/
 * - Configurações em arquivo separado
 * - Estatísticas de gifts
 * - Broadcast multimídia (texto, foto, vídeo, áudio, documento, enquete)
 * - Comandos de controle de broadcast (/status, /cancel)
 * - 🔥 CORREÇÃO: Loop infinito de broadcast resolvido
 * 
 * Data: 23/11/2024
 * Versão: 3.1 CORRIGIDO
 */

error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(0); // SEM LIMITE DE TEMPO
date_default_timezone_set('America/Sao_Paulo');

// ==================== CONFIGURAÇÕES ====================
$config_file = __DIR__ . '/config.php';
if (file_exists($config_file)) {
    $config = require $config_file;
    define('BOT_TOKEN', $config['bot_token']);
    define('BOT_USERNAME', $config['bot_username']);
    define('BOT_ID', $config['bot_id'] ?? null);
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
    define('BOT_ID', 8573849766); // ID extraído do token
    define('ADMIN_IDS', [1901426549]);
    define('DHRU_API_URL', 'https://realmcloud.cfd/api_center/api_dhru.php');
    define('DHRU_USERNAME', 'iFastServer');
    define('DHRU_API_KEY', 'iFastServer_API_KEY_ADMIN_2025_SZ00');
    define('DHRU_SERVICE_ID', 1);
    define('SERVICE_COST', 0.00);
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
define('BROADCAST_LOCK_TIMEOUT', 600); // 10 minutos

// Criar diretórios
foreach ([DATA_DIR, LOGS_DIR, BACKUP_DIR] as $dir) {
    if (!file_exists($dir)) @mkdir($dir, 0755, true);
}

// ==================== INCLUIR MÓDULOS ====================
require_once __DIR__ . '/referral_system.php';
require_once __DIR__ . '/broadcast_system.php';


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

// ==================== TELEGRAM ====================
function send_message($chat_id, $text, $parse = 'HTML') {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $parse,
        'disable_web_page_preview' => true
    ];

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

    $log_msg = date('Y-m-d H:i:s') . " SEND_MESSAGE TO {$chat_id}: " . substr($text,0,200) . 
               "\nRESPONSE: " . var_export($json, true) . "\n\n";
    @file_put_contents(LOG_MESSAGES, $log_msg, FILE_APPEND | LOCK_EX);

    return $json;
}

function pin_message($chat_id, $message_id, $disable_notification = false) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/pinChatMessage";
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'disable_notification' => $disable_notification ? true : false
    ];

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
    
    $log_msg = date('Y-m-d H:i:s') . " PIN_ATTEMPT: chat={$chat_id} msg={$message_id} " .
               "resp=" . var_export($json, true) . "\n";
    @file_put_contents(LOG_PIN, $log_msg, FILE_APPEND | LOCK_EX);
    
    return $json;
}

// ==================== COMMANDS ====================

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
        $msg .= "🔸 /broadcast [msg] - Broadcast texto\n";
        $msg .= "🔸 /broadcast_status - Status do broadcast\n";
        $msg .= "🔸 /broadcast_cancel - Cancelar broadcast\n";
        $msg .= "🔸 /criar_gift [CODE] [mode] [param] [uses]\n";
        $msg .= "🔸 /criar_gifts [qty] [mode] [param] [uses]\n";
        $msg .= "🔸 /gifts_list - Listar gifts\n";
        $msg .= "🔸 /gifts_stats - Estatísticas de gifts\n";
        $msg .= "🔸 /removerplano [id] - Remover plano\n";
        $msg .= "🔸 /remover_gift [CODE] - Remover gift\n";
        $msg .= "🔸 /backup - Fazer backup manual\n";
    }

    $msg .= "\n<b>🔓 SERVIÇO:</b>\n";
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

// ==================== ADMIN COMMANDS ====================

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

$raw = file_get_contents('php://input');
@file_put_contents(LOG_UPDATES, date('Y-m-d H:i:s') . " RAW_UPDATE:\n" . $raw . "\n\n", FILE_APPEND | LOCK_EX);

$update = json_decode($raw, true);
@file_put_contents(LOG_HANDLER, date('Y-m-d H:i:s') . " HANDLER_ENTER:\n" . var_export($update, true) . "\n\n", FILE_APPEND | LOCK_EX);

$chat_id = $update['message']['chat']['id'] ?? null;
$chat_type = $update['message']['chat']['type'] ?? 'private';
$text = $update['message']['text'] ?? '';
$username = $update['message']['from']['username'] ?? null;
$name = $update['message']['from']['first_name'] ?? null;
$from_id = $update['message']['from']['id'] ?? null;

// IGNORAR MENSAGENS DO PRÓPRIO BOT (CRÍTICO PARA EVITAR LOOPS)
// 1. Verificar se é mensagem editada
if (isset($update['edited_message'])) {
    bot_log("UPDATE_IGNORADO: Mensagem editada");
    http_response_code(200);
    exit;
}

// 2. Verificar se é post de canal
if (isset($update['channel_post'])) {
    bot_log("UPDATE_IGNORADO: Post de canal");
    http_response_code(200);
    exit;
}

// 3. CRÍTICO: Ignorar se a mensagem veio do próprio bot (via from_id)
if ($from_id && defined('BOT_ID') && (int)$from_id === (int)BOT_ID) {
    bot_log("UPDATE_IGNORADO: Mensagem do próprio bot (ID: {$from_id})");
    http_response_code(200);
    exit;
}

// 3.5. CRÍTICO: Ignorar mensagens em grupos/canais (apenas processar mensagens privadas de admins)
if ($chat_type !== 'private') {
    // Se não for mensagem privada, só processar se for comando de admin
    if ($text && $text[0] === '/' && in_array((int)$from_id, ADMIN_IDS, true)) {
        // Admin executando comando em grupo - permitir
        bot_log("GRUPO_PERMITIDO: Admin {$from_id} executou comando em grupo {$chat_id}");
    } else {
        // Qualquer outra mensagem de grupo - ignorar
        bot_log("UPDATE_IGNORADO: Mensagem de grupo/canal não permitida (tipo: {$chat_type})");
        http_response_code(200);
        exit;
    }
}

// 3.6. SUPER CRÍTICO: Ignorar mensagens que contenham texto de broadcast/status do sistema
$broadcast_keywords = [
    '📢 BROADCAST',
    'BROADCAST EM ANDAMENTO',
    'BROADCAST INICIADO',
    'BROADCAST CONCLUÍDO',
    'JÁ HÁ BROADCAST',
    'Progresso:',
    '✅ Enviados:',
    '❌ Falhas:',
    'GIFTS CRIADOS COM SUCESSO',
    'Processando...'
];

foreach ($broadcast_keywords as $keyword) {
    if (stripos($text, $keyword) !== false) {
        bot_log("UPDATE_IGNORADO: Mensagem contém palavra-chave de sistema: '{$keyword}'");
        http_response_code(200);
        exit;
    }
}

// 4. EXTRA: Ignorar se não houver texto (evita processar tipos inválidos)
if (empty($text) && empty($update['message']['photo']) && empty($update['message']['document'])) {
    bot_log("UPDATE_IGNORADO: Mensagem sem conteúdo processável");
    http_response_code(200);
    exit;
}

if ($chat_id) {
    check_auto_backup();
    
    get_user($chat_id);
    update_user($chat_id, [
        'username' => $username,
        'name' => $name,
        'last_seen' => date('Y-m-d H:i:s')
    ]);
    
    @check_plan_expiry_notify($chat_id);

    $parts = preg_split('/\s+/', trim($text));
    $cmd = strtolower($parts[0] ?? '');
    $arg1 = $parts[1] ?? null;
    $arg2 = $parts[2] ?? null;
    $arg3 = $parts[3] ?? null;
    $arg4 = $parts[4] ?? null;

    @file_put_contents(LOG_HANDLER, date('Y-m-d H:i:s') . " DETECTED_CMD: {$cmd} ARGS: " . json_encode([$arg1,$arg2,$arg3,$arg4]) . "\n\n", FILE_APPEND | LOCK_EX);

    switch ($cmd) {
        case '/start':
            // Detectar código de indicação
            if (isset($arg1) && strpos($arg1, 'REF') === 0) {
                handle_referral_start($chat_id, $arg1);
            }
        case '/help':
            cmd_start($chat_id, $name);
            break;
        case '/indicar':
            cmd_indicar($chat_id);
            break;
        case '/meusaldo':
            cmd_meusaldo($chat_id);
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
            cmd_broadcast($chat_id, $text);
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
