<?php
/**
 * FILE: book_stores.php
 * PURPOSE: Shows the list of bookstores that have the selected book in stock for pickup (with optional distance sorting).
 * USED BY: `public/book-stores.php` endpoint after it loads `$bookRow`, `$stores`, and `$errorMessage`.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */

$isLoggedIn = isset($_SESSION['user']) && isset($_SESSION['role']);
?>
<?php // VIEW FOR: public/book-stores.php store listings ?>
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
      .store-locator-wrap {
        padding: 2rem 9% 4rem;
      }
      .store-locator-hero {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
        align-items: flex-start;
        margin-bottom: 2rem;
      }
      .store-locator-book {
        flex: 0 0 12rem;
        text-align: center;
      }
      .store-locator-book img {
        max-width: 12rem;
        border-radius: 8px;
        box-shadow: 0 6px 16px rgba(0,0,0,0.12);
      }
      .store-locator-meta h1 { font-size: 2.4rem; margin-bottom: 0.5rem; }
      .store-locator-meta p { font-size: 1.4rem; color: #444; margin: 0.25rem 0; }
      .store-sort-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
        margin-bottom: 1rem;
      }
      .store-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 1.35rem;
        background: #fff;
      }
      .store-table th,
      .store-table td {
        border: 1px solid #e8e8e8;
        padding: 0.85rem 1rem;
        text-align: left;
      }
      .store-table th { background: #f8f8f8; }
      .store-flash-error {
        background: #fdecec;
        color: #842029;
        border: 1px solid #f0b4bb;
        padding: 1rem 1.2rem;
        border-radius: 8px;
        font-size: 1.4rem;
      }
      .store-muted { font-size: 1.25rem; color: #666; margin-top: 0.75rem; }
    </style>
    <title>Pickup locations | IBRCN</title>
  </head>
  <body>
    <header class="header">
      <div class="header-1">
        <a href="reader.php" class="logo"><i class="fas fa-book"></i> IBRCN</a>
        <div class="icons">
          <a href="reader.php" class="fas fa-book-open-reader"></a>
          <?php if ($isLoggedIn): ?>
          <a href="mailbox.php" class="fas fa-envelope" title="Mail"></a>
          <?php endif; ?>
          <a href="cart.php" class="fas fa-shopping-cart"></a>
          <?php if ($isLoggedIn): ?>
          <div class="account-menu">
            <a id="account-toggle" class="fas fa-user" href="#" title="Account"></a>
            <div id="account-panel" class="account-panel">
              <div class="account-name"><?php echo htmlspecialchars($_SESSION["user"]); ?></div>
              <div class="account-role"><?php echo htmlspecialchars($_SESSION["role"]); ?></div>
              <a class="account-logout" href="./logout.php">Logout</a>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <section class="store-locator-wrap">
      <?php if (!empty($errorMessage)): ?>
        <div class="store-flash-error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <p class="store-muted"><a href="reader.php" class="btn">Back to shop</a></p>
      <?php else: ?>
        <div class="store-locator-hero">
          <div class="store-locator-book">
            <?php if (!empty($bookRow['cover_image'])): ?>
              <img src="<?php echo htmlspecialchars((string) $bookRow['cover_image']); ?>" alt="" />
            <?php else: ?>
              <div style="width:12rem;height:16rem;background:#eee;border-radius:8px;margin:0 auto;"></div>
            <?php endif; ?>
          </div>
          <div class="store-locator-meta">
            <h1><?php echo htmlspecialchars((string) $bookRow['title']); ?></h1>
            <p><strong>Author:</strong> <?php echo htmlspecialchars((string) $bookRow['author']); ?></p>
            <p><strong>ISBN:</strong> <?php echo htmlspecialchars((string) $bookRow['isbn']); ?></p>
          </div>
        </div>

        <div class="store-sort-bar">
          <?php if ($readerLat !== null && $readerLng !== null): ?>
            <span style="font-size:1.35rem;">Sorted by distance from your location.</span>
            <a class="btn" href="book-stores.php?book_id=<?php echo (int) $bookRow['book_id']; ?>">Sort by lowest price instead</a>
          <?php else: ?>
            <span style="font-size:1.35rem;">Sorted by price, then store name.</span>
            <button type="button" class="btn" id="geo-sort-btn">Sort by distance (use my location)</button>
          <?php endif; ?>
          <a class="btn" href="reader.php">Back</a>
        </div>

        <?php if (empty($stores)): ?>
          <p style="font-size:1.5rem;">No approved stores currently list this title in stock.</p>
        <?php else: ?>
          <table class="store-table">
            <thead>
              <tr>
                <th>Store</th>
                <th>Region</th>
                <th>Condition</th>
                <th>Price</th>
                <th>Available</th>
                <?php if ($readerLat !== null && $readerLng !== null): ?>
                  <th>Distance</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($stores as $s): ?>
                <tr>
                  <td>
                    <?php echo htmlspecialchars((string) $s['name']); ?>
                    <div style="font-size:1.15rem;color:#777;"><?php echo htmlspecialchars((string) $s['address']); ?></div>
                  </td>
                  <td><?php echo htmlspecialchars((string) ($s['region'] ?? '')); ?></td>
                  <td><?php echo htmlspecialchars((string) $s['condition']); ?></td>
                  <td><?php echo htmlspecialchars((string) $s['price_egp_formatted']); ?></td>
                  <td><?php echo (int) $s['available_to_buy']; ?></td>
                  <?php if ($readerLat !== null && $readerLng !== null): ?>
                    <td>
                      <?php
                      $dkm = $s['distance_km'];
                      echo $dkm !== null ? htmlspecialchars(number_format((float) $dkm, 1)) . ' km' : '&mdash;';
                      ?>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      <?php endif; ?>
    </section>

    <script>
      (function () {
        var toggle = document.getElementById("account-toggle");
        var panel = document.getElementById("account-panel");
        if (toggle && panel) {
          toggle.addEventListener("click", function (e) {
            e.preventDefault();
            panel.classList.toggle("show");
          });
          document.addEventListener("click", function (e) {
            if (!panel.contains(e.target) && !toggle.contains(e.target)) {
              panel.classList.remove("show");
            }
          });
        }
        var geoBtn = document.getElementById("geo-sort-btn");
        <?php if (!empty($bookRow) && isset($bookRow['book_id'])): ?>
        var bookId = <?php echo json_encode((int) $bookRow['book_id']); ?>;
        if (geoBtn && bookId && navigator.geolocation) {
          geoBtn.addEventListener("click", function () {
            geoBtn.disabled = true;
            navigator.geolocation.getCurrentPosition(
              function (pos) {
                var lat = pos.coords.latitude;
                var lng = pos.coords.longitude;
                window.location.href =
                  "book-stores.php?book_id=" +
                  encodeURIComponent(bookId) +
                  "&lat=" +
                  encodeURIComponent(lat) +
                  "&lng=" +
                  encodeURIComponent(lng);
              },
              function () {
                geoBtn.disabled = false;
                alert("Location permission is required for distance sorting.");
              }
            );
          });
        } else if (geoBtn && !navigator.geolocation) {
          geoBtn.style.display = "none";
        }
        <?php endif; ?>
      })();
    </script>
  </body>
</html>
