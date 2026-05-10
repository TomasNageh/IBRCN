<?php
/**
 * FILE: inventory_edit.php
 * PURPOSE: Shows the owner inventory edit form for one listing (condition, price, quantity).
 * USED BY: `public/owner-inventory-edit.php` endpoint after it loads `$row`, `$formData`, and flash messages.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */
?>
<?php // VIEW FOR: public/owner-inventory-edit.php ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="./css/style.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css"
    />
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
      .owner-form-wrap {
        max-width: 760px;
        margin: 2rem auto 4rem;
        padding: 2.2rem;
        background: #fff;
        border: 1px solid #eee;
        border-radius: 10px;
      }
      .owner-form-wrap h2 { font-size: 2.4rem; margin-bottom: 1rem; }
      .owner-meta { font-size: 1.35rem; color: #555; margin-bottom: 1.5rem; line-height: 1.5; }
      .owner-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(220px, 1fr));
        gap: 1.2rem;
      }
      .owner-form-wrap label { display: block; font-size: 1.4rem; margin-bottom: 0.5rem; color: #333; }
      .owner-form-wrap input,
      .owner-form-wrap select {
        width: 100%;
        border: 1px solid #ccc;
        border-radius: 8px;
        padding: 0.9rem 1rem;
        font-size: 1.4rem;
      }
      .owner-flash {
        margin: 1rem 0;
        padding: 1rem 1.2rem;
        border-radius: 8px;
        font-size: 1.4rem;
      }
      .owner-flash.success { background: #e9f9ee; color: #1e6b36; border: 1px solid #97d8ab; }
      .owner-flash.error { background: #fdecec; color: #842029; border: 1px solid #f0b4bb; }
      @media (max-width: 768px) { .owner-form-grid { grid-template-columns: 1fr; } }
    </style>
    <title>Edit Listing | IBRCN</title>
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
      <div class="container"><h1>Edit Listing</h1></div>
    </section>

    <section>
      <div class="owner-form-wrap">
        <div style="display: flex; gap: 2rem; margin-bottom: 2rem;">
          <?php if (!empty($row['cover_image'])): ?>
            <div style="flex-shrink: 0;">
              <img src="<?php echo htmlspecialchars($row['cover_image']); ?>" alt="<?php echo htmlspecialchars((string) $row['title']); ?>" style="width: 120px; height: 180px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);" />
            </div>
          <?php endif; ?>
          <div style="flex-grow: 1;">
            <h2><?php echo htmlspecialchars((string) $row['title']); ?></h2>
            <div class="owner-meta">
              <div><strong>ISBN:</strong> <?php echo htmlspecialchars((string) $row['isbn']); ?></div>
              <div><strong>Author:</strong> <?php echo htmlspecialchars((string) $row['author']); ?></div>
              <div><strong>On hold (system):</strong> <?php echo (int) $row['hold_quantity']; ?> — do not set quantity below this without clearing holds first.</div>
            </div>
          </div>
        </div>

        <?php if (!empty($errorMessage)): ?>
          <div class="owner-flash error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>
        <?php if (!empty($successMessage)): ?>
          <div class="owner-flash success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <form method="post" action="owner-inventory-edit.php">
          <input type="hidden" name="inventory_id" value="<?php echo (int) $inventoryId; ?>" />
          <div class="owner-form-grid">
            <div>
              <label for="condition">Condition</label>
              <select id="condition" name="condition">
                <?php foreach (array('New', 'Fine', 'Good', 'Fair') as $c): ?>
                  <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $formData['condition'] === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="price">Price (EGP)</label>
              <input id="price" name="price" type="number" min="0.01" step="0.01" required value="<?php echo htmlspecialchars($formData['price']); ?>" />
            </div>
            <div style="grid-column: 1 / -1;">
              <label for="quantity">Quantity on shelf</label>
              <input id="quantity" name="quantity" type="number" min="0" step="1" required value="<?php echo htmlspecialchars($formData['quantity']); ?>" />
            </div>
          </div>
          <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <button type="submit" class="btn" name="update_listing">Save Changes</button>
            <a class="btn" href="owner-inventory.php" style="background: #6c757d;">Back to Inventory</a>
          </div>
        </form>
      </div>
    </section>

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
