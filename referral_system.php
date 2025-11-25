<?php
/**
 * SISTEMA DE INDICAÇÕES (REFERRAL SYSTEM)
 * Bypasa12_bot - Sistema modular de indicações
 * 
 * FUNCIONALIDADES:
 * - Geração de código único de indicação por usuário
 * - Registro de indicações quando novo usuário entra com código
 * - Sistema de recompensas por marcos de indicações (1, 3, 5, 10, 25, 50+)
 * - Saldo de créditos acumulado por indicações
 * - Histórico completo de transações
 * - Comando /indicar - Mostra código e estatísticas
 * - Comando /meusaldo - Mostra saldo e histórico
 * - Integração automática com /start
 * 
 * Data: 25/11/2024
 * Versão: 1.0
 */

// Arquivo de dados de indicações
define('REFERRALS_FILE', DATA_DIR . '/referrals.json');
define('REFERRAL_REWARDS_FILE', DATA_DIR . '/referral_rewards.json');
define('REFERRAL_BALANCE_HISTORY_FILE', DATA_DIR . '/referral_balance_history.json');

// ==================== CONFIGURAÇÃO DE RECOMPENSAS ====================

function get_referral_rewards_config() {
    $default_rewards = [
        1 => ['credits' => 5.00, 'description' => 'R$ 5,00 - Primeira indicação'],
        3 => ['credits' => 10.00, 'description' => 'R$ 10,00 - Três indicações'],
        5 => ['credits' => 20.00, 'description' => 'R$ 20,00 - Cinco indicações'],
        10 => ['credits' => 50.00, 'description' => 'R$ 50,00 - Dez indicações'],
        25 => ['credits' => 150.00, 'description' => 'R$ 150,00 - Vinte e cinco indicações'],
        50 => ['credits' => 350.00, 'description' => 'R$ 350,00 - Cinquenta indicações'],
        100 => ['credits' => 800.00, 'description' => 'R$ 800,00 - Cem indicações']
    ];
    
    $rewards = db_read(REFERRAL_REWARDS_FILE, $default_rewards);
    if (empty($rewards)) {
        db_write(REFERRAL_REWARDS_FILE, $default_rewards);
        return $default_rewards;
    }
    return $rewards;
}

// ==================== GERAÇÃO DE CÓDIGO ====================

function generate_referral_code($chat_id) {
    // Formato: REF{chat_id_6digitos}{hash_4chars}
    // Exemplo: REF000123AB4C
    $padded_id = str_pad(substr($chat_id, -6), 6, '0', STR_PAD_LEFT);
    $hash = strtoupper(substr(md5($chat_id . time()), 0, 4));
    return "REF{$padded_id}{$hash}";
}

function get_user_referral_code($chat_id) {
    $user = get_user($chat_id);
    
    if (!empty($user['referral_code'])) {
        return $user['referral_code'];
    }
    
    // Gera novo código
    $code = generate_referral_code($chat_id);
    update_user($chat_id, ['referral_code' => $code]);
    
    bot_log("REFERRAL: Código gerado para user {$chat_id}: {$code}");
    return $code;
}

// ==================== REGISTRO DE INDICAÇÕES ====================

function register_referral($referred_chat_id, $referral_code) {
    $referrals = db_read(REFERRALS_FILE, []);
    
    // Busca quem é o dono do código
    $referrer_chat_id = find_referrer_by_code($referral_code);
    
    if (!$referrer_chat_id) {
        bot_log("REFERRAL_ERROR: Código inválido: {$referral_code}");
        return ['success' => false, 'message' => 'Código de indicação inválido'];
    }
    
    if ($referrer_chat_id == $referred_chat_id) {
        bot_log("REFERRAL_ERROR: User {$referred_chat_id} tentou usar próprio código");
        return ['success' => false, 'message' => 'Você não pode usar seu próprio código'];
    }
    
    // Verifica se já foi indicado
    $referred_id_str = strval($referred_chat_id);
    if (isset($referrals[$referred_id_str])) {
        bot_log("REFERRAL_ERROR: User {$referred_chat_id} já foi indicado anteriormente");
        return ['success' => false, 'message' => 'Você já foi indicado por outro usuário'];
    }
    
    // Registra indicação
    $referrals[$referred_id_str] = [
        'referrer_chat_id' => $referrer_chat_id,
        'referred_chat_id' => $referred_chat_id,
        'referral_code' => $referral_code,
        'status' => 'pending', // pending, completed
        'registered_at' => date('Y-m-d H:i:s'),
        'completed_at' => null
    ];
    
    db_write(REFERRALS_FILE, $referrals);
    
    // Atualiza contador no perfil do indicador
    increment_referral_count($referrer_chat_id);
    
    bot_log("REFERRAL_SUCCESS: {$referred_chat_id} indicado por {$referrer_chat_id}");
    
    return [
        'success' => true,
        'message' => '✅ Indicação registrada com sucesso!',
        'referrer_chat_id' => $referrer_chat_id
    ];
}

function find_referrer_by_code($code) {
    $users = db_read(USERS_FILE, []);
    
    foreach ($users as $user) {
        if (isset($user['referral_code']) && $user['referral_code'] === $code) {
            return $user['chat_id'];
        }
    }
    
    return null;
}

