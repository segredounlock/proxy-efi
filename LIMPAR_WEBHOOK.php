<?php
/**
 * SCRIPT DE LIMPEZA E RECONFIGURAÇÃO DO WEBHOOK
 * Use este script se o bot não estiver respondendo
 */

header('Content-Type: text/plain; charset=utf-8');

$bot_token = '8573849766:AAErNoIGk0D3m4o66r65sifKombG9cZuGKA';
$webhook_url = 'https://buscalotter.com/a12/bot_unico_completo.php';

echo "═══════════════════════════════════════════════\n";
echo "  LIMPEZA E RECONFIGURAÇÃO DO WEBHOOK\n";
echo "═══════════════════════════════════════════════\n\n";

// PASSO 1: Verificar status atual
echo "📊 PASSO 1: Verificando status atual...\n";
$url = "https://api.telegram.org/bot{$bot_token}/getWebhookInfo";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if ($data['ok']) {
    $info = $data['result'];
    echo "   URL atual: " . ($info['url'] ?: 'Não configurado') . "\n";
    echo "   Updates pendentes: " . $info['pending_update_count'] . "\n";
    
    if (isset($info['last_error_date'])) {
        echo "   ⚠️ ÚLTIMO ERRO: " . $info['last_error_message'] . "\n";
        echo "   Data: " . date('Y-m-d H:i:s', $info['last_error_date']) . "\n";
    }
}

// PASSO 2: Deletar webhook e limpar updates
echo "\n🗑️ PASSO 2: Deletando webhook e limpando updates...\n";
$url = "https://api.telegram.org/bot{$bot_token}/deleteWebhook?drop_pending_updates=true";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if ($data['ok']) {
    echo "   ✅ Webhook deletado com sucesso\n";
    echo "   ✅ Updates pendentes foram limpos\n";
} else {
    echo "   ❌ Erro ao deletar webhook\n";
}

sleep(2); // Aguardar 2 segundos

// PASSO 3: Configurar novo webhook
echo "\n🔗 PASSO 3: Configurando novo webhook...\n";
echo "   URL: {$webhook_url}\n";

$url = "https://api.telegram.org/bot{$bot_token}/setWebhook?url=" . urlencode($webhook_url) . "&max_connections=40&drop_pending_updates=true";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if ($data['ok']) {
    echo "   ✅ Webhook configurado com sucesso!\n";
} else {
    echo "   ❌ Erro ao configurar webhook\n";
    echo "   Resposta: " . $response . "\n";
}

sleep(2); // Aguardar 2 segundos

// PASSO 4: Verificar configuração final
echo "\n✅ PASSO 4: Verificando configuração final...\n";
$url = "https://api.telegram.org/bot{$bot_token}/getWebhookInfo";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if ($data['ok']) {
    $info = $data['result'];
    echo "   URL: " . $info['url'] . "\n";
    echo "   Updates pendentes: " . $info['pending_update_count'] . "\n";
    echo "   Conexões máximas: " . $info['max_connections'] . "\n";
    
    if (isset($info['last_error_date'])) {
        echo "   ⚠️ Ainda há erro: " . $info['last_error_message'] . "\n";
    } else {
        echo "   ✅ Sem erros!\n";
    }
}

echo "\n═══════════════════════════════════════════════\n";
echo "  PROCEDIMENTO CONCLUÍDO\n";
echo "═══════════════════════════════════════════════\n\n";

echo "🎯 PRÓXIMOS PASSOS:\n\n";
echo "1. Envie /start para o bot no Telegram\n";
echo "2. Se não funcionar, execute CHECK_BOT.php\n";
echo "3. Verifique os logs em bot_logs/debug.log\n\n";

echo "Data: " . date('Y-m-d H:i:s') . "\n";
