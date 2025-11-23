<?php
/**
 * DIAGNÓSTICO COMPLETO DO BOT
 * Verifica todos os possíveis problemas
 */

echo "🔍 DIAGNÓSTICO COMPLETO DO BOT\n";
echo str_repeat("=", 60) . "\n\n";

$bot_token = '8573849766:AAErNoIGk0D3m4o66r65sifKombG9cZuGKA';
$webhook_url = 'https://buscalotter.com/a12/webhook.php';

// ==================== 1. VERIFICAR ARQUIVO WEBHOOK ====================
echo "1️⃣ VERIFICANDO ARQUIVO WEBHOOK\n";
echo str_repeat("-", 60) . "\n";

$webhook_file = __DIR__ . '/webhook.php';

if (file_exists($webhook_file)) {
    $size = filesize($webhook_file);
    $perms = substr(sprintf('%o', fileperms($webhook_file)), -4);
    echo "✅ webhook.php existe\n";
    echo "   Tamanho: " . number_format($size) . " bytes\n";
    echo "   Permissões: {$perms}\n";
    
    if ($size < 10000) {
        echo "   ⚠️ AVISO: Arquivo muito pequeno! Esperado ~110KB\n";
    }
    
    if ($perms != '0644') {
        echo "   ⚠️ AVISO: Permissões incorretas! Deve ser 644\n";
    }
} else {
    echo "❌ webhook.php NÃO EXISTE!\n";
    echo "   Solução: Envie o arquivo bot_unico_completo.php e renomeie\n";
}

echo "\n";

// ==================== 2. VERIFICAR PASTAS ====================
echo "2️⃣ VERIFICANDO PASTAS NECESSÁRIAS\n";
echo str_repeat("-", 60) . "\n";

$folders = [
    'bot_data' => __DIR__ . '/bot_data',
    'bot_logs' => __DIR__ . '/bot_logs'
];

foreach ($folders as $name => $path) {
    if (file_exists($path) && is_dir($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path) ? 'SIM' : 'NÃO';
        echo "✅ {$name}/ existe\n";
        echo "   Permissões: {$perms}\n";
        echo "   Gravável: {$writable}\n";
        
        if (!is_writable($path)) {
            echo "   ❌ ERRO: Pasta não gravável!\n";
        }
    } else {
        echo "❌ {$name}/ NÃO EXISTE!\n";
        echo "   Solução: mkdir {$name} && chmod 755 {$name}\n";
    }
}

echo "\n";

// ==================== 3. VERIFICAR .HTACCESS ====================
echo "3️⃣ VERIFICANDO .htaccess\n";
echo str_repeat("-", 60) . "\n";

$htaccess = __DIR__ . '/.htaccess';

if (file_exists($htaccess)) {
    $content = file_get_contents($htaccess);
    echo "✅ .htaccess existe\n";
    echo "   Tamanho: " . strlen($content) . " bytes\n";
    
    // Verificar conteúdo importante
    if (strpos($content, 'webhook.php') !== false) {
        echo "   ✅ Contém regras para webhook.php\n";
    } else {
        echo "   ⚠️ AVISO: Não contém regras para webhook.php\n";
    }
    
    if (strpos($content, 'Allow from all') !== false || strpos($content, 'Require all granted') !== false) {
        echo "   ✅ Permite acesso ao webhook\n";
    } else {
        echo "   ⚠️ AVISO: Pode estar bloqueando acesso\n";
    }
} else {
    echo "⚠️ .htaccess NÃO EXISTE\n";
    echo "   Isso pode causar erro 403 Forbidden\n";
    echo "   Solução: Criar .htaccess (ver documentação)\n";
}

echo "\n";

// ==================== 4. TESTAR SINTAXE PHP ====================
echo "4️⃣ TESTANDO SINTAXE DO ARQUIVO\n";
echo str_repeat("-", 60) . "\n";

