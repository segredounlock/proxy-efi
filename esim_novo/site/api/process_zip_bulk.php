<?php
/**
 * API: Processamento de Upload em Massa via ZIP
 * 
 * Aceita arquivos ZIP contendo QR codes em pastas ou diretamente na raiz.
 * Extrai números dos nomes dos arquivos e atualiza registros existentes no banco.
 * 
 * Exemplo de estrutura do ZIP:
 * - pasta1/(12)987048218.png
 * - pasta2/17996732234.jpg
 * - (11)999887766.png
 * 
 * Funcionamento:
 * 1. Extrai o ZIP para diretório temporário
 * 2. Varre recursivamente todas as pastas em busca de imagens
 * 3. Extrai números dos nomes dos arquivos usando regex
 * 4. Busca registros na tabela `esims` que tenham code_text vazio ou null
 * 5. Atualiza o campo code_text com o número extraído
 * 6. Move o arquivo QR para a pasta uploads/qr/
 * 7. Retorna relatório detalhado com sucesso/falhas
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

// Função auxiliar para remover diretório recursivamente
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? rrmdir($path) : unlink($path);
    }
    rmdir($dir);
}

// Função para extrair números de um nome de arquivo
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

// Função para log de ações
function writeLog(string $action, array $data): void {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    
    $logFile = $logDir . '/zip_bulk_upload.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = sprintf(
        "[%s] %s: %s\n",
        $timestamp,
        $action,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    
    @file_put_contents($logFile, $message, FILE_APPEND);
}

$output = ['ok' => false, 'message' => '', 'results' => []];

try {
    // Validação do método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido. Use POST.');
    }
    
    // Validação do arquivo ZIP
    if (!isset($_FILES['zip']) || $_FILES['zip']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Arquivo ZIP não enviado ou com erro.');
    }
    
    $zipFile = $_FILES['zip'];
    
    // Valida extensão
    $ext = strtolower(pathinfo($zipFile['name'], PATHINFO_EXTENSION));
    if ($ext !== 'zip') {
        throw new Exception('Apenas arquivos ZIP são permitidos.');
    }
    
    // Valida tamanho (máximo 100MB)
    if ($zipFile['size'] > 100 * 1024 * 1024) {
        throw new Exception('Arquivo ZIP muito grande. Máximo: 100MB.');
    }
    
    // Obtém parâmetros
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    writeLog('ZIP_UPLOAD_START', [
        'filename' => $zipFile['name'],
        'size' => $zipFile['size'],
        'product_id' => $productId
    ]);
    
    // Cria diretório temporário para extração
    $tempDir = sys_get_temp_dir() . '/zip_bulk_' . uniqid() . '/';
    if (!mkdir($tempDir, 0775, true)) {
        throw new Exception('Não foi possível criar diretório temporário.');
    }
    
    // Extrai o ZIP
    $zip = new ZipArchive();
    if ($zip->open($zipFile['tmp_name']) !== true) {
        rrmdir($tempDir);
        throw new Exception('Não foi possível abrir o arquivo ZIP.');
    }
    
    $zip->extractTo($tempDir);
    $totalFiles = $zip->numFiles;
    $zip->close();
    
    writeLog('ZIP_EXTRACTED', [
        'temp_dir' => $tempDir,
        'total_files' => $totalFiles
    ]);
    
    // Conecta ao banco de dados
    $pdo = db();
    
    // Diretório de destino para QR codes
    $uploadDir = __DIR__ . '/../uploads/qr/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    
    // Resultados do processamento
    $results = [
        'total_files' => 0,
        'processed' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'details' => []
    ];
    
    // Varre recursivamente o diretório extraído
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        
        $filename = $file->getFilename();
        $filepath = $file->getPathname();
        
        // Valida se é imagem
        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($fileExt, ['png', 'jpg', 'jpeg', 'gif'])) {
            continue; // Ignora arquivos que não são imagens
        }
        
        $results['total_files']++;
        
        // Extrai número do nome do arquivo
        $extractedNumber = extractNumberFromFilename($filename);
        
        if (!$extractedNumber) {
            $results['skipped']++;
            $results['details'][] = [
                'filename' => $filename,
                'status' => 'skipped',
                'reason' => 'Nenhum número encontrado no nome do arquivo'
            ];
            continue;
        }
        
        $results['processed']++;
        
        try {
            // Busca registro na tabela esims que tenha code_text vazio ou null
            // E que não tenha QR já atribuído (ou que seja do mesmo produto)
            $query = "SELECT id, product_id, code_text, qr_path 
                      FROM esims 
                      WHERE (code_text IS NULL OR code_text = '' OR qr_path IS NULL OR qr_path = '')
                      ORDER BY id ASC 
                      LIMIT 1";
            
            if ($productId > 0) {
                $query = "SELECT id, product_id, code_text, qr_path 
                          FROM esims 
                          WHERE product_id = ? 
                          AND (code_text IS NULL OR code_text = '' OR qr_path IS NULL OR qr_path = '')
                          ORDER BY id ASC 
                          LIMIT 1";
            }
            
            $stmt = $pdo->prepare($query);
            if ($productId > 0) {
                $stmt->execute([$productId]);
            } else {
                $stmt->execute();
            }
            
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                $results['skipped']++;
                $results['details'][] = [
                    'filename' => $filename,
                    'number' => $extractedNumber,
                    'status' => 'skipped',
                    'reason' => 'Nenhum registro disponível no banco de dados'
                ];
                continue;
            }
            
            // Gera nome único para o arquivo QR
            $newFilename = 'qr_' . uniqid() . '_' . $extractedNumber . '.' . $fileExt;
            $destPath = $uploadDir . $newFilename;
            $destPathRel = '/uploads/qr/' . $newFilename;
            
            // Copia o arquivo para o diretório de uploads
            if (!copy($filepath, $destPath)) {
                throw new Exception('Falha ao copiar arquivo');
            }
            
            // Atualiza o registro no banco de dados
            $updateStmt = $pdo->prepare("
                UPDATE esims 
                SET code_text = ?, qr_path = ? 
                WHERE id = ?
            ");
            
            $updateStmt->execute([$extractedNumber, $destPathRel, $record['id']]);
            
            $results['updated']++;
            $results['details'][] = [
                'filename' => $filename,
                'number' => $extractedNumber,
                'esim_id' => $record['id'],
                'qr_path' => $destPathRel,
                'status' => 'success',
                'message' => 'QR code atualizado com sucesso'
            ];
            
            writeLog('RECORD_UPDATED', [
                'esim_id' => $record['id'],
                'number' => $extractedNumber,
                'filename' => $filename,
                'qr_path' => $destPathRel
            ]);
            
        } catch (Exception $e) {
            $results['errors']++;
            $results['details'][] = [
                'filename' => $filename,
                'number' => $extractedNumber,
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            
            writeLog('RECORD_ERROR', [
                'filename' => $filename,
                'number' => $extractedNumber,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    // Remove diretório temporário
    rrmdir($tempDir);
    
    writeLog('ZIP_PROCESSING_COMPLETE', $results);
    
    $output = [
        'ok' => true,
        'message' => sprintf(
            'Processamento concluído: %d arquivos processados, %d atualizados, %d ignorados, %d erros',
            $results['processed'],
            $results['updated'],
            $results['skipped'],
            $results['errors']
        ),
        'results' => $results
    ];
    
    http_response_code(200);
    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    writeLog('ERROR', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
