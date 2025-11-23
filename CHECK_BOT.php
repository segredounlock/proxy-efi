<?php
/**
 * DIAGNÓSTICO SIMPLIFICADO DO BOT
 * Execute este arquivo no navegador ou via CLI
 */

header('Content-Type: text/plain; charset=utf-8');

echo "====================================\n";
echo "   DIAGNÓSTICO DO BOT TELEGRAM\n";
echo "====================================\n\n";

$bot_token = '8573849766:AAErNoIGk0D3m4o66r65sifKombG9cZuGKA';
$webhook_file = 'bot_unico_completo.php';
$errors = [];
$warnings = [];

// 1. VERIFICAR SE ARQUIVO EXISTE
echo "1️⃣ VERIFICANDO ARQUIVO DO BOT...\n";
if (file_exists($webhook_file)) {
    $size = filesize($webhook_file);
    echo "   ✅ Arquivo encontrado: {$webhook_file} (" . round($size/1024) . " KB)\n";
    
    if ($size < 100000) {
        $warnings[] = "Arquivo muito pequeno (esperado ~110KB)";
        echo "   ⚠️ AVISO: Arquivo parece incompleto\n";
    }
    
    // Verificar permissões
    $perms = substr(sprintf('%o', fileperms($webhook_file)), -3);
    echo "   Permissões: {$perms}\n";
    
    if (!is_readable($webhook_file)) {
        $errors[] = "Arquivo não pode ser lido";
        echo "   ❌ ERRO: Arquivo não é legível\n";
    }
} else {
    $errors[] = "Arquivo {$webhook_file} não encontrado";
    echo "   ❌ ERRO: Arquivo não existe!\n";
}

// 2. VERIFICAR PASTAS
echo "\n2️⃣ VERIFICANDO PASTAS NECESSÁRIAS...\n";
$folders = ['bot_data', 'bot_logs'];
foreach ($folders as $folder) {
    if (is_dir($folder)) {
        echo "   ✅ Pasta {$folder}/ existe\n";
        if (!is_writable($folder)) {
            $errors[] = "Pasta {$folder} não é gravável";
            echo "   ❌ ERRO: Sem permissão de escrita!\n";
        }
    } else {
        echo "   ⚠️ Pasta {$folder}/ não existe - será criada automaticamente\n";
    }
}

// 3. VERIFICAR .HTACCESS
echo "\n3️⃣ VERIFICANDO .HTACCESS...\n";
if (file_exists('.htaccess')) {
    echo "   ✅ Arquivo .htaccess existe\n";
    $htaccess_content = file_get_contents('.htaccess');
    if (strpos($htaccess_content, 'SecRuleEngine Off') !== false) {
        echo "   ✅ ModSecurity desabilitado\n";
    } else {
        $warnings[] = ".htaccess não desabilita ModSecurity";
        echo "   ⚠️ ModSecurity pode estar bloqueando\n";
    }
} else {
    $warnings[] = "Arquivo .htaccess não encontrado";
    echo "   ⚠️ AVISO: .htaccess não encontrado\n";
    echo "   💡 Isso pode causar erro 403 Forbidden\n";
}

// 4. TESTAR CONEXÃO COM TELEGRAM
echo "\n4️⃣ TESTANDO CONEXÃO COM TELEGRAM API...\n";
$url = "https://api.telegram.org/bot{$bot_token}/getMe";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200 && $response) {
    $data = json_decode($response, true);
    if (isset($data['ok']) && $data['ok']) {
        echo "   ✅ Bot está ativo: @{$data['result']['username']}\n";
    } else {
        $errors[] = "Bot não respondeu corretamente";
        echo "   ❌ ERRO: Resposta inválida da API\n";
    }
} else {
    $errors[] = "Não foi possível conectar ao Telegram";
    echo "   ❌ ERRO: Falha na conexão (HTTP {$http_code})\n";
}

// 5. VERIFICAR STATUS DO WEBHOOK
echo "\n5️⃣ VERIFICANDO WEBHOOK CONFIGURADO...\n";
$url = "https://api.telegram.org/bot{$bot_token}/getWebhookInfo";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $data = json_decode($response, true);
    if (isset($data['result'])) {
        $result = $data['result'];
        
        echo "   URL: " . ($result['url'] ?: '❌ NÃO CONFIGURADO') . "\n";
        echo "   Pending updates: " . $result['pending_update_count'] . "\n";
        
        if (empty($result['url'])) {
            $errors[] = "Webhook não está configurado";
            echo "   ❌ ERRO: Webhook não configurado!\n";
            echo "   💡 Configure com: setWebhook?url=https://SEU_DOMINIO.com/a12/bot_unico_completo.php\n";
        }
        
        if (isset($result['last_error_date'])) {
            $errors[] = "Webhook com erro: " . $result['last_error_message'];
            echo "\n   🚨 ÚLTIMO ERRO DO WEBHOOK:\n";
            echo "   Data: " . date('Y-m-d H:i:s', $result['last_error_date']) . "\n";
            echo "   Erro: " . $result['last_error_message'] . "\n";
            
            // Identificar tipo de erro
            $error_msg = $result['last_error_message'];
            if (strpos($error_msg, '403') !== false) {
                echo "\n   💡 SOLUÇÃO PARA 403 FORBIDDEN:\n";
                echo "   1. Criar arquivo .htaccess com:\n";
                echo "      SecRuleEngine Off\n";
                echo "      Require all granted\n";
                echo "   2. Verificar permissões do arquivo (644)\n";
                echo "   3. Verificar se ModSecurity não está bloqueando\n";
            } elseif (strpos($error_msg, '404') !== false) {
                echo "\n   💡 SOLUÇÃO PARA 404 NOT FOUND:\n";
                echo "   1. Verificar se arquivo está no caminho correto\n";
                echo "   2. URL do webhook deve apontar para: bot_unico_completo.php\n";
            } elseif (strpos($error_msg, '500') !== false) {
                echo "\n   💡 SOLUÇÃO PARA 500 INTERNAL ERROR:\n";
                echo "   1. Verificar logs de erro do PHP\n";
                echo "   2. Verificar sintaxe do arquivo PHP\n";
                echo "   3. Verificar permissões das pastas bot_data/ e bot_logs/\n";
            }
        } else {
            echo "   ✅ Sem erros recentes\n";
        }
        
        if ($result['pending_update_count'] > 0) {
            $warnings[] = "{$result['pending_update_count']} atualizações pendentes";
            echo "   ⚠️ Existem {$result['pending_update_count']} mensagens aguardando processamento\n";
        }
    }
}