if (file_exists($webhook_file)) {
    $output = [];
    $return = 0;
    exec("php -l " . escapeshellarg($webhook_file) . " 2>&1", $output, $return);
    
    if ($return === 0) {
        echo "✅ Sintaxe PHP válida\n";
    } else {
        echo "❌ ERRO DE SINTAXE PHP!\n";
        foreach ($output as $line) {
            echo "   {$line}\n";
        }
    }
} else {
    echo "⚠️ Arquivo não encontrado, pulando teste\n";
}

echo "\n";

// ==================== 5. VERIFICAR BOT NO TELEGRAM ====================
echo "5️⃣ VERIFICANDO BOT NO TELEGRAM\n";
echo str_repeat("-", 60) . "\n";

$url = "https://api.telegram.org/bot{$bot_token}/getMe";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $data = json_decode($response, true);
    if ($data['ok']) {
        echo "✅ Bot está ativo no Telegram\n";
        echo "   Nome: " . $data['result']['first_name'] . "\n";
        echo "   Username: @" . $data['result']['username'] . "\n";
        echo "   ID: " . $data['result']['id'] . "\n";
    } else {
        echo "❌ Erro na resposta do Telegram\n";
        echo "   " . ($data['description'] ?? 'Erro desconhecido') . "\n";
    }
} else {
    echo "❌ Não foi possível conectar ao Telegram\n";
    echo "   HTTP Code: {$http_code}\n";
}

echo "\n";

// ==================== 6. VERIFICAR WEBHOOK ====================
echo "6️⃣ VERIFICANDO CONFIGURAÇÃO DO WEBHOOK\n";
echo str_repeat("-", 60) . "\n";

$url = "https://api.telegram.org/bot{$bot_token}/getWebhookInfo";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);

$info = json_decode($response, true);

if ($info['ok']) {
    $result = $info['result'];
    
    echo "📊 Status do Webhook:\n";
    echo "   URL: " . ($result['url'] ?: '❌ NÃO CONFIGURADO') . "\n";
    echo "   Updates pendentes: " . $result['pending_update_count'] . "\n";
    
    if (!empty($result['url'])) {
        if ($result['url'] === $webhook_url) {
            echo "   ✅ URL correta\n";
        } else {
            echo "   ⚠️ URL diferente do esperado!\n";
            echo "      Esperado: {$webhook_url}\n";
            echo "      Atual: {$result['url']}\n";
        }
    } else {
        echo "   ❌ WEBHOOK NÃO CONFIGURADO!\n";
        echo "   Solução: Configure o webhook\n";
    }
    
    if (isset($result['last_error_date'])) {
        echo "\n   ⚠️ ÚLTIMO ERRO:\n";
        echo "   Data: " . date('Y-m-d H:i:s', $result['last_error_date']) . "\n";
        echo "   Mensagem: " . $result['last_error_message'] . "\n";
        
        if (strpos($result['last_error_message'], '403') !== false) {
            echo "\n   🚨 ERRO 403 FORBIDDEN!\n";
            echo "   Causa: Servidor bloqueando acesso ao webhook\n";
            echo "   Solução: Criar/corrigir arquivo .htaccess\n";
        }
        
        if (strpos($result['last_error_message'], '404') !== false) {
            echo "\n   🚨 ERRO 404 NOT FOUND!\n";
            echo "   Causa: Arquivo webhook.php não encontrado no servidor\n";
            echo "   Solução: Enviar arquivo para o servidor\n";
        }
        
        if (strpos($result['last_error_message'], '500') !== false) {
            echo "\n   🚨 ERRO 500 INTERNAL SERVER ERROR!\n";
            echo "   Causa: Erro de PHP no arquivo\n";
            echo "   Solução: Verificar logs de erro do servidor\n";
        }
    } else {
        echo "   ✅ Sem erros recentes\n";
    }
    
    if (isset($result['ip_address'])) {
        echo "   IP: " . $result['ip_address'] . "\n";
    }
} else {
    echo "❌ Erro ao verificar webhook\n";
}

echo "\n";

// ==================== 7. TESTAR ACESSO AO WEBHOOK ====================
echo "7️⃣ TESTANDO ACESSO DIRETO AO WEBHOOK\n";
echo str_repeat("-", 60) . "\n";