function increment_referral_count($chat_id) {
    $user = get_user($chat_id);
    $count = isset($user['referral_count']) ? $user['referral_count'] : 0;
    update_user($chat_id, ['referral_count' => $count + 1]);
}

// ==================== COMPLETAR INDICAÇÃO ====================

function complete_referral($referred_chat_id) {
    $referrals = db_read(REFERRALS_FILE, []);
    $referred_id_str = strval($referred_chat_id);
    
    if (!isset($referrals[$referred_id_str])) {
        return false;
    }
    
    $referral = $referrals[$referred_id_str];
    
    // Se já foi completada, não faz nada
    if ($referral['status'] === 'completed') {
        return false;
    }
    
    // Marca como completada
    $referrals[$referred_id_str]['status'] = 'completed';
    $referrals[$referred_id_str]['completed_at'] = date('Y-m-d H:i:s');
    db_write(REFERRALS_FILE, $referrals);
    
    $referrer_chat_id = $referral['referrer_chat_id'];
    
    // Verifica se deve dar recompensa
    check_and_give_rewards($referrer_chat_id);
    
    bot_log("REFERRAL_COMPLETED: User {$referred_chat_id} completou primeira compra");
    
    return true;
}

// ==================== SISTEMA DE RECOMPENSAS ====================

function check_and_give_rewards($referrer_chat_id) {
    $user = get_user($referrer_chat_id);
    $total_referrals = $user['referral_count'] ?? 0;
    
    $rewards_config = get_referral_rewards_config();
    
    // Verifica se atingiu algum marco
    if (isset($rewards_config[$total_referrals])) {
        $reward = $rewards_config[$total_referrals];
        $credits = $reward['credits'];
        $description = $reward['description'];
        
        // Adiciona créditos
        add_referral_credits($referrer_chat_id, $credits, 'referral_reward', $description);
        
        // Notifica usuário
        $msg = "🎉 <b>RECOMPENSA DE INDICAÇÃO!</b>\n\n";
        $msg .= "Você atingiu <b>{$total_referrals} indicaç" . ($total_referrals == 1 ? 'ão' : 'ões') . "</b>!\n\n";
        $msg .= "💰 <b>Recompensa:</b> {$description}\n";
        $msg .= "✅ Créditos adicionados ao seu saldo!\n\n";
        $msg .= "Continue indicando amigos e ganhe mais recompensas! 🚀";
        
        send_message($referrer_chat_id, $msg);
        
        bot_log("REFERRAL_REWARD: User {$referrer_chat_id} recebeu {$credits} créditos por {$total_referrals} indicações");
    }
}

// ==================== GERENCIAMENTO DE CRÉDITOS ====================