// 6. TESTAR ACESSO HTTP AO WEBHOOK
echo "\n6️⃣ TESTANDO ACESSO HTTP AO ARQUIVO...\n";
$current_domain = $_SERVER['HTTP_HOST'] ?? 'SEU_DOMINIO';
$current_path = dirname($_SERVER['PHP_SELF']);
$webhook_url = "https://{$current_domain}{$current_path}/{$webhook_file}";

echo "   Testando: {$webhook_url}\n";

$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    echo "   ✅ Arquivo acessível via HTTP (200 OK)\n";
} elseif ($http_code == 403) {
    $errors[] = "Erro 403 Forbidden ao acessar webhook";
    echo "   ❌ ERRO 403 FORBIDDEN!\n";
    echo "   💡 Servidor está bloqueando o acesso\n";
    echo "   💡 Solução: Criar/corrigir .htaccess\n";
} elseif ($http_code == 404) {
    $errors[] = "Erro 404 Not Found ao acessar webhook";
    echo "   ❌ ERRO 404 NOT FOUND!\n";
    echo "   💡 Arquivo não foi encontrado no caminho testado\n";
} elseif ($http_code == 500) {
    $errors[] = "Erro 500 Internal Server Error";
    echo "   ❌ ERRO 500 INTERNAL SERVER ERROR!\n";
    echo "   💡 Há um erro de PHP no arquivo\n";
} else {
    echo "   ⚠️ HTTP Status: {$http_code}\n";
}

// 7. VERIFICAR PHP
echo "\n7️⃣ AMBIENTE PHP...\n";
echo "   Versão: " . phpversion() . "\n";
echo "   cURL: " . (function_exists('curl_init') ? '✅' : '❌') . "\n";
echo "   JSON: " . (function_exists('json_encode') ? '✅' : '❌') . "\n";

// RESUMO FINAL
echo "\n====================================\n";
echo "   RESUMO DO DIAGNÓSTICO\n";
echo "====================================\n\n";

if (empty($errors)) {
    echo "✅ NENHUM ERRO CRÍTICO DETECTADO!\n\n";
    
    if (!empty($warnings)) {
        echo "⚠️ AVISOS ({$count_warnings} total):\n";
        foreach ($warnings as $i => $warning) {
            echo "   " . ($i+1) . ". {$warning}\n";
        }
    }
    
    echo "\n💡 SE O BOT AINDA NÃO FUNCIONA:\n";
    echo "   1. Verifique se o webhook está configurado corretamente\n";
    echo "   2. Envie /start para o bot no Telegram\n";
    echo "   3. Verifique os logs em bot_logs/debug.log\n";
    echo "   4. Teste acessar o webhook diretamente no navegador\n";
    
} else {
    echo "❌ ERROS ENCONTRADOS (" . count($errors) . " total):\n\n";
    foreach ($errors as $i => $error) {
        echo "   " . ($i+1) . ". {$error}\n";
    }
    
    echo "\n🔧 AÇÕES NECESSÁRIAS:\n\n";
    
    // Sugestões específicas baseadas nos erros
    $error_string = implode(' ', $errors);
    
    if (strpos($error_string, '403') !== false || strpos($error_string, '.htaccess') !== false) {
        echo "   📝 CRIAR ARQUIVO .htaccess:\n";
        echo "   ----------------------------------------\n";
        echo "   <IfModule mod_security.c>\n";
        echo "       SecRuleEngine Off\n";
        echo "   </IfModule>\n";
        echo "   \n";
        echo "   <Files \"bot_unico_completo.php\">\n";
        echo "       Require all granted\n";
        echo "   </Files>\n";
        echo "   ----------------------------------------\n\n";
    }
    
    if (strpos($error_string, 'não configurado') !== false) {
        echo "   🔗 CONFIGURAR WEBHOOK:\n";
        echo "   https://api.telegram.org/bot{$bot_token}/setWebhook?url={$webhook_url}\n\n";
    }
    
    if (strpos($error_string, 'não é gravável') !== false) {
        echo "   🔐 CORRIGIR PERMISSÕES:\n";
        echo "   chmod 755 bot_data bot_logs\n";
        echo "   chmod 644 bot_unico_completo.php\n\n";
    }
}

echo "\n====================================\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n";
echo "====================================\n";
