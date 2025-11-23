<?php
/**
 * SCRIPT DE TESTE PARA DIAGNÓSTICO DO BOT
 * Use este arquivo para testar a configuração
 */

// Configurações
define('BOT_TOKEN', '8573849766:AAErNoIGk0D3m4o66r65sifKombG9cZuGKA');

echo "=== TESTE DE CONFIGURAÇÃO DO BOT ===\n\n";

// Teste 1: Verificar BOT_TOKEN
echo "1. Token do Bot: " . (BOT_TOKEN ? "✅ Configurado" : "❌ Não configurado") . "\n";
echo "   Token: " . substr(BOT_TOKEN, 0, 20) . "...\n\n";

// Teste 2: Testar conexão com API do Telegram
echo "2. Testando conexão com API do Telegram...\n";
$url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getMe";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $data = json_decode($response, true);
    if ($data['ok']) {
        echo "   ✅ Conexão OK!\n";
        echo "   Bot: @" . $data['result']['username'] . "\n";
        echo "   Nome: " . $data['result']['first_name'] . "\n";
        echo "   ID: " . $data['result']['id'] . "\n\n";
    } else {
        echo "   ❌ Erro na resposta: " . ($data['description'] ?? 'Desconhecido') . "\n\n";
    }
} else {
    echo "   ❌ Erro HTTP: " . $http_code . "\n\n";
}

// Teste 3: Verificar diretórios
echo "3. Verificando diretórios...\n";
$dirs = [
    __DIR__ . '/bot_data',
    __DIR__ . '/bot_logs',
    __DIR__ . '/bot_data/backups'
];

foreach ($dirs as $dir) {
    if (file_exists($dir)) {
        echo "   ✅ " . basename($dir) . " - Existe\n";
    } else {
        echo "   ⚠️ " . basename($dir) . " - Não existe (será criado automaticamente)\n";
    }
}
echo "\n";

// Teste 4: Verificar permissões de escrita
echo "4. Testando permissões de escrita...\n";
$test_file = __DIR__ . '/bot_logs/teste_permissao.txt';
@mkdir(__DIR__ . '/bot_logs', 0755, true);
$result = @file_put_contents($test_file, "teste\n");
if ($result !== false) {
    echo "   ✅ Permissão de escrita OK\n";
    @unlink($test_file);
} else {
    echo "   ❌ Sem permissão de escrita em bot_logs/\n";
}
echo "\n";

// Teste 5: Verificar extensões PHP necessárias
echo "5. Verificando extensões PHP...\n";
$extensions = ['curl', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ {$ext}\n";
    } else {
        echo "   ❌ {$ext} - NÃO INSTALADA\n";
    }
}
echo "\n";

// Teste 6: Obter informações do webhook
echo "6. Verificando webhook...\n";
$url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getWebhookInfo";
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if ($data['ok']) {
    $info = $data['result'];
    echo "   URL: " . ($info['url'] ?? 'Não configurado') . "\n";
    echo "   Pending updates: " . ($info['pending_update_count'] ?? 0) . "\n";
    if (!empty($info['last_error_message'])) {
        echo "   ⚠️ Último erro: " . $info['last_error_message'] . "\n";
        echo "   ⚠️ Data do erro: " . date('d/m/Y H:i:s', $info['last_error_date']) . "\n";
    } else {
        echo "   ✅ Sem erros\n";
    }
} else {
    echo "   ❌ Erro ao obter info do webhook\n";
}
echo "\n";

echo "=== FIM DO TESTE ===\n";
?>