function add_referral_credits($chat_id, $amount, $type = 'referral_reward', $description = '') {
    // Adiciona créditos ao saldo do usuário
    add_credits($chat_id, $amount);
    
    // Registra no histórico de saldo
    $history = db_read(REFERRAL_BALANCE_HISTORY_FILE, []);
    
    $history[] = [
        'chat_id' => $chat_id,
        'amount' => $amount,
        'type' => $type,
        'description' => $description,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    db_write(REFERRAL_BALANCE_HISTORY_FILE, $history);
    
    bot_log("REFERRAL_CREDITS: {$amount} créditos adicionados para user {$chat_id}");
}

// ==================== ESTATÍSTICAS ====================

function get_referral_stats($chat_id) {
    $referrals = db_read(REFERRALS_FILE, []);
    $user = get_user($chat_id);
    
    $total = 0;
    $completed = 0;
    $pending = 0;
    $referred_users = [];
    
    foreach ($referrals as $referral) {
        if ($referral['referrer_chat_id'] == $chat_id) {
            $total++;
            
            if ($referral['status'] === 'completed') {
                $completed++;
            } else {
                $pending++;
            }
            
            $referred_users[] = [
                'chat_id' => $referral['referred_chat_id'],
                'status' => $referral['status'],
                'registered_at' => $referral['registered_at'],
                'completed_at' => $referral['completed_at']
            ];
        }
    }
    
    // Próxima recompensa
    $rewards_config = get_referral_rewards_config();
    $next_reward = null;
    
    foreach ($rewards_config as $milestone => $reward) {
        if ($milestone > $total) {
            $next_reward = [
                'milestone' => $milestone,
                'credits' => $reward['credits'],
                'description' => $reward['description'],
                'remaining' => $milestone - $total
            ];
            break;
        }
    }
    
    return [
        'referral_code' => $user['referral_code'] ?? '',
        'total_referrals' => $total,
        'completed_referrals' => $completed,
        'pending_referrals' => $pending,
        'current_balance' => $user['credits'] ?? 0.00,
        'referred_users' => $referred_users,
        'next_reward' => $next_reward
    ];
}

function get_balance_history($chat_id, $limit = 20) {
    $history = db_read(REFERRAL_BALANCE_HISTORY_FILE, []);
    
    $user_history = array_filter($history, function($item) use ($chat_id) {
        return $item['chat_id'] == $chat_id;
    });
    
    // Ordena por data decrescente
    usort($user_history, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return array_slice($user_history, 0, $limit);
}

// ==================== COMANDOS ====================

function cmd_indicar($chat_id) {
    $code = get_user_referral_code($chat_id);
    $stats = get_referral_stats($chat_id);
    
    $bot_username = BOT_USERNAME;
    $referral_link = "https://t.me/{$bot_username}?start={$code}";
    
    $msg = "🎁 <b>SISTEMA DE INDICAÇÕES</b>\n\n";
    $msg .= "📱 <b>Seu Código:</b> <code>{$code}</code>\n";
    $msg .= "<i>(Toque para copiar)</i>\n\n";
    
    $msg .= "👥 <b>Suas Indicações:</b>\n";
    $msg .= "• Total: {$stats['total_referrals']}\n";
    $msg .= "• Completas: {$stats['completed_referrals']}\n";
    $msg .= "• Pendentes: {$stats['pending_referrals']}\n\n";
    
    $msg .= "💰 <b>Seu Saldo:</b> R$ " . number_format($stats['current_balance'], 2, ',', '.') . "\n\n";
    
    // Próxima recompensa
    if ($stats['next_reward']) {
        $next = $stats['next_reward'];
        $msg .= "🎯 <b>Próxima Recompensa:</b>\n";
        $msg .= "{$next['description']}\n";
        $msg .= "Faltam apenas <b>{$next['remaining']}</b> indicaç" . ($next['remaining'] == 1 ? 'ão' : 'ões') . "!\n\n";
    }
    
    $msg .= "🔗 <b>Compartilhe seu link:</b>\n";
    $msg .= "<code>{$referral_link}</code>\n\n";
    
    $msg .= "💡 <b>Como Funciona:</b>\n";
    $msg .= "1️⃣ Compartilhe seu código ou link\n";
    $msg .= "2️⃣ Seus amigos se cadastram usando seu código\n";
    $msg .= "3️⃣ Quando fazem a primeira compra, você ganha recompensas!\n";
    $msg .= "4️⃣ Use seu saldo como desconto em compras\n";
    
    // Lista últimas indicações
    if (!empty($stats['referred_users'])) {
        $msg .= "\n📋 <b>Suas Últimas Indicações:</b>\n";
        $shown = array_slice($stats['referred_users'], 0, 5);
        foreach ($shown as $i => $ref) {
            $status_icon = $ref['status'] === 'completed' ? '✅' : '⏳';
            $msg .= ($i + 1) . ". {$status_icon} User #{$ref['chat_id']}\n";
        }
    }
    
    send_message($chat_id, $msg);
}

function cmd_meusaldo($chat_id) {
    $user = get_user($chat_id);
    $balance = $user['credits'] ?? 0.00;
    $history = get_balance_history($chat_id, 10);
    
    $msg = "💰 <b>MEU SALDO</b>\n\n";
    $msg .= "<b>Saldo Atual:</b> R$ " . number_format($balance, 2, ',', '.') . "\n\n";
    
    if (!empty($history)) {
        $msg .= "📜 <b>Histórico de Transações:</b>\n";
        $msg .= "<i>(Últimas 10)</i>\n\n";
        
        foreach ($history as $item) {
            $type_labels = [
                'referral_reward' => '🎁 Recompensa de Indicação',
                'admin_adjust' => '⚙️ Ajuste Administrativo',
                'bonus' => '🎉 Bônus'
            ];
            
            $type_label = $type_labels[$item['type']] ?? $item['type'];
            $amount_formatted = number_format($item['amount'], 2, ',', '.');
            
            $msg .= "💚 + R$ {$amount_formatted}\n";
            $msg .= "   {$type_label}\n";
            
            if (!empty($item['description'])) {
                $msg .= "   <i>{$item['description']}</i>\n";
            }
            
            $date = date('d/m/Y H:i', strtotime($item['created_at']));
            $msg .= "   {$date}\n\n";
        }
    } else {
        $msg .= "<i>Nenhuma transação ainda.</i>\n\n";
    }
    
    $msg .= "💡 <b>Como usar seu saldo:</b>\n";
    $msg .= "Seu saldo pode ser usado como desconto em suas próximas compras!\n\n";
    $msg .= "Use /indicar para ganhar mais créditos!";
    
    send_message($chat_id, $msg);
}

// ==================== INTEGRAÇÃO COM /start ====================

function handle_referral_start($chat_id, $start_param) {
    if (empty($start_param) || !str_starts_with($start_param, 'REF')) {
        return false;
    }
    
    $result = register_referral($chat_id, $start_param);
    
    if ($result['success']) {
        $msg = "🎉 <b>BEM-VINDO!</b>\n\n";
        $msg .= "Você entrou através de uma indicação!\n";
        $msg .= "Quando fizer sua primeira compra, seu amigo ganhará recompensas! 🎁\n\n";
        $msg .= "Você também pode indicar amigos e ganhar créditos!\n";
        $msg .= "Use /indicar para ver seu código.";
        
        send_message($chat_id, $msg);
        
        return true;
    }
    
    return false;
}

?>
