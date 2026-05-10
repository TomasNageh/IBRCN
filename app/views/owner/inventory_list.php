<?php
/**
 * FILE: inventory_list.php
 * PURPOSE: Shows the Owner’s current inventory listings and actions (download PDF, add book, level up, remove listing, CSV import).
 * USED BY: `public/owner-inventory.php` endpoint after it loads `$listings`, `$successMessage`, and `$errorMessage`.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */
?>
<?php // VIEW FOR: public/owner-inventory.php ?>
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
      .owner-table-wrap {
        max-width: 1100px;
        margin: 2rem auto 4rem;
        padding: 0 2rem;
      }
      .owner-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 1.35rem;
        background: #fff;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      }
      .owner-table th,
      .owner-table td {
        border: 1px solid #e8e8e8;
        padding: 0.85rem 1rem;
        text-align: left;
      }
      .owner-table th { background: #f8f8f8; }
      .owner-flash {
        max-width: 1100px;
        margin: 1rem auto 0;
        padding: 1rem 1.2rem;
        border-radius: 8px;
        font-size: 1.4rem;
      }
      .owner-flash.success { background: #e9f9ee; color: #1e6b36; border: 1px solid #97d8ab; }
      .owner-flash.error { background: #fdecec; color: #842029; border: 1px solid #f0b4bb; }
      .owner-actions { display: flex; flex-wrap: wrap; gap: 0.6rem; }
      .owner-actions .btn { font-size: 1.25rem; padding: 0.45rem 0.9rem; }
      .owner-toolbar { max-width: 100%; margin: 2rem 0 0; padding: 0 2rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
      .owner-inventory-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 2rem;
        padding: 2rem 9%;
        max-width: 1400px;
        margin: 0 auto;
      }
      .owner-book-card {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 1.2rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        transition: transform 0.3s, box-shadow 0.3s;
      }
      .owner-book-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
      }
      .owner-book-image {
        background: linear-gradient(15deg, #f0f0f0 30%, #fff 30.2%);
        padding: 1.4rem;
        display: flex;
        justify-content: center;
        min-height: 280px;
      }
      .owner-book-image img {
        max-width: 100%;
        max-height: 260px;
        object-fit: cover;
        border-radius: 0.8rem;
      }
      .owner-book-content {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        padding: 1.2rem;
        flex-grow: 1;
      }
      .owner-book-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: #222;
        line-height: 1.3;
      }
      .owner-book-meta {
        font-size: 1.1rem;
        color: #666;
      }
      .owner-book-price {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--primaryColor);
      }
      .owner-book-stock {
        display: flex;
        justify-content: space-between;
        font-size: 1.1rem;
        color: #555;
        padding: 0.8rem 0;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
      }
      .owner-book-condition {
        display: inline-block;
        background: #f0f0f0;
        padding: 0.4rem 0.8rem;
        border-radius: 0.6rem;
        font-size: 1rem;
        color: #555;
      }
      .owner-book-actions {
        display: flex;
        gap: 0.8rem;
        margin-top: auto;
      }
      .owner-book-actions .btn {
        flex: 1;
        font-size: 1.1rem;
        padding: 0.6rem;
        text-align: center;
      }
      .owner-delete-btn {
        background: #e74c3c;
        color: #fff;
        border: none;
        border-radius: 0.6rem;
        padding: 0.6rem;
        font-size: 1.1rem;
        cursor: pointer;
        flex: 1;
        text-align: center;
        transition: background 0.3s;
      }
      .owner-delete-btn:hover {
        background: #c0392b;
      }
      .owner-empty {
        text-align: center;
        padding: 4rem 2rem;
      }
      .owner-empty p {
        font-size: 1.5rem;
        color: #666;
        margin-bottom: 2rem;
      }
    </style>
    <title>My Inventory | IBRCN</title>
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
        <h1>Store Inventory</h1>
        <p style="font-size: 1.4rem; color: #666; margin-top: 0.5rem;">Manage your bookstore's books and listings</p>
      </div>
    </section>

    <?php if (!empty($successMessage)): ?>
      <div class="owner-flash success" style="max-width: 90%; margin: 1rem auto 0;"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
      <div class="owner-flash error" style="max-width: 90%; margin: 1rem auto 0;"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <div class="owner-toolbar" style="max-width: 90%; margin: 1.5rem auto 0;">
      <a href="owner-report-pdf.php" class="btn" style="background: #0d6efd;"><i class="fas fa-file-pdf"></i> Download inventory PDF</a>
      <a href="owner-used-book.php" class="btn"><i class="fas fa-plus"></i> Add New Book</a>
      <a href="owner.php" class="btn" style="background: #6c757d;"><i class="fas fa-arrow-left"></i> Back to Portal</a>
      <form method="POST" enctype="multipart/form-data" action="owner-inventory.php" style="display:flex;gap:0.6rem;flex-wrap:wrap;align-items:center;">
        <label for="csv_file" style="font-size:1.25rem;">Import Inventory (CSV, max 10 MB):</label>
        <input type="file" id="csv_file" name="csv_file" accept=".csv" />
        <button type="submit" name="upload_csv" value="1" class="btn" style="background:#0d6efd;border:none;cursor:pointer;">Upload</button>
      </form>
      <form method="post" action="owner-inventory.php" onsubmit="return confirm('Level up all books by +1 quantity?');" style="display: inline;">
        <input type="hidden" name="increment" value="1" />
        <button type="submit" name="level_up_all" class="btn" style="background: #198754; border: none; cursor: pointer;">
          <i class="fas fa-level-up-alt"></i> Level Up All (+1)
        </button>
      </form>
    </div>

    <?php if (empty($listings)): ?>
      <div class="owner-empty">
        <div style="font-size: 3rem; color: var(--primaryColor); margin-bottom: 1rem;">
          <i class="fas fa-inbox"></i>
        </div>
        <p>No books in your inventory yet.</p>
        <a href="owner-used-book.php" class="btn">Add Your First Book</a>
      </div>
    <?php else: ?>
      <div class="owner-inventory-grid">
        <?php foreach ($listings as $line): ?>
          <div class="owner-book-card">
            <div class="owner-book-image">
              <?php if (!empty($line['cover_image'])): ?>
                <img src="<?php echo htmlspecialchars($line['cover_image']); ?>" alt="<?php echo htmlspecialchars((string) $line['title']); ?>" />
              <?php else: ?>
                <div style="display: flex; align-items: center; justify-content: center; color: #999; font-size: 3rem;">
                  <i class="fas fa-image"></i>
                </div>
              <?php endif; ?>
            </div>
            <div class="owner-book-content">
              <h3 class="owner-book-title"><?php echo htmlspecialchars((string) $line['title']); ?></h3>
              <p class="owner-book-meta"><?php echo htmlspecialchars((string) $line['author']); ?></p>
              <p class="owner-book-price"><?php echo htmlspecialchars((string) $line['price_egp_formatted']); ?></p>
              
              <div class="owner-book-stock">
                <span><strong><?php echo (int) $line['quantity']; ?></strong> in stock</span>
                <span><strong><?php echo (int) $line['hold_quantity']; ?></strong> on hold</span>
              </div>
              
              <p style="margin-top: 0.5rem;">
                <span class="owner-book-condition"><?php echo htmlspecialchars((string) $line['condition']); ?></span>
              </p>
              
              <div class="owner-book-actions">
                <a class="btn" href="owner-inventory-edit.php?inventory_id=<?php echo (int) $line['inventory_id']; ?>" style="background: var(--primaryColor);">
                  <i class="fas fa-edit"></i> Edit
                </a>
                <form method="post" action="owner-inventory.php" style="flex: 1;" onsubmit="return confirm('Are you sure you want to delete this book from your inventory?');">
                  <input type="hidden" name="inventory_id" value="<?php echo (int) $line['inventory_id']; ?>" />
                  <button class="owner-delete-btn" type="submit" name="remove_listing">
                    <i class="fas fa-trash"></i> Delete
                  </button>
                </form>
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
