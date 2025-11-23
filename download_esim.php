<?php
/**
 * Download Page - eSIM Project v2.0
 */

$file = __DIR__ . '/esim_project_v2.0.tar.gz';

if (isset($_GET['download'])) {
    if (!file_exists($file)) {
        die('Arquivo não encontrado');
    }
    
    header('Content-Type: application/x-gzip');
    header('Content-Disposition: attachment; filename="esim_project_v2.0.tar.gz"');
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: no-cache');
    
    readfile($file);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download - eSIM Project v2.0</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .version {
            color: #666;
            font-size: 18px;
            margin-bottom: 30px;
        }
        .file-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        .file-info h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .label {
            color: #666;
            font-weight: 500;
        }
        .value {
            color: #333;
            font-weight: 600;
        }
        .features {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        .features h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .features ul {
            list-style: none;
        }
        .features li {
            padding: 8px 0;
            color: #666;
        }
        .features li:before {
            content: '✅ ';
            margin-right: 8px;
        }
        .download-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
            margin: 20px 0;
        }
        .download-btn:hover {
            transform: translateY(-2px);
        }
        .note {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            color: #856404;
            margin-top: 20px;
            font-size: 14px;
        }
        .code {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            text-align: left;
            margin-top: 20px;
            font-size: 13px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 eSIM Project</h1>
        <p class="version">Versão 2.0 - Organizado e Documentado</p>
        
        <div class="file-info">
            <h3>📦 Informações do Pacote</h3>
            <div class="info-item">
                <span class="label">Arquivo:</span>
                <span class="value">esim_project_v2.0.tar.gz</span>
            </div>
            <div class="info-item">
                <span class="label">Tamanho:</span>
                <span class="value"><?php echo number_format(filesize($file)/1024/1024, 2); ?> MB</span>
            </div>
            <div class="info-item">
                <span class="label">Arquivos:</span>
                <span class="value">122 arquivos</span>
            </div>
            <div class="info-item">
                <span class="label">Data:</span>
                <span class="value"><?php echo date('d/m/Y H:i', filemtime($file)); ?></span>
            </div>
        </div>

        <div class="features">
            <h3>✨ Inclui:</h3>
            <ul>
                <li>Bot Telegram (Node.js)</li>
                <li>Backend PHP (API + Admin)</li>
                <li>Banco de Dados MySQL</li>
                <li>Documentação completa</li>
                <li>Scripts de setup</li>
                <li>.htaccess seguro</li>
            </ul>
        </div>

        <a href="?download=1" class="download-btn">
            ⬇️ BAIXAR PROJETO
        </a>

        <div class="note">
            <strong>⚠️ Após baixar:</strong><br>
            1. Extraia: <code>tar -xzf esim_project_v2.0.tar.gz</code><br>
            2. Leia: <code>README.md</code><br>
            3. Execute: <code>scripts/setup.sh</code>
        </div>

        <div class="code">
# Comandos de instalação:
tar -xzf esim_project_v2.0.tar.gz
cd esim_project
cat README.md
cd scripts
./setup.sh
        </div>
    </div>
</body>
</html>
