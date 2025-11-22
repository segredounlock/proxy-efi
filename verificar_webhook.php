<?php
/**
 * Script para verificar status do webhook
 */

$bot_token = '8573849766:AAErNoIGk0D3m4o66r65sifKombG9cZuGKA';

echo "🔍 VERIFICANDO WEBHOOK DO BOT\n";
echo "=====================================\n\n";

// 1. Verificar informações do webhook
$url = "https://api.telegram.org/bot{$bot_token}/getWebhookInfo";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$info = json_decode($response, true);

echo "📊 STATUS DO WEBHOOK:\n";
echo "URL: " . ($info['result']['url'] ?? 'Não configurado') . "\n";
echo "Certificado: " . ($info['result']['has_custom_certificate'] ? 'Sim' : 'Não') . "\n";
echo "Updates pendentes: " . ($info['result']['pending_update_count'] ?? 0) . "\n";
echo "IP: " . ($info['result']['ip_address'] ?? 'N/A') . "\n\n";

if (isset($info['result']['last_error_date'])) {
    echo "⚠️ ÚLTIMO ERRO:\n";
    echo "Data: " . date('Y-m-d H:i:s', $info['result']['last_error_date']) . "\n";
    echo "Mensagem: " . $info['result']['last_error_message'] . "\n\n";
}

// 2. Testar se o bot está ativo
echo "🤖 TESTANDO BOT:\n";
$url = "https://api.telegram.org/bot{$bot_token}/getMe";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$bot = json_decode($response, true);

if ($bot['ok']) {
    echo "✅ Bot está ativo\n";
    echo "Nome: " . $bot['result']['first_name'] . "\n";
    echo "Username: @" . $bot['result']['username'] . "\n\n";
} else {
    echo "❌ Erro ao verificar bot\n\n";
}

// 3. Verificar updates pendentes
echo "📬 UPDATES PENDENTES:\n";
$url = "https://api.telegram.org/bot{$bot_token}/getUpdates?limit=5";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$updates = json_decode($response, true);

if (!empty($updates['result'])) {
    echo "Total de updates: " . count($updates['result']) . "\n";
    foreach ($updates['result'] as $update) {
        $update_id = $update['update_id'];
        $msg = $update['message']['text'] ?? 'N/A';
        $chat_id = $update['message']['chat']['id'] ?? 'N/A';
        echo "  - Update #{$update_id}: {$msg} de {$chat_id}\n";
    }
} else {
    echo "Nenhum update pendente\n";
}

echo "\n=====================================\n";
echo "✅ Verificação concluída!\n";
?>
