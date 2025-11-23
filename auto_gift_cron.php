<?php
/**
 * AUTO-GIFT CRON SYSTEM
 * Sistema automático de geração e envio de gifts
 * 
 * Este script deve ser executado periodicamente via cron
 * ou pode ser executado como daemon em background
 * 
 * Versão: 1.0
 * Data: 2025-11-23
 */

error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(0);
date_default_timezone_set('America/Sao_Paulo');

// ==================== CONFIGURAÇÕES ====================
define('BASE_DIR', __DIR__);
define('DATA_DIR', BASE_DIR . '/bot_data');
define('LOGS_DIR', BASE_DIR . '/bot_logs');
define('AUTO_GIFT_CONFIG_FILE', DATA_DIR . '/auto_gift_config.json');
define('AUTO_GIFT_LOG', LOGS_DIR . '/auto_gift.log');
define('USERS_FILE', DATA_DIR . '/users.json');
define('GIFTS_FILE', DATA_DIR . '/gifts.json');

// Constantes do bot (copiar do arquivo principal)
$config_file = BASE_DIR . '/config.php';
if (file_exists($config_file)) {
    $config = require $config_file;
    define('BOT_TOKEN', $config['bot_token']);
    define('ADMIN_IDS', $config['admin_ids']);
} else {
    define('BOT_TOKEN', '8573849766:AAErNoIGk0D3m4o66r65sifKombG9cZuGKA');
    define('ADMIN_IDS', [1901426549]);
}

// Criar diretórios se não existirem
foreach ([DATA_DIR, LOGS_DIR] as $dir) {
    if (!file_exists($dir)) @mkdir($dir, 0755, true);
}

// ==================== FUNÇÕES AUXILIARES ====================

function auto_gift_log($message) {
    $log = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    @file_put_contents(AUTO_GIFT_LOG, $log, FILE_APPEND | LOCK_EX);
    echo $log; // Para debug quando executado manualmente
}

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
    return @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

function load_auto_gift_config() {
    $default = [
        'enabled' => false,
        'interval_minutes' => 60, // Padrão: 1 hora
        'gift_quantity' => 1,
        'gift_mode' => 'credit',
        'gift_param' => '5.00',
        'gift_uses' => 1,
        'broadcast_message' => "🎁 <b>GIFT AUTOMÁTICO!</b>\n\nUse o código abaixo para resgatar:\n\n<code>{CODE}</code>\n\n⚡ Válido por tempo limitado!",
        'last_run' => null,
        'total_runs' => 0,
        'total_gifts_sent' => 0
    ];
    return db_read(AUTO_GIFT_CONFIG_FILE, $default);
}

function save_auto_gift_config($config) {
    return db_write(AUTO_GIFT_CONFIG_FILE, $config);
}

function generate_gift_code($length = 12) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, $max)];
    }
    return $code;
}

function load_gifts() {
    return db_read(GIFTS_FILE, []);
}

function save_gifts($gifts) {
    return db_write(GIFTS_FILE, $gifts);
}

function load_users() {
    return db_read(USERS_FILE, []);
}

function send_telegram_message($chat_id, $text, $parse_mode = 'HTML') {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $parse_mode,
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
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    }
    
    return ['ok' => false];
}

// ==================== FUNÇÕES PRINCIPAIS ====================

function create_auto_gift($mode, $param, $uses = 1) {
    $gifts = load_gifts();
    $code = generate_gift_code();
    
    // Garantir código único
    $attempts = 0;
    while (isset($gifts[$code]) && $attempts < 10) {
        $code = generate_gift_code();
        $attempts++;
    }
    
    if ($attempts >= 10) {
        auto_gift_log("ERRO: Não foi possível gerar código único após 10 tentativas");
        return null;
    }
    
    $entry = [
        'code' => $code,
        'mode' => $mode,
        'param' => $param,
        'uses' => $uses,
        'created_at' => date('Y-m-d H:i:s'),
        'auto_generated' => true
    ];
    
    $gifts[$code] = $entry;
    
    if (save_gifts($gifts)) {
        auto_gift_log("✅ Gift criado: {$code} | mode:{$mode} | param:{$param} | uses:{$uses}");
        return $code;
    }
    
    auto_gift_log("❌ ERRO ao salvar gift no arquivo");
    return null;
}

function broadcast_auto_gift($code, $message_template) {
    $users = load_users();
    $total_users = count($users);
    
    if ($total_users === 0) {
        auto_gift_log("⚠️ Nenhum usuário cadastrado para enviar broadcast");
        return ['sent' => 0, 'failed' => 0];
    }
    
    // Substituir {CODE} no template
    $message = str_replace('{CODE}', $code, $message_template);
    
    $sent = 0;
    $failed = 0;
    
    auto_gift_log("📢 Iniciando broadcast para {$total_users} usuários...");
    
    foreach ($users as $user) {
        if (!empty($user['chat_id'])) {
            $chat_id = $user['chat_id'];
            
            // PROTEÇÃO: Não enviar para admins
            if (in_array((int)$chat_id, ADMIN_IDS, true)) {
                auto_gift_log("🚫 Admin {$chat_id} bloqueado de receber auto-gift");
                continue;
            }
            
            $result = send_telegram_message($chat_id, $message);
            
            if (isset($result['ok']) && $result['ok']) {
                $sent++;
                auto_gift_log("✅ Enviado para {$chat_id}");
            } else {
                $failed++;
                auto_gift_log("❌ Falha ao enviar para {$chat_id}");
            }
            
            // Rate limiting: 100ms entre mensagens
            usleep(100000);
        }
    }
    
    auto_gift_log("📊 Broadcast concluído: {$sent} enviados, {$failed} falhas");
    
    return ['sent' => $sent, 'failed' => $failed];
}

