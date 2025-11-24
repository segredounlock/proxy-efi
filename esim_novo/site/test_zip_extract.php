<?php
/**
 * Script de Teste: Extração de Números de Nomes de Arquivos
 * 
 * Testa a função extractNumberFromFilename() com vários padrões
 */

function extractNumberFromFilename(string $filename): ?string {
    // Remove extensão
    $name = pathinfo($filename, PATHINFO_FILENAME);
    
    // Extrai apenas dígitos (remove parênteses, espaços, etc)
    preg_match_all('/\d+/', $name, $matches);
    
    if (!empty($matches[0])) {
        // Concatena todos os números encontrados
        return implode('', $matches[0]);
    }
    
    return null;
}

// Casos de teste
$testCases = [
    '(12)987048218.png' => '12987048218',
    '17996732234.jpg' => '17996732234',
    'qr_11999887766.png' => '11999887766',
    'IMG_21987654321_final.png' => '21987654321',
    '(14)91234-5678.png' => '14912345678',
    'numero (15)98888-7777.jpg' => '15988887777',
    'QR_CODE_13_98765_4321.png' => '13987654321',
    'semNumeros.png' => null,
    'arquivo_teste.jpg' => null,
    '(11) 9 8888-7777.png' => '1198887777',
];

echo "<!DOCTYPE html>\n";
echo "<html lang='pt-BR'>\n";
echo "<head>\n";
echo "  <meta charset='UTF-8'>\n";
echo "  <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "  <title>Teste: Extração de Números</title>\n";
echo "  <style>\n";
echo "    body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 20px; }\n";
echo "    h1 { color: #667eea; }\n";
echo "    table { width: 100%; border-collapse: collapse; margin-top: 20px; }\n";
echo "    th, td { border: 1px solid #334155; padding: 12px; text-align: left; }\n";
echo "    th { background: #1e293b; color: #a78bfa; }\n";
echo "    tr:hover { background: #1e293b; }\n";
echo "    .success { color: #10b981; font-weight: bold; }\n";
echo "    .error { color: #ef4444; font-weight: bold; }\n";
echo "    .warning { color: #f59e0b; font-weight: bold; }\n";
echo "    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.875rem; }\n";
echo "    .badge-success { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid #10b981; }\n";
echo "    .badge-error { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; }\n";
echo "  </style>\n";
echo "</head>\n";
echo "<body>\n";

echo "<h1>🧪 Teste de Extração de Números</h1>\n";
echo "<p>Testando a função <code>extractNumberFromFilename()</code> com " . count($testCases) . " casos.</p>\n";

echo "<table>\n";
echo "  <thead>\n";
echo "    <tr>\n";
echo "      <th>#</th>\n";
echo "      <th>Nome do Arquivo</th>\n";
echo "      <th>Número Esperado</th>\n";
echo "      <th>Número Extraído</th>\n";
echo "      <th>Status</th>\n";
echo "    </tr>\n";
echo "  </thead>\n";
echo "  <tbody>\n";

$passed = 0;
$failed = 0;
$index = 1;

foreach ($testCases as $filename => $expected) {
    $result = extractNumberFromFilename($filename);
    $success = ($result === $expected);
    
    if ($success) {
        $passed++;
        $statusClass = 'badge-success';
        $statusText = '✅ PASSOU';
    } else {
        $failed++;
        $statusClass = 'badge-error';
        $statusText = '❌ FALHOU';
    }
    
    echo "  <tr>\n";
    echo "    <td>{$index}</td>\n";
    echo "    <td><code>{$filename}</code></td>\n";
    echo "    <td><code>" . ($expected ?? '<em>null</em>') . "</code></td>\n";
    echo "    <td><code>" . ($result ?? '<em>null</em>') . "</code></td>\n";
    echo "    <td><span class='badge {$statusClass}'>{$statusText}</span></td>\n";
    echo "  </tr>\n";
    
    $index++;
}

echo "  </tbody>\n";
echo "</table>\n";

echo "<h2 style='margin-top: 30px;'>📊 Resultado do Teste</h2>\n";
echo "<div style='background: #1e293b; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;'>\n";
echo "  <p><strong>Total de Testes:</strong> " . count($testCases) . "</p>\n";
echo "  <p class='success'><strong>✅ Passou:</strong> {$passed}</p>\n";
echo "  <p class='error'><strong>❌ Falhou:</strong> {$failed}</p>\n";
echo "  <p><strong>Taxa de Sucesso:</strong> " . round(($passed / count($testCases)) * 100, 2) . "%</p>\n";
echo "</div>\n";

if ($failed === 0) {
    echo "<div style='background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; padding: 20px; border-radius: 8px; margin-top: 20px;'>\n";
    echo "  <h3 style='color: #10b981; margin: 0;'>🎉 Todos os testes passaram!</h3>\n";
    echo "  <p style='margin: 10px 0 0 0;'>A função está funcionando corretamente para todos os casos testados.</p>\n";
    echo "</div>\n";
} else {
    echo "<div style='background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; padding: 20px; border-radius: 8px; margin-top: 20px;'>\n";
    echo "  <h3 style='color: #ef4444; margin: 0;'>⚠️ Alguns testes falharam</h3>\n";
    echo "  <p style='margin: 10px 0 0 0;'>Revise os casos que falharam e ajuste a função conforme necessário.</p>\n";
    echo "</div>\n";
}

echo "<div style='margin-top: 30px; padding: 20px; background: #1e293b; border-radius: 8px;'>\n";
echo "  <h3 style='color: #a78bfa;'>💡 Como Funciona</h3>\n";
echo "  <pre style='background: #0f172a; padding: 15px; border-radius: 6px; overflow-x: auto;'>";
echo htmlspecialchars('
function extractNumberFromFilename(string $filename): ?string {
    // Remove extensão
    $name = pathinfo($filename, PATHINFO_FILENAME);
    
    // Extrai apenas dígitos (remove parênteses, espaços, etc)
    preg_match_all(\'/\d+/\', $name, $matches);
    
    if (!empty($matches[0])) {
        // Concatena todos os números encontrados
        return implode(\'\', $matches[0]);
    }
    
    return null;
}
');
echo "</pre>\n";
echo "  <p><strong>Regex:</strong> <code>/\d+/</code> - Busca sequências de dígitos</p>\n";
echo "  <p><strong>Funcionamento:</strong> Remove extensão, extrai todos os grupos de números e concatena</p>\n";
echo "</div>\n";

echo "</body>\n";
echo "</html>\n";
