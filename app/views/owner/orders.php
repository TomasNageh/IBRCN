<?php
/**
 * FILE: orders.php
 * PURPOSE: Shows the bookstore owner’s customer orders and provides the “Mark Ready” action.
 * USED BY: `public/owner-orders.php` endpoint after `OwnerOrdersService` prepares `$orders`, `$orderItems`, and notifications.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */
?>
<?php // VIEW FOR: public/owner-orders.php ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="./css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css" />
    <link rel="icon" type="image/svg" href="./img/bookfavicon.svg" />
    <style>
      .account-menu { position: relative; display: inline-block; }
      .account-panel {
        display: none;
        position: absolute;
        right: 0;
        top: 120%;
        min-width: 220px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        padding: 12px;
        z-index: 1000;
      }
      .account-panel.show { display: block; }
      .account-name { font-size: 1.4rem; font-weight: 700; color: #222; }
      .account-role { font-size: 1.2rem; color: #666; margin-bottom: 10px; }
      .account-logout {
        display: inline-block;
        background: #d9534f;
        color: #fff;
        padding: 6px 10px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 1.2rem;
      }
      .order-flash { max-width: 90%; margin: 1rem auto; padding: 1rem 1.2rem; border-radius: 8px; font-size: 1.4rem; }
      .order-flash.success { background: #e9f9ee; color: #1e6b36; border: 1px solid #97d8ab; }
      .order-flash.error { background: #fdecec; color: #842029; border: 1px solid #f0b4bb; }
      .notify-panel { max-width: 90%; margin: 1rem auto; padding: 1rem 1.2rem; border-radius: 8px; background: #f4f7ff; border: 1px solid #c9d8ff; font-size: 1.25rem; }
      .notify-panel h2 { margin: 0 0 0.75rem; font-size: 1.4rem; }
      .notify-item { padding: 0.6rem 0; border-bottom: 1px solid #e2e8f7; }
      .notify-item:last-child { border-bottom: 0; }
      .notify-meta { font-size: 1.05rem; color: #666; margin-top: 0.25rem; }
      .order-toolbar { max-width: 90%; margin: 1.5rem auto 0; display: flex; gap: 1rem; flex-wrap: wrap; }
      .order-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 2rem;
        padding: 2rem 9%;
        max-width: 1400px;
        margin: 0 auto;
      }
      .order-card {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 1.2rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        transition: transform 0.3s, box-shadow 0.3s;
      }
      .order-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
      }
      .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.4rem;
        border-bottom: 1px solid #eee;
        background: #f9f9f9;
      }
      .order-id {
        font-size: 1.4rem;
        font-weight: 700;
        color: #222;
      }
      .order-status {
        display: inline-block;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 1rem;
        font-weight: 600;
      }
      .order-status.placed { background: #fff3cd; color: #856404; }
      .order-status.ready { background: #d4edda; color: #155724; }
      .order-status.collected { background: #d1ecf1; color: #0c5460; }
      .order-content {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1.4rem;
        flex-grow: 1;
      }
      .order-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        font-size: 1.1rem;
        color: #555;
      }
      .order-meta-item { display: flex; flex-direction: column; gap: 0.3rem; }
      .order-meta-label { font-weight: 600; color: #333; }
      .order-amount {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--primaryColor);
        padding-top: 1rem;
        border-top: 1px solid #eee;
      }
      .order-actions {
        display: flex;
        gap: 0.8rem;
        margin-top: auto;
      }
      .order-actions .btn {
        flex: 1;
        font-size: 1rem;
        padding: 0.6rem;
        text-align: center;
      }
      .order-ready-btn {
        background: var(--primaryColor);
        color: #fff;
        border: none;
        border-radius: 0.6rem;
        padding: 0.6rem;
        font-size: 1rem;
        cursor: pointer;
        flex: 1;
        text-align: center;
        font-weight: 600;
        transition: background 0.3s;
      }
      .order-ready-btn:hover {
        background: #27a047;
      }
      .order-ready-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
      }
      .order-empty {
        text-align: center;
        padding: 4rem 2rem;
      }
      .order-empty p {
        font-size: 1.5rem;
        color: #666;
        margin-bottom: 2rem;
      }
    </style>
    <title>Orders | IBRCN</title>
  </head>
  <body>
    <header class="header">
      <div class="header-1">
        <a href="owner.php" class="logo"><i class="fas fa-book"></i> IBRCN</a>
        <div class="icons">
          <a href="owner.php" class="fas fa-store"></a>
          <a href="reader.php" class="fas fa-book-open-reader"></a>
          <a href="mailbox.php" class="fas fa-envelope" title="Mail"></a>
          <div class="account-menu">
            <a id="account-toggle" href="#" class="fas fa-user" title="Account"></a>
            <div id="account-panel" class="account-panel">
              <div class="account-name"><?php echo htmlspecialchars($_SESSION["user"]); ?></div>
              <div class="account-role"><?php echo htmlspecialchars($_SESSION["role"]); ?></div>
              <a class="account-logout" href="logout.php">Logout</a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section class="member">
      <div class="container">
        <h1>Order Management</h1>
        <p style="font-size: 1.4rem; color: #666; margin-top: 0.5rem;">Track and manage customer orders</p>
      </div>
    </section>

    <?php if (!empty($successMessage)): ?>
      <div class="order-flash success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
      <div class="order-flash error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <?php if (!empty($inAppNotifications)): ?>
      <div class="notify-panel">
        <h2>Notifications</h2>
        <?php foreach ($inAppNotifications as $note): ?>
          <div class="notify-item">
            <strong><?php echo htmlspecialchars((string) ($note['title'] ?? '')); ?></strong>
            <div><?php echo htmlspecialchars((string) ($note['body'] ?? '')); ?></div>
            <?php if (!empty($note['at'])): ?>
              <div class="notify-meta"><?php echo htmlspecialchars((string) $note['at']); ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="order-toolbar">
      <a href="owner.php" class="btn" style="background: #6c757d;"><i class="fas fa-arrow-left"></i> Back to Portal</a>
    </div>

    <?php if (empty($orders)): ?>
      <div class="order-empty">
        <div style="font-size: 3rem; color: var(--primaryColor); margin-bottom: 1rem;">
          <i class="fas fa-inbox"></i>
        </div>
        <p>No orders yet. Your customer orders will appear here.</p>
        <a href="owner-inventory.php" class="btn">View Your Inventory</a>
      </div>
    <?php else: ?>
      <div class="order-list">
        <?php foreach ($orders as $order): ?>
          <div class="order-card">
            <div class="order-header">
              <div class="order-id">#<?php echo (int) $order['order_id']; ?></div>
              <span class="order-status <?php echo strtolower($order['status']); ?>">
                <?php echo htmlspecialchars($order['status']); ?>
              </span>
            </div>
            <div class="order-content">
              <div class="order-meta">
                <div class="order-meta-item">
                  <span class="order-meta-label">Store</span>
                  <span><?php echo htmlspecialchars($order['store_name']); ?></span>
                </div>
                <div class="order-meta-item">
                  <span class="order-meta-label">Items</span>
                  <span><?php echo (int) $order['item_count']; ?> book<?php echo $order['item_count'] != 1 ? 's' : ''; ?></span>
                </div>
                <div class="order-meta-item">
                  <span class="order-meta-label">Reader</span>
                  <span><?php echo htmlspecialchars($order['reader_name']); ?></span>
                </div>
                <div class="order-meta-item">
                  <span class="order-meta-label">Date</span>
                  <span><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                </div>
              </div>
              
              <div class="order-amount">
                EGP <?php echo number_format((float) $order['total_amount'], 2); ?>
              </div>

              <div class="order-actions">
                <?php if ($order['status'] === 'Placed'): ?>
                  <form method="post" action="owner-orders.php" style="flex: 1;">
                    <input type="hidden" name="order_id" value="<?php echo (int) $order['order_id']; ?>" />
                    <button type="submit" class="order-ready-btn" name="mark_ready">
                      <i class="fas fa-check"></i> Mark Ready
                    </button>
                  </form>
                <?php elseif ($order['status'] === 'Ready'): ?>
                  <div style="flex: 1; padding: 0.6rem; background: #e9f9ee; border-radius: 0.6rem; text-align: center; color: #1e6b36; font-weight: 600;">
                    <i class="fas fa-box"></i> Ready for Pickup
                  </div>
                <?php else: ?>
                  <div style="flex: 1; padding: 0.6rem; background: #d1ecf1; border-radius: 0.6rem; text-align: center; color: #0c5460; font-weight: 600;">
                    <i class="fas fa-check-circle"></i> Collected
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <script>
      (function () {
        const toggle = document.getElementById("account-toggle");
        const panel = document.getElementById("account-panel");
        if (!toggle || !panel) return;
        toggle.addEventListener("click", function (e) {
          e.preventDefault();
          panel.classList.toggle("show");
        });
        document.addEventListener("click", function (e) {
          if (!panel.contains(e.target) && !toggle.contains(e.target)) {
            panel.classList.remove("show");
          }
        });
      })();
    </script>
  </body>
</html>