<?php
/**
 * TESTE DE ACESSO AO WEBHOOK
 * Verifica se o servidor está permitindo acesso ao arquivo
 */

header('Content-Type: application/json');
http_response_code(200);

$response = [
    'status' => 'OK',
    'message' => 'Webhook está acessível!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown'
    ],
    'files_accessible' => []
];

// Verificar quais arquivos existem e são acessíveis
$webhook_files = [
    'api_telegram.php',
    'api_telegram_FINAL.php',
    'bot_unico_completo.php',
    'webhook.php'
];

foreach ($webhook_files as $file) {
    $path = __DIR__ . '/' . $file;
    $response['files_accessible'][$file] = [
        'exists' => file_exists($path),
        'readable' => file_exists($path) && is_readable($path),
        'size' => file_exists($path) ? filesize($path) : 0
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
