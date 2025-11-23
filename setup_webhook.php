<?php
/**
 * CONFIGURADOR DE WEBHOOK DO TELEGRAM
 * Atualiza a URL do webhook para o arquivo correto
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurações
$BOT_TOKEN = '8573849766:AAErNoIGk0D3m4o66r65sifKombG9cZuGKA';
$BASE_URL = 'https://segredounlock.com/a12bot/';

// Arquivos webhook disponíveis
$webhook_files = [
    'api_telegram_FINAL.php' => 'Bot FINAL com Auto-Gift (RECOMENDADO)',
    'api_telegram.php' => 'Bot atual',
    'bot_unico_completo.php' => 'Bot anterior'
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurador de Webhook</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .status-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        .status-box h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .status-item:last-child { border-bottom: none; }
        .status-label {
            color: #666;
            font-weight: 500;
        }
        .status-value {
            color: #333;
            font-family: monospace;
        }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .webhook-options {
            margin-bottom: 30px;
        }
        .webhook-option {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .webhook-option:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        .webhook-option.selected {
            border-color: #667eea;
            background: #e8efff;
        }
        .webhook-option h4 {
            color: #333;
            margin-bottom: 5px;
        }
        .webhook-option p {
            color: #666;
            font-size: 14px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .result {
            margin-top: 20px;
            padding: 20px;
            border-radius: 10px;
            display: none;
        }
        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 Configurador de Webhook</h1>
        <p class="subtitle">Configure o webhook do seu bot Telegram</p>

        <?php
        // Obter informações atuais do webhook
        $webhook_info_url = "https://api.telegram.org/bot{$BOT_TOKEN}/getWebhookInfo";
        $webhook_info = @json_decode(file_get_contents($webhook_info_url), true);
        
        if ($webhook_info && $webhook_info['ok']) {
            $info = $webhook_info['result'];
            ?>
            <div class="status-box">
                <h3>📊 Status Atual do Webhook</h3>
                <div class="status-item">
                    <span class="status-label">URL Atual:</span>
                    <span class="status-value"><?php echo htmlspecialchars($info['url'] ?? 'Não configurado'); ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Status:</span>
                    <span class="status-value <?php echo empty($info['last_error_message']) ? 'status-ok' : 'status-error'; ?>">
                        <?php echo empty($info['last_error_message']) ? '✅ OK' : '❌ Com Erro'; ?>
                    </span>
                </div>
                <?php if (!empty($info['last_error_message'])): ?>
                <div class="status-item">
                    <span class="status-label">Último Erro:</span>
                    <span class="status-value status-error"><?php echo htmlspecialchars($info['last_error_message']); ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Data do Erro:</span>
                    <span class="status-value"><?php echo date('d/m/Y H:i:s', $info['last_error_date']); ?></span>
                </div>
                <?php endif; ?>
                <div class="status-item">
                    <span class="status-label">Updates Pendentes:</span>
                    <span class="status-value"><?php echo $info['pending_update_count'] ?? 0; ?></span>
                </div>
            </div>
            <?php
        }
        ?>

        <form id="webhookForm" method="POST">
            <div class="webhook-options">
                <h3 style="margin-bottom: 15px; color: #333;">Escolha o Arquivo Webhook:</h3>
                <?php foreach ($webhook_files as $file => $description): ?>
                <label class="webhook-option">
                    <input type="radio" name="webhook_file" value="<?php echo $file; ?>" 
                           <?php echo $file === 'api_telegram_FINAL.php' ? 'checked' : ''; ?>
                           style="display: none;">
                    <h4>📄 <?php echo $file; ?></h4>
                    <p><?php echo $description; ?></p>
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn">🚀 Atualizar Webhook</button>
        </form>

        <div id="result" class="result"></div>
    </div>

    <script>
        // Seleção visual de opções
        document.querySelectorAll('.webhook-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.webhook-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Marcar opção inicial
        document.querySelector('input[name="webhook_file"]:checked').closest('.webhook-option').classList.add('selected');

        // Submissão do formulário
        document.getElementById('webhookForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const webhookFile = formData.get('webhook_file');
            const resultDiv = document.getElementById('result');
            const btn = this.querySelector('button');
            
            btn.disabled = true;
            btn.textContent = '⏳ Atualizando...';
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=set_webhook&webhook_file=' + encodeURIComponent(webhookFile)
                });
                
                const data = await response.json();
                
                resultDiv.className = 'result ' + (data.success ? 'success' : 'error');
                resultDiv.innerHTML = `
                    <h3>${data.success ? '✅ Sucesso!' : '❌ Erro'}</h3>
                    <p>${data.message}</p>
                    ${data.response ? '<div class="code">' + JSON.stringify(data.response, null, 2) + '</div>' : ''}
                `;
                resultDiv.style.display = 'block';
                
                if (data.success) {
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = '<h3>❌ Erro</h3><p>' + error.message + '</p>';
                resultDiv.style.display = 'block';
            }
            
            btn.disabled = false;
            btn.textContent = '🚀 Atualizar Webhook';
        });
    </script>
</body>
</html>

<?php
// Processar requisição de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_webhook') {
    header('Content-Type: application/json');
    
    $webhook_file = $_POST['webhook_file'] ?? '';
    
    if (!isset($webhook_files[$webhook_file])) {
        echo json_encode([
            'success' => false,
            'message' => 'Arquivo inválido'
        ]);
        exit;
    }
    
    $webhook_url = $BASE_URL . $webhook_file;
    $set_webhook_url = "https://api.telegram.org/bot{$BOT_TOKEN}/setWebhook?url=" . urlencode($webhook_url);
    
    $response = @file_get_contents($set_webhook_url);
    $result = @json_decode($response, true);
    
    if ($result && $result['ok']) {
        echo json_encode([
            'success' => true,
            'message' => "Webhook atualizado com sucesso para: {$webhook_file}",
            'response' => $result
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao atualizar webhook',
            'response' => $result
        ]);
    }
    exit;
}
?>
