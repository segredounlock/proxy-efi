<?php
require_once __DIR__ . '/../config.php'; 
require_login();
$pdo = db();
$products = $pdo->query("SELECT id, name FROM products ORDER BY name")->fetchAll();
include __DIR__ . '/_header_new.php';
?>

<style>
/* === MODERN UPLOADER STYLES === */
.upload-container {
  background: linear-gradient(135deg, rgba(10, 14, 26, 0.95), rgba(15, 23, 42, 0.95));
  border: 1px solid rgba(102, 126, 234, 0.2);
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(102, 126, 234, 0.1) inset;
  backdrop-filter: blur(10px);
}

.dropzone-multi {
  border: 3px dashed rgba(102, 126, 234, 0.4);
  border-radius: 16px;
  padding: 48px 24px;
  text-align: center;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.03), rgba(118, 75, 162, 0.03));
  transition: all 0.3s ease;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}

.dropzone-multi::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.dropzone-multi:hover::before,
.dropzone-multi.dragover::before {
  opacity: 1;
}

.dropzone-multi:hover {
  border-color: rgba(102, 126, 234, 0.8);
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
  box-shadow: 0 0 30px rgba(102, 126, 234, 0.2);
}

.dropzone-multi.dragover {
  border-color: #667eea;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
  transform: scale(1.02);
}

.dropzone-icon {
  font-size: 64px;
  color: #667eea;
  margin-bottom: 16px;
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

.dropzone-text {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.dropzone-subtext {
  font-size: 0.875rem;
  color: var(--text-secondary);
  margin-bottom: 20px;
}

.file-preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
  margin-top: 24px;
}

.file-card {
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.9));
  border: 1px solid rgba(102, 126, 234, 0.2);
  border-radius: 12px;
  padding: 16px;
  position: relative;
  transition: all 0.3s ease;
  animation: slideInUp 0.4s ease;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.file-card:hover {
  border-color: rgba(102, 126, 234, 0.5);
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
  transform: translateY(-4px);
}

.file-preview-img {
  width: 100%;
  height: 160px;
  object-fit: cover;
  border-radius: 8px;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(102, 126, 234, 0.1);
}

.file-info {
  margin-top: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.file-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}

.file-size {
  font-size: 0.75rem;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 6px;
}

.file-remove-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.9);
  border: 2px solid rgba(239, 68, 68, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  z-index: 10;
}