function notify_admins($message) {
    foreach (ADMIN_IDS as $admin_id) {
        send_telegram_message($admin_id, $message);
    }
}

function run_auto_gift() {
    auto_gift_log("========== AUTO-GIFT EXECUTION START ==========");
    
    $config = load_auto_gift_config();
    
    // Verificar se está habilitado
    if (!$config['enabled']) {
        auto_gift_log("⚠️ Auto-gift está DESABILITADO");
        auto_gift_log("========== AUTO-GIFT EXECUTION END ==========\n");
        return;
    }
    
    // Verificar intervalo
    $now = time();
    $last_run = $config['last_run'] ? strtotime($config['last_run']) : 0;
    $interval_seconds = $config['interval_minutes'] * 60;
    $time_since_last = $now - $last_run;
    
    if ($last_run > 0 && $time_since_last < $interval_seconds) {
        $wait_time = $interval_seconds - $time_since_last;
        $wait_minutes = ceil($wait_time / 60);
        auto_gift_log("⏳ Aguardando intervalo: {$wait_minutes} minutos restantes");
        auto_gift_log("========== AUTO-GIFT EXECUTION END ==========\n");
        return;
    }
    
    auto_gift_log("🎁 Iniciando geração automática de gifts...");
    auto_gift_log("📦 Quantidade: {$config['gift_quantity']}");
    auto_gift_log("🎯 Modo: {$config['gift_mode']}");
    auto_gift_log("💰 Valor: {$config['gift_param']}");
    auto_gift_log("🔢 Usos: {$config['gift_uses']}");
    
    $gifts_created = [];
    $gifts_failed = 0;
    
    // Criar gifts
    for ($i = 0; $i < $config['gift_quantity']; $i++) {
        $code = create_auto_gift(
            $config['gift_mode'],
            $config['gift_param'],
            $config['gift_uses']
        );
        
        if ($code) {
            $gifts_created[] = $code;
        } else {
            $gifts_failed++;
        }
    }
    
    if (empty($gifts_created)) {
        auto_gift_log("❌ ERRO: Nenhum gift foi criado");
        notify_admins("❌ <b>AUTO-GIFT FALHOU</b>\n\nNão foi possível criar gifts.\n\nVerifique os logs para mais detalhes.");
        auto_gift_log("========== AUTO-GIFT EXECUTION END ==========\n");
        return;
    }
    
    auto_gift_log("✅ Gifts criados com sucesso: " . count($gifts_created));
    
    // Enviar broadcasts
    $total_sent = 0;
    $total_failed = 0;
    
    foreach ($gifts_created as $code) {
        $result = broadcast_auto_gift($code, $config['broadcast_message']);
        $total_sent += $result['sent'];
        $total_failed += $result['failed'];
    }
    
    // Atualizar configuração
    $config['last_run'] = date('Y-m-d H:i:s');
    $config['total_runs']++;
    $config['total_gifts_sent'] += count($gifts_created);
    save_auto_gift_config($config);
    
    // Notificar admins
    $admin_msg = "✅ <b>AUTO-GIFT EXECUTADO</b>\n\n";
    $admin_msg .= "📦 Gifts criados: <b>" . count($gifts_created) . "</b>\n";
    if ($gifts_failed > 0) {
        $admin_msg .= "⚠️ Falhas na criação: {$gifts_failed}\n";
    }
    $admin_msg .= "📢 Broadcasts enviados: <b>{$total_sent}</b>\n";
    if ($total_failed > 0) {
        $admin_msg .= "❌ Falhas no envio: {$total_failed}\n";
    }
    $admin_msg .= "\n<b>📋 Códigos gerados:</b>\n";
    foreach ($gifts_created as $code) {
        $admin_msg .= "<code>{$code}</code>\n";
    }
    $admin_msg .= "\n⏰ Próxima execução em: <b>{$config['interval_minutes']} minutos</b>";
    
    notify_admins($admin_msg);
    
    auto_gift_log("✅ Execução completa!");
    auto_gift_log("📊 Total de execuções até agora: {$config['total_runs']}");
    auto_gift_log("🎁 Total de gifts enviados: {$config['total_gifts_sent']}");
    auto_gift_log("========== AUTO-GIFT EXECUTION END ==========\n");
}

// ==================== EXECUÇÃO ====================

// Verificar se foi chamado via CLI ou web
if (php_sapi_name() === 'cli') {
    // Modo CLI - executar uma vez
    run_auto_gift();
} else {
    // Modo web - pode ser usado para testes
    header('Content-Type: text/plain; charset=utf-8');
    echo "AUTO-GIFT CRON SYSTEM\n";
    echo "=====================\n\n";
    run_auto_gift();
    echo "\n\nExecução concluída!\n";
}
