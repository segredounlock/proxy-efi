<?php
/**
 * SEGREDO A12+ Bot com Menu Interativo
 * Sistema de Broadcast com Botões
 * 
 * ✅ RECURSOS:
 * - Menu principal com botões
 * - Submenu de broadcast
 * - Controles interativos
 * - Confirmações visuais
 * - Interface amigável
 * 
 * Versão: 5.0 COM MENU INTERATIVO
 */

// Incluir todas as funções do bot melhorado
require_once __DIR__ . '/bot_completo_melhorado.php';

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