$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Testando: {$webhook_url}\n";
echo "HTTP Code: {$http_code}\n";

switch ($http_code) {
    case 200:
        echo "✅ Webhook acessível!\n";
        break;
    case 403:
        echo "❌ ERRO 403 FORBIDDEN!\n";
        echo "   Servidor está bloqueando acesso\n";
        echo "   Solução: Criar .htaccess correto\n";
        break;
    case 404:
        echo "❌ ERRO 404 NOT FOUND!\n";
        echo "   Arquivo não existe no servidor\n";
        echo "   Solução: Enviar webhook.php\n";
        break;
    case 500:
        echo "❌ ERRO 500 INTERNAL SERVER ERROR!\n";
        echo "   Erro no código PHP\n";
        echo "   Solução: Verificar logs de erro\n";
        break;
    default:
        echo "⚠️ Código HTTP inesperado: {$http_code}\n";
}

echo "\n";

// ==================== 8. VERIFICAR PHP ====================
echo "8️⃣ VERIFICANDO AMBIENTE PHP\n";
echo str_repeat("-", 60) . "\n";

echo "Versão PHP: " . PHP_VERSION . "\n";
echo "cURL: " . (function_exists('curl_init') ? '✅ Disponível' : '❌ Não disponível') . "\n";
echo "JSON: " . (function_exists('json_encode') ? '✅ Disponível' : '❌ Não disponível') . "\n";
echo "File Operations: " . (function_exists('file_get_contents') ? '✅ Disponível' : '❌ Não disponível') . "\n";

echo "\n";

// ==================== 9. RESUMO E RECOMENDAÇÕES ====================
echo "9️⃣ RESUMO E RECOMENDAÇÕES\n";
echo str_repeat("-", 60) . "\n";

$errors = [];
$warnings = [];

// Verificar arquivo
if (!file_exists($webhook_file)) {
    $errors[] = "webhook.php não existe";
} elseif (filesize($webhook_file) < 10000) {
    $warnings[] = "webhook.php muito pequeno";
}

// Verificar pastas
foreach ($folders as $name => $path) {
    if (!file_exists($path)) {
        $errors[] = "Pasta {$name}/ não existe";
    } elseif (!is_writable($path)) {
        $errors[] = "Pasta {$name}/ não gravável";
    }
}

// Verificar .htaccess
if (!file_exists($htaccess)) {
    $warnings[] = ".htaccess não existe (pode causar 403)";
}

// Verificar webhook
if (empty($result['url'])) {
    $errors[] = "Webhook não configurado";
}

if (isset($result['last_error_date'])) {
    $errors[] = "Webhook com erro: " . $result['last_error_message'];
}

if ($http_code == 403) {
    $errors[] = "Webhook bloqueado (403 Forbidden)";
}

if ($http_code == 404) {
    $errors[] = "Webhook não encontrado (404)";
}

echo "ERROS CRÍTICOS: " . count($errors) . "\n";
foreach ($errors as $i => $error) {
    echo "  " . ($i + 1) . ". ❌ {$error}\n";
}

echo "\n";

echo "AVISOS: " . count($warnings) . "\n";
foreach ($warnings as $i => $warning) {
    echo "  " . ($i + 1) . ". ⚠️ {$warning}\n";
}

echo "\n";

if (empty($errors)) {
    echo "🎉 NENHUM ERRO CRÍTICO DETECTADO!\n";
    echo "   O bot deveria estar funcionando.\n";
    echo "   Se não estiver, verifique os logs do servidor.\n";
} else {
    echo "🚨 CORRIJA OS ERROS ACIMA PARA O BOT FUNCIONAR!\n";
}

echo "\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Diagnóstico concluído!\n";
echo "\n";
echo "Para mais ajuda, consulte:\n";
echo "- DIAGNOSTICO_BOT_NAO_RESPONDE.md\n";
echo "- GUIA_INSTALACAO_ARQUIVO_UNICO.md\n";
?>
