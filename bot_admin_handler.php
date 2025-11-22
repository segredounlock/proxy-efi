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
        if ($user['is_admin']) {
            bot_log("BROADCAST_REPLY_DETECTED: Admin {$chat_id} respondendo mensagem");
            cmd_broadcast_reply($chat_id, $reply_to_message);
            http_response_code(200);
            exit;
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
