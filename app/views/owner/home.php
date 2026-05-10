<?php
/**
 * FILE: home.php
 * PURPOSE: Shows the Owner portal home page with links to inventory management, orders, and reports.
 * USED BY: `public/owner.php` endpoint.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */
?>
<?php // VIEW FOR: public/owner.php ?>
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
    </style>
    <title>Owner Dashboard | IBRCN</title>
  </head>
  <body>
    <header class="header">
      <div class="header-1">
        <a href="#" class="logo"><i class="fas fa-book"></i> IBRCN</a>
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

    <section class="home" id="home">
      <div class="row">
        <div class="content">
          <h3>Owner Portal</h3>
          <p>
            Welcome, <?php echo htmlspecialchars($_SESSION["user"]); ?>. This area is for bookstore owner workflows.
          </p>
          <a href="#owner-tools" class="btn">Open Owner Tools</a>
        </div>
        <div class="image">
          <img src="./img/img4.svg" alt="Owner Dashboard" />
        </div>
      </div>
    </section>

    <section id="owner-tools" class="member">
      <div class="container">
        <h1>OWNER FEATURES</h1>
        <p style="font-size: 1.6rem; text-align: center; margin-bottom: 3rem;">
          Manage your bookstore inventory, track orders, and grow your business.
        </p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
          <!-- Inventory Card -->
          <div style="background: #fff; border: 2px solid var(--primaryColor); border-radius: 12px; padding: 2rem; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.3s, box-shadow 0.3s;">
            <div style="font-size: 3.5rem; color: var(--primaryColor); margin-bottom: 1rem;">
              <i class="fas fa-boxes"></i>
            </div>
            <h3 style="font-size: 1.8rem; margin-bottom: 0.8rem; color: #222;">Manage Inventory</h3>
            <p style="font-size: 1.3rem; color: #666; margin-bottom: 1.5rem;">View, add, update, and remove books from your store.</p>
            <a href="owner-inventory.php" class="btn" style="display: inline-block;">Go to Inventory</a>
          </div>
          
          <!-- Add Book Card -->
          <div style="background: #fff; border: 2px solid var(--primaryColor); border-radius: 12px; padding: 2rem; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: transform 0.3s, box-shadow 0.3s;">
            <div style="font-size: 3.5rem; color: var(--primaryColor); margin-bottom: 1rem;">
              <i class="fas fa-plus-circle"></i>
            </div>
            <h3 style="font-size: 1.8rem; margin-bottom: 0.8rem; color: #222;">Add New Books</h3>
            <p style="font-size: 1.3rem; color: #666; margin-bottom: 1.5rem;">List new used books from your collection.</p>
            <a href="owner-used-book.php" class="btn" style="display: inline-block;">Add Books</a>
          </div>

          <div style="background: #fff; border: 2px solid #0d6efd; border-radius: 12px; padding: 2rem; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
            <div style="font-size: 3.5rem; color: #0d6efd; margin-bottom: 1rem;">
              <i class="fas fa-file-pdf"></i>
            </div>
            <h3 style="font-size: 1.8rem; margin-bottom: 0.8rem; color: #222;">Inventory report</h3>
            <p style="font-size: 1.3rem; color: #666; margin-bottom: 1.5rem;">Download a PDF listing your books, prices, quantities, and on-hold counts.</p>
            <a href="owner-report-pdf.php" class="btn" style="display: inline-block; background: #0d6efd;">Download PDF</a>
          </div>
        </div>
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
