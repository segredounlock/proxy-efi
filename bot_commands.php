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
