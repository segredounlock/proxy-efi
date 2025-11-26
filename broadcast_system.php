<?php
/**
 * SISTEMA DE BROADCAST
 * Bypasa12_bot - Sistema modular de broadcast com proteção anti-loop
 * 
 * FUNCIONALIDADES:
 * - Sistema de LOCK para evitar broadcasts simultâneos
 * - Proteção anti-duplicação de mensagens
 * - Bloqueio automático para admins (previne loops)
 * - Barra de progresso em tempo real
 * - Estatísticas completas
 * - Comandos de controle: /broadcast_status, /broadcast_cancel
 * - Logs detalhados
 * 
 * Data: 25/11/2024
 * Versão: 1.0
 */

// ==================== VERIFICAÇÃO DE BROADCAST ATIVO ====================

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
    
    // Timeout de 10 minutos
    if (($now - $started) > BROADCAST_LOCK_TIMEOUT) {
        @unlink(BROADCAST_LOCK_FILE);
        bot_log("BROADCAST_LOCK: Timeout detectado, lock removido");
        return false;
    }
    
    return true;
}

function create_broadcast_lock($chat_id, $type = 'text') {
    $lock_data = [
        'admin_id' => $chat_id,
        'type' => $type,
        'started' => time(),
        'pid' => getmypid()
    ];
    
    @file_put_contents(BROADCAST_LOCK_FILE, json_encode($lock_data), LOCK_EX);
    bot_log("BROADCAST_LOCK: Criado por admin {$chat_id} tipo:{$type}");
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

// ==================== COMANDO BROADCAST ====================

function cmd_broadcast($chat_id, $full_text) {
    $user = get_user($chat_id);
    if (!$user['is_admin']) { 
        send_message($chat_id, "❌ Apenas administradores podem usar este comando."); 
        return; 
    }
    
    // VERIFICAR LOCK
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
        $msg .= "• /broadcast_cancel - Cancelar broadcast\n\n";
        $msg .= "⚠️ <b>ATENÇÃO:</b> Não copie e cole mensagens do sistema como broadcast!";
        
        send_message($chat_id, $msg);
        bot_log("BROADCAST_BLOCKED: Admin {$chat_id} tentou broadcast com outro em andamento");
        return;
    }
    
    // Extrair mensagem
    $parts = preg_split('/\s+/', trim($full_text));
    array_shift($parts); // Remove /broadcast
    $message = trim(implode(' ', $parts));
    
    if ($message === '') {
        send_message($chat_id, "❌ Uso incorreto.\n\nExemplo:\n<code>/broadcast Promoção especial!</code>");
        return;
    }
    
    // PROTEÇÃO ANTI-DUPLICAÇÃO
    $last_broadcast_file = DATA_DIR . '/.last_broadcast_msg';
    if (file_exists($last_broadcast_file)) {
        $last_broadcast = @file_get_contents($last_broadcast_file);
        if ($last_broadcast === $message) {
            $msg = "⚠️ <b>BROADCAST DUPLICADO DETECTADO</b>\n\n";
            $msg .= "Você está tentando enviar a mesma mensagem novamente!\n\n";
            $msg .= "❌ Esta mensagem já foi enviada recentemente.\n\n";
            $msg .= "💡 <b>Dica:</b> Não copie e cole mensagens do sistema (como confirmações) como broadcast.";
            send_message($chat_id, $msg);
            bot_log("BROADCAST_BLOCKED: Mensagem duplicada detectada");
            return;
        }
    }
    
    // Salvar mensagem para comparação
    @file_put_contents($last_broadcast_file, $message, LOCK_EX);
    
    $users = db_read(USERS_FILE, []);
    if (empty($users)) { 
        send_message($chat_id, "⚠️ Nenhum usuário registrado para enviar broadcast."); 
        return; 
    }
    
    // CRIAR LOCK
    create_broadcast_lock($chat_id, 'text');
    
    $total = count($users);
    $sent = 0;
    $failed = 0;
    $skipped_admins = 0;
    $errors = [];
    
    // Log inicial
    $log_msg = "========== BROADCAST INICIADO ==========\n";
    $log_msg .= "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
    $log_msg .= "Admin: {$chat_id}\n";
    $log_msg .= "Total de usuários: {$total}\n";
    $log_msg .= "Mensagem: " . substr($message, 0, 200) . "\n";
    $log_msg .= "PID: " . getmypid() . "\n";
    $log_msg .= "========================================\n\n";
    @file_put_contents(LOG_BROADCAST, $log_msg, FILE_APPEND | LOCK_EX);
    
    // Mensagem inicial ao admin
    $initial_msg = "📢 <b>BROADCAST INICIADO</b>\n\n";
    $initial_msg .= "📊 Total de usuários: <b>{$total}</b>\n";
    $initial_msg .= "⏳ Enviando mensagens...\n\n";
    $initial_msg .= "ℹ️ <i>Nota: Admins não recebem broadcasts para evitar loops</i>";
    
    $init_resp = send_message($chat_id, $initial_msg);
    $status_msg_id = $init_resp['result']['message_id'] ?? null;
    
    $processed = 0;
    $last_update = 0;
    
    // Enviar para cada usuário
    foreach ($users as $u) {
        // VERIFICAR SE LOCK AINDA EXISTE (permite cancelamento)
        if (!is_broadcast_running()) {
            bot_log("BROADCAST_ABORTED: Lock removido durante execução");
            
            $abort_msg = "⚠️ <b>BROADCAST CANCELADO</b>\n\n";
            $abort_msg .= "📊 Progresso antes do cancelamento:\n";
            $abort_msg .= "✅ Enviados: {$sent}\n";
            $abort_msg .= "❌ Falhas: {$failed}\n";
            $abort_msg .= "⏸️ Interrompido em: {$processed}/{$total}";
            
            if ($status_msg_id) {
                edit_message($chat_id, $status_msg_id, $abort_msg);
            }
            
            return;
        }
        
        if (!empty($u['chat_id'])) {
            $target_chat_id = $u['chat_id'];
            
            // 🔒 PROTEÇÃO CRÍTICA: NUNCA ENVIAR BROADCAST PARA ADMINS
            if (in_array((int)$target_chat_id, ADMIN_IDS, true)) {
                $skipped_admins++;
                $log_entry = "🚫 BLOQUEADO: Admin {$target_chat_id} não recebe broadcasts (prevenção de loop)\n";
                @file_put_contents(LOG_BROADCAST, $log_entry, FILE_APPEND | LOCK_EX);
                bot_log("BROADCAST_SKIP: Admin {$target_chat_id} bloqueado de receber broadcast");
                continue;
            }
            
            // Enviar mensagem
            $resp = send_message($target_chat_id, $message);
            
            if (isset($resp['ok']) && $resp['ok']) {
                $sent++;
                $log_entry = "✅ ENVIADO para {$target_chat_id}\n";
            } else {
                $failed++;
                $error_desc = $resp['description'] ?? 'Erro desconhecido';
                $errors[] = "{$target_chat_id}: {$error_desc}";
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
                    edit_message($chat_id, $status_msg_id, $progress_msg);
                }
                
                $last_update = $now;
            }
            
            usleep(100000); // 0.1 segundo entre envios
        }
    }
    
    // REMOVER LOCK
    remove_broadcast_lock();
    
    // Estatísticas finais
    $success_rate = $total > 0 ? round(($sent / $total) * 100, 1) : 0;
    
    $final_msg = "✅ <b>BROADCAST CONCLUÍDO</b>\n\n";
    $final_msg .= "📊 <b>ESTATÍSTICAS:</b>\n";
    $final_msg .= "━━━━━━━━━━━━━━━━━━━━\n";
    $final_msg .= "👥 Total: <b>{$total}</b>\n";
    $final_msg .= "✅ Enviados: <b>{$sent}</b>\n";
    $final_msg .= "❌ Falhas: <b>{$failed}</b>\n";
    if ($skipped_admins > 0) {
        $final_msg .= "🚫 Admins bloqueados: <b>{$skipped_admins}</b>\n";
    }
    $final_msg .= "📈 Taxa: <b>{$success_rate}%</b>\n";
    $final_msg .= "━━━━━━━━━━━━━━━━━━━━\n\n";
    $final_msg .= "ℹ️ <i>Admins não recebem broadcasts para prevenir loops</i>";
    
    if ($status_msg_id) {
        edit_message($chat_id, $status_msg_id, $final_msg);
    } else {
        send_message($chat_id, $final_msg);
    }
    
    // Log final
    $log_final = "\n========== BROADCAST FINALIZADO ==========\n";
    $log_final .= "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
    $log_final .= "Total: {$total} | Enviados: {$sent} | Falhas: {$failed}\n";
    $log_final .= "Taxa de sucesso: {$success_rate}%\n";
    $log_final .= "==========================================\n\n";
    @file_put_contents(LOG_BROADCAST, $log_final, FILE_APPEND | LOCK_EX);
    
    bot_log("BROADCAST_COMPLETED: admin={$chat_id} total={$total} sent={$sent} failed={$failed}");
}