.file-remove-btn:hover {
  background: rgba(239, 68, 68, 1);
  border-color: rgba(239, 68, 68, 0.5);
  transform: scale(1.1) rotate(90deg);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.file-remove-btn i {
  color: white;
  font-size: 16px;
}

.file-progress {
  width: 100%;
  height: 6px;
  background: rgba(15, 23, 42, 0.8);
  border-radius: 3px;
  overflow: hidden;
  margin-top: 8px;
}

.file-progress-bar {
  height: 100%;
  width: 0%;
  background: linear-gradient(90deg, #667eea, #764ba2);
  transition: width 0.3s ease;
  box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
}

.file-status {
  font-size: 0.75rem;
  font-weight: 600;
  margin-top: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.file-status.pending { color: #94a3b8; }
.file-status.uploading { color: #667eea; }
.file-status.success { color: #10b981; }
.file-status.error { color: #ef4444; }

.upload-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
  flex-wrap: wrap;
}

.btn-upload-all {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border: none;
  color: white;
  padding: 12px 32px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
}

.btn-upload-all:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(102, 126, 234, 0.5);
}

.btn-upload-all:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.btn-clear-all {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #ef4444;
  padding: 12px 24px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
}

.btn-clear-all:hover {
  background: rgba(239, 68, 68, 0.2);
  border-color: rgba(239, 68, 68, 0.5);
}

.upload-stats {
  display: flex;
  gap: 24px;
  margin-top: 24px;
  padding: 20px;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
  border-radius: 12px;
  border: 1px solid rgba(102, 126, 234, 0.15);
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
}

.stat-value.success { color: #10b981; }
.stat-value.error { color: #ef4444; }
.stat-value.pending { color: #667eea; }

.tips-card {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(5, 150, 105, 0.05));
  border: 1px solid rgba(16, 185, 129, 0.2);
  border-radius: 12px;
  padding: 24px;
  margin-top: 24px;
}

.tips-card h5 {
  color: #10b981;
  font-weight: 700;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.tips-card ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.tips-card li {
  padding: 8px 0;
  color: var(--text-secondary);
  font-size: 0.875rem;
  display: flex;
  align-items: start;
  gap: 12px;
}

.tips-card li::before {
  content: '✓';
  color: #10b981;
  font-weight: 700;
  font-size: 1.1rem;
  flex-shrink: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .file-preview-grid {
    grid-template-columns: 1fr;
  }
  
  .upload-actions {
    flex-direction: column;
  }
  
  .btn-upload-all,
  .btn-clear-all {
    width: 100%;
    justify-content: center;
  }
  
  .upload-stats {
    flex-direction: column;
    gap: 16px;
  }
}
</style>

<div class="container-fluid py-4">
  <div class="row mb-4">
    <div class="col-12">
      <h2 style="color: var(--text-primary); font-weight: 700; display: flex; align-items: center; gap: 12px;">
        <i class="bi bi-cloud-upload" style="color: #667eea;"></i>
        Upload Múltiplo de eSIMs
      </h2>
      <p style="color: var(--text-secondary); margin-top: 8px;">
        Selecione múltiplos arquivos de QR codes para adicionar ao estoque
      </p>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8">
      <div class="upload-container">
        <!-- Product Selection -->
        <div class="mb-4">
          <label class="form-label" style="color: var(--text-primary); font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-box-seam" style="color: #667eea;"></i>
            Produto
          </label>
          <select id="product" class="form-select" style="background: rgba(15, 23, 42, 0.8); border-color: rgba(102, 126, 234, 0.3); color: var(--text-primary);">
            <?php foreach($products as $p): ?>
              <option value="<?= $p['id'] ?>"><?= esc($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Code Text (Optional) -->
        <div class="mb-4">
          <label class="form-label" style="color: var(--text-primary); font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-code-square" style="color: #667eea;"></i>
            Texto/Código (opcional)
          </label>
          <textarea id="code_text" class="form-control" rows="2" 
            placeholder="Cole aqui um código ou notas do eSIM (será aplicado a todos os arquivos)"
            style="background: rgba(15, 23, 42, 0.8); border-color: rgba(102, 126, 234, 0.3); color: var(--text-primary);"></textarea>
        </div>

        <!-- Dropzone -->
        <div id="dropzone" class="dropzone-multi">
          <div class="dropzone-icon">
            <i class="bi bi-cloud-arrow-up"></i>
          </div>
          <div class="dropzone-text">
            Arraste e solte os arquivos aqui
          </div>
          <div class="dropzone-subtext">
            ou clique para selecionar múltiplos arquivos (PNG, JPG) ou arquivo ZIP para envio em massa
          </div>
          <input id="fileInput" type="file" accept="image/*,.zip" multiple style="display: none;">
        </div>

        <!-- File Preview Grid -->
        <div id="filePreviewGrid" class="file-preview-grid"></div>

        <!-- Upload Actions -->
        <div id="uploadActions" class="upload-actions" style="display: none;">
          <button id="btnUploadAll" class="btn-upload-all">
            <i class="bi bi-upload"></i>
            Enviar Todos (<span id="fileCount">0</span>)
          </button>
          <button id="btnClearAll" class="btn-clear-all">
            <i class="bi bi-trash"></i>
            Limpar Todos
          </button>
          <a href="esims_new.php" class="btn btn-outline-light" style="padding: 12px 24px; border-radius: 10px;">
            <i class="bi bi-grid"></i>
            Ver Estoque
          </a>
        </div>

        <!-- Upload Stats -->
        <div id="uploadStats" class="upload-stats" style="display: none;">
          <div class="stat-item">
            <div class="stat-label">Total</div>
            <div class="stat-value" id="statTotal">0</div>
          </div>
          <div class="stat-item">
            <div class="stat-label">Enviados</div>
            <div class="stat-value success" id="statSuccess">0</div>
          </div>
          <div class="stat-item">
            <div class="stat-label">Pendentes</div>
            <div class="stat-value pending" id="statPending">0</div>
          </div>
          <div class="stat-item">
            <div class="stat-label">Erros</div>
            <div class="stat-value error" id="statErrors">0</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <!-- Tips Card -->
      <div class="tips-card">
        <h5>
          <i class="bi bi-lightbulb"></i>
          Dicas de Uso
        </h5>
        <ul>
          <li>Selecione múltiplos arquivos de uma vez usando Ctrl/Cmd + clique</li>
          <li>Arraste e solte vários arquivos diretamente na área de upload</li>
          <li><strong>NOVO:</strong> Envie arquivo ZIP com QR codes para processamento em massa</li>
          <li>No ZIP: organize QR codes em pastas ou diretamente na raiz</li>
          <li>Nome do arquivo deve conter o número: <code>(12)987048218.png</code></li>
          <li>Formatos aceitos: PNG, JPG, ZIP (máx: 100MB para ZIP)</li>
          <li>O sistema extrai números automaticamente e atualiza o banco</li>
          <li>Arquivos são salvos em <code>/uploads/qr/</code></li>
        </ul>
      </div>

      <!-- Additional Info -->
      <div class="tips-card" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05)); border-color: rgba(102, 126, 234, 0.2); margin-top: 16px;">
        <h5 style="color: #667eea;">
          <i class="bi bi-info-circle"></i>
          Informações Técnicas
        </h5>
        <ul>
          <li>Logs em tempo real: <code>/logs/upload_YYYYMMDD.log</code></li>
          <li>Banco de dados: <code>esims</code> table</li>
          <li>API endpoint: <code>/api/upload_esim.php</code></li>
          <li>Máximo recomendado: 20 arquivos por vez</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
// ===== MULTI-FILE UPLOADER SCRIPT =====
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const filePreviewGrid = document.getElementById('filePreviewGrid');
const uploadActions = document.getElementById('uploadActions');
const uploadStats = document.getElementById('uploadStats');
const btnUploadAll = document.getElementById('btnUploadAll');
const btnClearAll = document.getElementById('btnClearAll');
const fileCount = document.getElementById('fileCount');

let selectedFiles = [];
let uploadedCount = 0;
let errorCount = 0;

// Click to select files
dropzone.addEventListener('click', () => fileInput.click());

// Drag and drop events
dropzone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropzone.classList.add('dragover');
});

dropzone.addEventListener('dragleave', () => {
  dropzone.classList.remove('dragover');
});

dropzone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropzone.classList.remove('dragover');
  
  if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
    handleFiles(e.dataTransfer.files);
  }
});

// File input change
fileInput.addEventListener('change', (e) => {
  if (e.target.files && e.target.files.length > 0) {
    handleFiles(e.target.files);
  }
});

// Handle selected files
function handleFiles(files) {
  const filesArray = Array.from(files);
  
  // Separar arquivos ZIP e imagens
  const zipFiles = filesArray.filter(file => file.name.toLowerCase().endsWith('.zip'));
  const imageFiles = filesArray.filter(file => file.type.startsWith('image/'));
  
  // Processar arquivos ZIP para envio em massa
  if (zipFiles.length > 0) {
    zipFiles.forEach(zipFile => handleZipFile(zipFile));
  }
  
  // Processar imagens normalmente
  const validImages = imageFiles.filter(file => {
    const isUnderSize = file.size < 5 * 1024 * 1024; // 5MB
    
    if (!isUnderSize) {
      window.toast?.show('Arquivo ignorado: ' + file.name + ' (maior que 5MB)', 'warning', 3000);
    }
    
    return isUnderSize;
  });
  
  if (validImages.length === 0 && zipFiles.length === 0) return;
  
  validImages.forEach(file => {
    const fileObj = {
      id: Date.now() + Math.random(),
      file: file,
      status: 'pending',
      progress: 0,
      preview: null,
      uploadedId: null,
      type: 'image'
    };
    
    selectedFiles.push(fileObj);
    createFilePreview(fileObj);
  });
  
  updateUI();
  
  if (validImages.length > 0) {
    window.toast?.show(`${validImages.length} imagem(s) adicionada(s)`, 'success', 2000);
  }
}

// Handle ZIP file for bulk upload
async function handleZipFile(zipFile) {
  // Validar tamanho do ZIP (máx 100MB)
  if (zipFile.size > 100 * 1024 * 1024) {
    window.toast?.show('Arquivo ZIP muito grande. Máximo: 100MB', 'error', 5000);
    return;
  }
  
  const productId = document.getElementById('product').value;
  
  if (!productId) {
    window.toast?.show('Selecione um produto antes de enviar o ZIP', 'warning', 4000);
    return;
  }
  
  // Confirmar envio
  if (!confirm(`Deseja processar o arquivo ZIP "${zipFile.name}" para envio em massa?\n\nO sistema irá:\n- Extrair todos os QR codes do ZIP\n- Identificar números nos nomes dos arquivos\n- Atualizar registros existentes no banco\n\nEsta operação pode levar alguns minutos.`)) {
    return;
  }
  
  // Criar card de progresso para o ZIP
  const zipCard = document.createElement('div');
  zipCard.className = 'file-card';
  zipCard.style.gridColumn = '1 / -1'; // Ocupa toda a largura
  zipCard.style.background = 'linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1))';
  zipCard.innerHTML = `
    <div style="padding: 20px; text-align: center;">
      <div style="font-size: 48px; color: #667eea; margin-bottom: 16px;">
        <i class="bi bi-file-zip"></i>
      </div>
      <div style="font-size: 1.25rem; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">
        ${zipFile.name}
      </div>
      <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 16px;">
        ${formatFileSize(zipFile.size)} - Processando...
      </div>
      <div class="file-progress" style="max-width: 400px; margin: 0 auto 16px;">
        <div class="file-progress-bar" id="zipProgress" style="width: 0%"></div>
      </div>
      <div id="zipStatus" class="file-status uploading">
        <i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Enviando ZIP para servidor...
      </div>
    </div>
  `;
  
  filePreviewGrid.insertBefore(zipCard, filePreviewGrid.firstChild);
  
  // Preparar FormData
  const formData = new FormData();
  formData.append('zip', zipFile);
  formData.append('product_id', productId);
  
  try {
    // Atualizar progresso
    const zipProgress = document.getElementById('zipProgress');
    const zipStatus = document.getElementById('zipStatus');
    
    zipProgress.style.width = '30%';
    zipStatus.innerHTML = '<i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Extraindo arquivos...';
    
    // Enviar para API
    const response = await fetch('/api/process_zip_bulk.php', {
      method: 'POST',
      body: formData
    });
    
    zipProgress.style.width = '70%';
    zipStatus.innerHTML = '<i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite;"></i> Processando QR codes...';
    
    const result = await response.json();
    
    zipProgress.style.width = '100%';
    
    if (result.ok) {
      const r = result.results;
      
      // Mostrar resultado detalhado
      zipStatus.className = 'file-status success';
      zipStatus.innerHTML = `
        <i class="bi bi-check-circle-fill"></i> Processamento concluído!<br>
        <small style="display: block; margin-top: 8px;">
          Total: ${r.total_files} arquivos | 
          ✅ Atualizados: ${r.updated} | 
          ⏭️ Ignorados: ${r.skipped} | 
          ❌ Erros: ${r.errors}
        </small>
      `;
      
      // Exibir detalhes em modal ou console
      console.log('Resultado detalhado do ZIP:', result);
      
      window.toast?.show(
        `✅ ZIP processado com sucesso!\n${r.updated} registros atualizados de ${r.total_files} arquivos.`,
        'success',
        8000
      );
      
      // Mostrar detalhes se houver erros
      if (r.errors > 0 || r.skipped > 0) {
        setTimeout(() => {
          const details = r.details
            .filter(d => d.status !== 'success')
            .slice(0, 10)
            .map(d => `- ${d.filename}: ${d.reason || d.message}`)
            .join('\n');
          
          alert(`Detalhes (primeiros 10):\n\n${details}\n\n${r.errors + r.skipped > 10 ? '...e mais ' + (r.errors + r.skipped - 10) + ' arquivos.' : ''}\n\nConsulte o console (F12) para ver todos os detalhes.`);
        }, 2000);
      }
      
    } else {
      zipStatus.className = 'file-status error';
      zipStatus.innerHTML = `<i class="bi bi-x-circle-fill"></i> Erro: ${result.message}`;
      window.toast?.show('Erro ao processar ZIP: ' + result.message, 'error', 5000);
    }
    
  } catch (error) {
    console.error('Erro no upload do ZIP:', error);
    const zipStatus = document.getElementById('zipStatus');
    if (zipStatus) {
      zipStatus.className = 'file-status error';
      zipStatus.innerHTML = `<i class="bi bi-x-circle-fill"></i> Erro de conexão: ${error.message}`;
    }
    window.toast?.show('Erro ao enviar ZIP: ' + error.message, 'error', 5000);
  }
}

// Create file preview card
function createFilePreview(fileObj) {
  const card = document.createElement('div');
  card.className = 'file-card';
  card.id = 'file-' + fileObj.id;
  
  // Create image preview
  const img = document.createElement('img');
  img.className = 'file-preview-img';
  img.alt = fileObj.file.name;
  
  const reader = new FileReader();
  reader.onload = (e) => {
    img.src = e.target.result;
    fileObj.preview = e.target.result;
  };
  reader.readAsDataURL(fileObj.file);
  
  // Remove button
  const removeBtn = document.createElement('div');
  removeBtn.className = 'file-remove-btn';
  removeBtn.innerHTML = '<i class="bi bi-x"></i>';
  removeBtn.onclick = () => removeFile(fileObj.id);
  
  // File info
  const fileInfo = document.createElement('div');
  fileInfo.className = 'file-info';
  
  const fileName = document.createElement('div');
  fileName.className = 'file-name';
  fileName.textContent = fileObj.file.name;
  
  const fileSize = document.createElement('div');
  fileSize.className = 'file-size';
  fileSize.innerHTML = `<i class="bi bi-file-image"></i> ${formatFileSize(fileObj.file.size)}`;
  
  // Progress bar
  const progressBar = document.createElement('div');
  progressBar.className = 'file-progress';
  progressBar.innerHTML = '<div class="file-progress-bar" id="progress-' + fileObj.id + '"></div>';
  
  // Status
  const status = document.createElement('div');
  status.className = 'file-status pending';
  status.id = 'status-' + fileObj.id;
  status.innerHTML = '<i class="bi bi-clock"></i> Aguardando...';
  
  fileInfo.appendChild(fileName);
  fileInfo.appendChild(fileSize);
  fileInfo.appendChild(progressBar);
  fileInfo.appendChild(status);
  
  card.appendChild(img);
  card.appendChild(removeBtn);
  card.appendChild(fileInfo);
  
  filePreviewGrid.appendChild(card);
}

// Remove file from list
function removeFile(fileId) {
  selectedFiles = selectedFiles.filter(f => f.id !== fileId);
  document.getElementById('file-' + fileId)?.remove();
  updateUI();
  
  window.toast?.show('Arquivo removido', 'info', 2000);
}

// Clear all files
btnClearAll.addEventListener('click', () => {
  if (confirm('Deseja remover todos os arquivos selecionados?')) {
    selectedFiles = [];
    filePreviewGrid.innerHTML = '';
    uploadedCount = 0;
    errorCount = 0;
    updateUI();
    window.toast?.show('Todos os arquivos foram removidos', 'info', 2000);
  }
});

// Upload all files
btnUploadAll.addEventListener('click', async () => {
  const pendingFiles = selectedFiles.filter(f => f.status === 'pending');
  
  if (pendingFiles.length === 0) {
    window.toast?.show('Nenhum arquivo pendente para enviar', 'warning', 2000);
    return;
  }
  
  btnUploadAll.disabled = true;
  btnClearAll.disabled = true;
  uploadedCount = 0;
  errorCount = 0;
  
  // Upload files in parallel (max 3 concurrent)
  const maxConcurrent = 3;
  const chunks = [];
  for (let i = 0; i < pendingFiles.length; i += maxConcurrent) {
    chunks.push(pendingFiles.slice(i, i + maxConcurrent));
  }
  
  for (const chunk of chunks) {
    await Promise.all(chunk.map(fileObj => uploadFile(fileObj)));
  }
  
  btnUploadAll.disabled = false;
  btnClearAll.disabled = false;
  
  if (errorCount === 0) {
    window.toast?.show(`✅ Todos os ${uploadedCount} arquivos foram enviados com sucesso!`, 'success', 5000);
  } else {
    window.toast?.show(`Upload concluído: ${uploadedCount} sucesso, ${errorCount} erros`, 'warning', 5000);
  }
});

// Upload single file
async function uploadFile(fileObj) {
  const productId = document.getElementById('product').value;
  const codeText = document.getElementById('code_text').value;
  
  fileObj.status = 'uploading';
  updateFileStatus(fileObj.id, 'uploading', '<i class="bi bi-arrow-up-circle"></i> Enviando...');
  
  const formData = new FormData();
  formData.append('product_id', productId);
  formData.append('code_text', codeText);
  formData.append('file', fileObj.file);
  
  return new Promise((resolve) => {
    const xhr = new XMLHttpRequest();
    
    xhr.upload.onprogress = (e) => {
      if (e.lengthComputable) {
        const progress = Math.round((e.loaded / e.total) * 100);
        fileObj.progress = progress;
        updateFileProgress(fileObj.id, progress);
      }
    };
    
    xhr.onload = () => {
      try {
        const response = JSON.parse(xhr.responseText || '{}');
        
        if (xhr.status === 200 && response.ok) {
          fileObj.status = 'success';
          fileObj.uploadedId = response.id;
          uploadedCount++;
          updateFileStatus(fileObj.id, 'success', `<i class="bi bi-check-circle-fill"></i> Enviado! ID: ${response.id}`);
          updateFileProgress(fileObj.id, 100);
        } else {
          fileObj.status = 'error';
          errorCount++;
          updateFileStatus(fileObj.id, 'error', `<i class="bi bi-x-circle-fill"></i> Erro: ${response.message || 'falha'}`);
        }
      } catch (e) {
        fileObj.status = 'error';
        errorCount++;
        updateFileStatus(fileObj.id, 'error', '<i class="bi bi-x-circle-fill"></i> Resposta inválida');
      }
      
      updateUI();
      resolve();
    };
    
    xhr.onerror = () => {
      fileObj.status = 'error';
      errorCount++;
      updateFileStatus(fileObj.id, 'error', '<i class="bi bi-x-circle-fill"></i> Falha de rede');
      updateUI();
      resolve();
    };
    
    xhr.open('POST', '/api/upload_esim.php');
    xhr.send(formData);
  });
}

// Update file progress bar
function updateFileProgress(fileId, progress) {
  const progressBar = document.getElementById('progress-' + fileId);
  if (progressBar) {
    progressBar.style.width = progress + '%';
  }
}

// Update file status
function updateFileStatus(fileId, statusClass, html) {
  const statusEl = document.getElementById('status-' + fileId);
  if (statusEl) {
    statusEl.className = 'file-status ' + statusClass;
    statusEl.innerHTML = html;
  }
}

// Update UI (counts, stats, buttons)
function updateUI() {
  const total = selectedFiles.length;
  const pending = selectedFiles.filter(f => f.status === 'pending').length;
  const success = selectedFiles.filter(f => f.status === 'success').length;
  const errors = selectedFiles.filter(f => f.status === 'error').length;
  
  fileCount.textContent = pending;
  
  document.getElementById('statTotal').textContent = total;
  document.getElementById('statSuccess').textContent = success;
  document.getElementById('statPending').textContent = pending;
  document.getElementById('statErrors').textContent = errors;
  
  uploadActions.style.display = total > 0 ? 'flex' : 'none';
  uploadStats.style.display = total > 0 ? 'flex' : 'none';
  
  btnUploadAll.disabled = pending === 0;
}

// Format file size
function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Adicionar animação de rotação para ícones de loading
const style = document.createElement('style');
style.textContent = `
  @keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
`;
document.head.appendChild(style);

// Initialize
updateUI();
</script>

<?php include __DIR__ . '/_footer_new.php'; ?>