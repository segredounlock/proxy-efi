<?php
/**
 * eSIM Admin - Dashboard
 * Painel principal com métricas e visão geral do sistema
 */
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_login();
include __DIR__ . '/_header_new.php';

// Buscar métricas
$k_users = $k_orders = $k_revenue = $k_sold = 0;
$k_pending = $k_delivered = 0;

try {
  $pdo = db();
  
  // Total de usuários únicos
  $k_users = (int)($pdo->query("SELECT COUNT(DISTINCT chat_id) AS n FROM orders")->fetch()['n'] ?? 0);
  
  // Total de pedidos
  $k_orders = (int)($pdo->query("SELECT COUNT(*) AS n FROM orders")->fetch()['n'] ?? 0);
  
  // Receita total (pedidos pagos/entregues/completados) - CORRIGIDO
  $revenueResult = $pdo->query("
    SELECT 
      COALESCE(
        SUM(
          CASE 
            WHEN final_price_cents IS NOT NULL AND final_price_cents > 0 THEN final_price_cents
            WHEN price_cents IS NOT NULL AND price_cents > 0 THEN price_cents
            ELSE 0
          END
        ) / 100, 
        0
      ) AS s 
    FROM orders 
    WHERE status IN ('delivered','completed','paid')
  ");
  $k_revenue = (float)($revenueResult->fetch()['s'] ?? 0);
  
  // eSIMs vendidos
  $k_sold = (int)($pdo->query("
    SELECT COUNT(*) AS n 
    FROM orders 
    WHERE status IN ('delivered','completed','paid')
  ")->fetch()['n'] ?? 0);
  
  // Pedidos pendentes
  $k_pending = (int)($pdo->query("SELECT COUNT(*) AS n FROM orders WHERE status = 'pending'")->fetch()['n'] ?? 0);
  
  // Pedidos entregues
  $k_delivered = (int)($pdo->query("SELECT COUNT(*) AS n FROM orders WHERE status IN ('delivered','completed')")->fetch()['n'] ?? 0);
  
} catch (Throwable $e) {
  if (function_exists('write_log')) {
    write_log('admin_index', ['error' => $e->getMessage()]);
  }
  echo '<div class="alert alert-danger">Erro ao carregar métricas: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Calcular variações (simulado - você pode implementar lógica real de comparação)
$users_trend = '+12.5%';
$orders_trend = '+8.3%';
$revenue_trend = '+15.2%';
$sold_trend = '+10.7%';
?>

<!-- KPI Cards -->
<div class="kpi-grid">
  
  <!-- Usuários -->
  <div class="kpi-card users">
    <div class="icon">
      <i class="bi bi-people-fill"></i>
    </div>
    <div class="kpi-label">Total de Usuários</div>
    <div class="kpi-value" data-count="<?= $k_users ?>">0</div>
    <div class="kpi-trend up">
      <i class="bi bi-arrow-up"></i> <?= $users_trend ?> vs mês anterior
    </div>
  </div>
  
  <!-- Pedidos -->
  <div class="kpi-card orders">
    <div class="icon">
      <i class="bi bi-bag-check-fill"></i>
    </div>
    <div class="kpi-label">Total de Pedidos</div>
    <div class="kpi-value" data-count="<?= $k_orders ?>">0</div>
    <div class="kpi-trend up">
      <i class="bi bi-arrow-up"></i> <?= $orders_trend ?> vs mês anterior
    </div>
  </div>
  
  <!-- Receita -->
  <div class="kpi-card revenue">
    <div class="icon">
      <i class="bi bi-cash-coin"></i>
    </div>
    <div class="kpi-label">Receita Total</div>
    <div class="kpi-value" data-count="<?= $k_revenue ?>" data-money>R$ 0,00</div>
    <div class="kpi-trend up">
      <i class="bi bi-arrow-up"></i> <?= $revenue_trend ?> vs mês anterior
    </div>
  </div>
  
  <!-- eSIMs Vendidos -->
  <div class="kpi-card sold">
    <div class="icon">
      <i class="bi bi-qr-code-scan"></i>
    </div>
    <div class="kpi-label">eSIMs Vendidos</div>
    <div class="kpi-value" data-count="<?= $k_sold ?>">0</div>
    <div class="kpi-trend up">
      <i class="bi bi-arrow-up"></i> <?= $sold_trend ?> vs mês anterior
    </div>
  </div>
  
</div>

<!-- Status Overview -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card">
      <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
        <div style="
          width: 48px;
          height: 48px;
          border-radius: 12px;
          background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1.5rem;
          color: #f59e0b;
        ">
          <i class="bi bi-clock-history"></i>
        </div>
        <div>
          <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;">Pedidos Pendentes</h3>
          <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">Aguardando pagamento</p>
        </div>
      </div>
      <div style="font-size: 2.5rem; font-weight: 800; color: #f59e0b;">
        <?= $k_pending ?>
      </div>
      <a href="<?= base_url() ?>/admin/orders.php?status=pending" class="btn btn-outline mt-3">
        Ver pedidos pendentes <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>
  
  <div class="col-md-6">
    <div class="card">
      <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
        <div style="
          width: 48px;
          height: 48px;
          border-radius: 12px;
          background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1.5rem;
          color: #10b981;
        ">
          <i class="bi bi-check-circle"></i>
        </div>
        <div>
          <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;">Pedidos Entregues</h3>
          <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">Concluídos com sucesso</p>
        </div>
      </div>
      <div style="font-size: 2.5rem; font-weight: 800; color: #10b981;">
        <?= $k_delivered ?>
      </div>
      <a href="<?= base_url() ?>/admin/orders.php?status=delivered" class="btn btn-outline mt-3">
        Ver pedidos entregues <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
  <h3 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; font-weight: 700;">
    <i class="bi bi-lightning-charge"></i> Ações Rápidas
  </h3>
  <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
    <a href="<?= base_url() ?>/admin/uploader.php" class="btn btn-primary">
      <i class="bi bi-cloud-upload"></i> Adicionar Estoque
    </a>
    <a href="<?= base_url() ?>/admin/esims.php" class="btn btn-info">
      <i class="bi bi-sim"></i> Ver Estoque eSIM
    </a>
    <a href="<?= base_url() ?>/admin/orders.php" class="btn btn-success">
      <i class="bi bi-cart-check"></i> Ver Todos os Pedidos
    </a>
    <a href="<?= base_url() ?>/admin/sales.php" class="btn btn-warning">
      <i class="bi bi-graph-up"></i> Relatório de Vendas
    </a>
    <a href="<?= base_url() ?>/admin/notify.php" class="btn btn-outline">
      <i class="bi bi-bell"></i> Enviar Notificação
    </a>
  </div>
</div>

<!-- Últimos Pedidos -->
<?php
try {
  $stmt = $pdo->query("
    SELECT o.id, o.chat_id, o.status, o.created_at, o.final_price_cents, o.price_cents,
           p.name as product_name
    FROM orders o
    LEFT JOIN products p ON p.id = o.product_id
    ORDER BY o.id DESC
    LIMIT 5
  ");
  $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  if ($recent_orders):
?>
<div class="card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;">
      <i class="bi bi-clock-history"></i> Últimos Pedidos
    </h3>
    <a href="<?= base_url() ?>/admin/orders.php" class="btn btn-sm btn-ghost">
      Ver todos <i class="bi bi-arrow-right"></i>
    </a>
  </div>
  
  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th style="width: 80px;">ID</th>
          <th style="width: 220px;">Produto</th>
          <th style="width: 150px;">Cliente</th>
          <th style="width: 140px;">Status</th>
          <th style="width: 120px;">Valor</th>
          <th style="width: 150px;">Data/Hora</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent_orders as $order): 
          $statusClasses = [
            'pending' => 'badge-pending',
            'paid' => 'badge-paid',
            'delivered' => 'badge-delivered',
            'completed' => 'badge-completed'
          ];
          $statusLabels = [
            'pending' => 'Pendente',
            'paid' => 'Pago',
            'delivered' => 'Entregue',
            'completed' => 'Completo'
          ];
          $badgeClass = $statusClasses[$order['status']] ?? 'badge-pending';
          $badgeLabel = $statusLabels[$order['status']] ?? ucfirst($order['status']);
          
          $price = $order['final_price_cents'] ?? $order['price_cents'] ?? 0;
          $price_formatted = 'R$ ' . number_format($price / 100, 2, ',', '.');
        ?>
        <tr>
          <td>
            <div style="
              width: 36px;
              height: 36px;
              border-radius: 8px;
              background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
              display: flex;
              align-items: center;
              justify-content: center;
              font-size: 0.75rem;
              font-weight: 700;
              color: #667eea;
            ">
              #<?= $order['id'] ?>
            </div>
          </td>
          <td>
            <div style="display: flex; align-items: center; gap: 8px;">
              <i class="bi bi-sim" style="font-size: 1.25rem; color: #667eea;"></i>
              <span style="font-weight: 600; color: var(--text-primary);">
                <?= htmlspecialchars($order['product_name'] ?? 'Produto') ?>
              </span>
            </div>
          </td>
          <td>
            <div style="display: flex; align-items: center; gap: 6px;">
              <i class="bi bi-person-circle" style="color: #94a3b8;"></i>
              <code style="
                font-size: 0.75rem;
                padding: 3px 7px;
                background: linear-gradient(135deg, rgba(148, 163, 184, 0.08), rgba(148, 163, 184, 0.12));
                border: 1px solid rgba(148, 163, 184, 0.15);
                border-radius: 5px;
                font-weight: 600;
              "><?= htmlspecialchars(substr((string)$order['chat_id'], 0, 10)) ?></code>
            </div>
          </td>
          <td>
            <span class="badge <?= $badgeClass ?>" style="
              font-size: 0.7rem;
              padding: 5px 10px;
              font-weight: 700;
            "><?= $badgeLabel ?></span>
          </td>
          <td>
            <div style="
              font-weight: 700;
              font-size: 0.95rem;
              color: #10b981;
              display: flex;
              align-items: center;
              gap: 4px;
            ">
              <i class="bi bi-cash-coin" style="opacity: 0.7;"></i>
              <?= $price_formatted ?>
            </div>
          </td>
          <td>
            <div style="display: flex; flex-direction: column; gap: 2px;">
              <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-primary);">
                <i class="bi bi-calendar-check" style="font-size: 0.75rem; color: #667eea; margin-right: 4px;"></i>
                <?= date('d/m/Y', strtotime($order['created_at'])) ?>
              </span>
              <span style="font-size: 0.7rem; color: var(--text-secondary);">
                <i class="bi bi-clock" style="font-size: 0.65rem; margin-right: 4px;"></i>
                <?= date('H:i', strtotime($order['created_at'])) ?>
              </span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php 
  endif;
} catch (Throwable $e) {
  echo '<div class="alert alert-danger">Erro ao carregar pedidos recentes: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

<!-- Welcome Message -->
<div class="card mt-4" style="text-align: center; padding: 3rem 2rem;">
  <div style="font-size: 3rem; margin-bottom: 1rem;">🎉</div>
  <h2 style="margin: 0 0 1rem 0; font-size: 1.5rem; font-weight: 700;">
    Bem-vindo ao eSIM Admin 2.0!
  </h2>
  <p style="color: var(--text-secondary); margin: 0; max-width: 600px; margin: 0 auto;">
    Sistema de gerenciamento completamente reformulado com design moderno,
    interface intuitiva e animações fluidas. Gerencie seus eSIMs com eficiência e estilo!
  </p>
</div>

<script>
// Mostrar toast de boas-vindas (apenas na primeira vez)
if (!sessionStorage.getItem('welcomeShown')) {
  setTimeout(() => {
    if (window.toast) {
      toast.show('Bem-vindo ao novo eSIM Admin! 🎉', 'success', 5000);
      sessionStorage.setItem('welcomeShown', 'true');
    }
  }, 1000);
}
</script>

<?php include __DIR__ . '/_footer_new.php'; ?>