// ==================== COMANDO STATUS ====================

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
    $msg .= "👤 Admin: <code>" . $lock_info['admin_id'] . "</code>\n";
    $msg .= "📢 Tipo: " . $lock_info['type'] . "\n";
    $msg .= "⏱️ Tempo decorrido: " . $lock_info['elapsed_formatted'] . "\n";
    $msg .= "🔢 PID: " . $lock_info['pid'] . "\n\n";
    $msg .= "💡 Use /broadcast_cancel para forçar cancelamento";
    
    send_message($chat_id, $msg);
}

// ==================== COMANDO CANCEL ====================

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
        $msg .= "👤 Admin: <code>" . $lock_info['admin_id'] . "</code>\n";
        $msg .= "📢 Tipo: " . $lock_info['type'] . "\n";
        $msg .= "⏱️ Duração: " . $lock_info['elapsed_formatted'] . "\n";
    }
    $msg .= "\n⚠️ Lock removido manualmente";
    
    send_message($chat_id, $msg);
    bot_log("BROADCAST_CANCELLED: Admin {$chat_id} cancelou broadcast manualmente");
}

// ==================== HELPER: EDITAR MENSAGEM ====================

function edit_message($chat_id, $message_id, $text) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/editMessageText";
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 5
    ]);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return @json_decode($result, true) ?? [];
}

?>
