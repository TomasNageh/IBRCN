<?php
/**
 * FILE: orders.php
 * PURPOSE: Shows the reader’s order history, notifications, and the “Mark Collected” action for ready orders.
 * USED BY: `public/orders.php` endpoint after it loads `$orders`, `$orderItems`, `$inAppNotifications`, and optional `$myClubs`.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */
?>
<?php // VIEW FOR: public/orders.php reader order history ?>
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
      .order-wrap { max-width: 1100px; margin: 2rem auto 4rem; padding: 0 2rem; }
      .order-table { width: 100%; border-collapse: collapse; font-size: 1.35rem; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
      .order-table th, .order-table td { border: 1px solid #e8e8e8; padding: 0.85rem 1rem; text-align: left; }
      .order-table th { background: #f8f8f8; }
      .order-flash { margin: 1rem 0; padding: 1rem 1.2rem; border-radius: 8px; font-size: 1.4rem; }
      .order-flash.success { background: #e9f9ee; color: #1e6b36; border: 1px solid #97d8ab; }
      .order-flash.error { background: #fdecec; color: #842029; border: 1px solid #f0b4bb; }
      .notify-panel { margin: 1rem 0 1.5rem; padding: 1rem 1.2rem; border-radius: 8px; background: #f4f7ff; border: 1px solid #c9d8ff; font-size: 1.25rem; }
      .notify-panel h2 { margin: 0 0 0.75rem; font-size: 1.4rem; }
      .notify-item { padding: 0.6rem 0; border-bottom: 1px solid #e2e8f7; }
      .notify-item:last-child { border-bottom: 0; }
      .notify-meta { font-size: 1.05rem; color: #666; margin-top: 0.25rem; }
    </style>
    <title>My Orders | IBRCN</title>
  </head>
  <body>
    <header class="header">
      <div class="header-1">
        <a href="reader.php" class="logo"><i class="fas fa-book"></i> IBRCN</a>
        <div class="icons">
          <a href="reader.php" class="fas fa-book-open-reader"></a>
          <a href="mailbox.php" class="fas fa-envelope" title="Mail"></a>
          <a href="cart.php" class="fas fa-shopping-cart"></a>
          <a href="logout.php" class="fas fa-right-from-bracket"></a>
        </div>
      </div>
    </header>

    <section class="member">
      <div class="container">
        <h1>Order Status</h1>
      </div>
    </section>

    <?php if (!empty($myClubs)): ?>
      <div class="order-wrap" style="padding-bottom: 0;">
        <div class="notify-panel" style="margin-top: 0;">
          <h2>Your reading clubs</h2>
          <p style="margin: 0 0 0.75rem; font-size: 1.25rem; color: #555;">
            What everyone is reading in your clubs (same data as on <a href="./member.php">Reading Clubs</a>).
          </p>
          <ul style="font-size: 1.35rem; margin: 0; padding-left: 1.2rem;">
            <?php foreach ($myClubs as $club): ?>
              <li style="margin: 0.75rem 0;">
                <strong><?php echo htmlspecialchars((string) $club['name']); ?></strong>
                <?php if (!empty($club['description'])): ?>
                  <span style="color: #666"> — <?php echo htmlspecialchars((string) $club['description']); ?></span>
                <?php endif; ?>
                <?php if (!empty($club['member_reads'])): ?>
                  <ul style="font-size: 1.15rem; color: #444; margin: 0.4rem 0 0; padding-left: 1.25rem;">
                    <?php foreach ($club['member_reads'] as $mr): ?>
                      <li style="margin: 0.2rem 0;">
                        <strong><?php echo htmlspecialchars((string) $mr['display_name']); ?></strong>
                        <?php if (!empty($mr['book_title'])): ?>
                          — <em><?php echo htmlspecialchars((string) $mr['book_title']); ?></em>
                          <?php if (!empty($mr['book_author'])): ?>
                            <span style="color: #666"> — <?php echo htmlspecialchars((string) $mr['book_author']); ?></span>
                          <?php endif; ?>
                        <?php else: ?>
                          <span style="color: #999"> — no book listed yet</span>
                        <?php endif; ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <div class="order-wrap">
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

      <?php if (empty($orders)): ?>
        <p style="font-size:1.5rem;">No orders yet.</p>
      <?php else: ?>
        <table class="order-table">
          <thead>
            <tr>
              <th>Order</th>
              <th>Store</th>
              <th>Status</th>
              <th>Total</th>
              <th>Items</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
              <tr>
                <td>#<?php echo (int) $order['order_id']; ?></td>
                <td><?php echo htmlspecialchars((string) $order['store_name']); ?></td>
                <td><?php echo htmlspecialchars((string) $order['status']); ?></td>
                <td>EGP <?php echo number_format((float) $order['total_amount'], 2); ?></td>
                <td><?php echo (int) $order['item_count']; ?></td>
                <td>
                  <?php if ($order['status'] === 'Ready'): ?>
                    <form method="post" action="orders.php" style="margin:0;">
                      <input type="hidden" name="order_id" value="<?php echo (int) $order['order_id']; ?>" />
                      <button class="btn" type="submit" name="mark_collected" value="1">Mark Collected</button>
                    </form>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </body>
</html>