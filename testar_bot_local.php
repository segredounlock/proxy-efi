<?php
/**
 * Script para testar o bot localmente
 * Simula uma chamada do Telegram
 */

echo "🧪 TESTANDO BOT LOCALMENTE\n";
echo "=====================================\n\n";

// Simular um update do Telegram
$fake_update = [
    'update_id' => 999999999,
    'message' => [
        'message_id' => 1,
        'from' => [
            'id' => 1901426549,
            'is_bot' => false,
            'first_name' => 'Admin',
            'username' => 'admin_test'
        ],
        'chat' => [
            'id' => 1901426549,
            'first_name' => 'Admin',
            'username' => 'admin_test',
            'type' => 'private'
        ],
        'date' => time(),
        'text' => '/start'
    ]
];

// Salvar em arquivo temporário para simular entrada
$input_file = __DIR__ . '/test_input.json';
file_put_contents($input_file, json_encode($fake_update));

echo "📝 Update simulado criado\n";
echo "Comando: /start\n";
echo "Chat ID: 1901426549 (Admin)\n\n";

echo "🔄 Executando webhook...\n\n";

// Verificar se arquivo existe
if (!file_exists(__DIR__ . '/bot_unico_completo.php')) {
    echo "❌ ERRO: bot_unico_completo.php não encontrado!\n";
    echo "Verifique se o arquivo está na mesma pasta.\n";
    exit(1);
}

// Simular entrada via stdin
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Capturar output
ob_start();

// Definir input simulado
file_put_contents('php://input', json_encode($fake_update));

try {
    // Incluir o bot
    include __DIR__ . '/bot_unico_completo.php';
    
    echo "\n✅ Bot executado sem erros de sintaxe\n";
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();

echo "\n📤 OUTPUT DO BOT:\n";
echo "-----------------------------------\n";
echo $output;
echo "\n-----------------------------------\n";

// Limpar arquivo temporário
@unlink($input_file);

echo "\n✅ Teste concluído!\n";
echo "\n💡 PRÓXIMOS PASSOS:\n";
echo "1. Se não houver erros, o bot está funcionando\n";
echo "2. Verifique os logs em bot_logs/\n";
echo "3. Teste no servidor real\n";
?>
