<?php
/**
 * FILE: cart.php
 * PURPOSE: Shows the reader shopping cart contents and checkout button, and displays success/error messages after cart actions.
 * USED BY: `public/cart.php` endpoint after it prepares `$cartItems`, `$totalPrice`, `$successMessage`, and `$errorMessage`.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */
?>
<?php // VIEW FOR: public/cart.php cart checkout UI ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- External CSS -->
  <link rel="stylesheet" href="./css/style.css" />
  <!-- Font Awesome-->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css"
  />
  <!-- Swiper -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css"
  />
  <link rel="stylesheet"
  href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

  <link rel="icon" type="image/svg+xml" href="./img/bookfavicon.svg" />
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
    .cart-flash {
      max-width: 900px;
      margin: 1rem auto;
      padding: 1rem 1.2rem;
      border-radius: 8px;
      font-size: 1.4rem;
    }
    .cart-flash.success { background: #e9f9ee; color: #1e6b36; border: 1px solid #97d8ab; }
    .cart-flash.error { background: #fdecec; color: #842029; border: 1px solid #f0b4bb; }
  </style>
  <title>IBRCN</title>
</head>
<body>
<?php
$isLoggedIn = isset($_SESSION["user"]) && isset($_SESSION["role"]);
$accountTitle = $isLoggedIn ? "Account" : "Sign In";
$cartItems = $cartItems ?? array();
$totalPrice = $totalPrice ?? 0.0;
$checkoutSuccess = !empty($checkoutSuccess);
?>
      <!-- Header Start  -->
      <header class="header">
        <!-- Header 1 Start  -->
        <div class="header-1">
          <a href="#" class="logo"><i class="fas fa-book"></i> IBRCN</a>
          <form action="" class="search-form">
            <input
              type="search"
              name=""
              placeholder="Search..."
              id="search-box"
            />
            <label for="search-box" type="submit" class="fas fa-search"></label>
          </form>
          <div class="icons">
            <div id="search-btn" class="fas fa-search"></div>
            <a href="#" class="fas fa-heart-circle-check"></a>
            <?php if ($isLoggedIn): ?>
            <a href="mailbox.php" class="fas fa-envelope" title="Mail"></a>
            <?php endif; ?>
            <a href="cart.php" class="fas fa-shopping-cart"></a>
            <?php if ($isLoggedIn): ?>
            <div class="account-menu">
              <a id="account-toggle" class="fa-solid fa-user" title="<?php echo $accountTitle; ?>" href="#"></a>
              <div id="account-panel" class="account-panel">
                <div class="account-name"><?php echo htmlspecialchars($_SESSION["user"]); ?></div>
                <div class="account-role"><?php echo htmlspecialchars($_SESSION["role"]); ?></div>
                <a class="account-logout" href="logout.php">Logout</a>
              </div>
            </div>
            <?php else: ?>
            <a id="login-btn" class="fa-solid fa-user" title="<?php echo $accountTitle; ?>" href="login.php"></a>
            <?php endif; ?>
          </div>
        </div>
        <!-- Header 1 End -->
  
        <!-- Header 2 Start -->
        <div class="header-2">
          <div class="navbar">
            <a class="active" href="index.php">Home</a>
            <a href="#about">About</a>
            <a href="#populer">Popular</a>
            <a href="#member">Member</a>
            <a href="#new">New</a>
            <a href="#reviews">Reviews</a>
            <a href="#blogs">Blogs</a>
          </div>
        </div>
        <!-- Header 2 End -->
      </header>
      <!-- Header End -->
  
      <!-- Bottom Navbar Start -->
      <div class="bottom-navbar">
        <a href="#home" class="fas fa-home"></a>
        <a href="#about" class="fas fa-people-group"></a>
        <a href="#populer" class="fas fa-fire"></a>
        <a href="#member" class="fas fa-user-plus"></a>
        <a href="#new" class="fas fa-book-bookmark"></a>
        <a href="#reviews" class="fas fa-star"></a>
        <a href="#blogs" class="fas fa-newspaper"></a>
      </div>
      <!-- Bottom Navbar End -->
      <section>
        <h2 id="title-sb">Your Shopping Bag</h2>
        <?php if (!empty($successMessage)): ?>
          <div class="cart-flash success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMessage)): ?>
          <div class="cart-flash error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>
        <div id="bagContent"></div>
        <p id="totalPrice">Total Price: EGP <?php echo number_format((float) $totalPrice, 2); ?></p>
        <form id="checkout-form" method="post" action="cart.php" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
          <button class="a2c_btn" type="submit" name="checkout_cart" value="1">Proceed to Checkout</button>
        </form>
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

    const serverCartItems = <?php echo json_encode(array_values($cartItems)); ?>;
    const checkoutSuccess = <?php echo json_encode($checkoutSuccess); ?>;

    (function syncStorageUserScope() {
      var uid = <?php echo (int) ($_SESSION['user_id'] ?? 0); ?>;
      var prev = parseInt(sessionStorage.getItem('ibrcn_ls_user') || '0', 10);
      if (uid > 0 && prev > 0 && uid !== prev) {
        try {
          localStorage.removeItem('cart');
          localStorage.removeItem('ibrcn_wishlist');
        } catch (e) {}
      }
      if (uid > 0) {
        sessionStorage.setItem('ibrcn_ls_user', String(uid));
      }
    })();

    var CART_STORAGE_KEY = <?php
      $uid = (int) ($_SESSION['user_id'] ?? 0);
      echo json_encode($uid > 0 ? 'ibrcn_cart_u' . $uid : 'cart');
    ?>;

    function readLocalCart() {
      try {
        return JSON.parse(localStorage.getItem(CART_STORAGE_KEY)) || [];
      } catch (e) {
        return [];
      }
    }

    function writeLocalCart(cart) {
      localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
    }

    function syncCart(cart) {
      return fetch('cart-sync.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ cart: cart })
      }).catch(function () {
        return null;
      });
    }

    function normalizeCartItem(item) {
      return {
        book_id: Number(item.book_id || item.bookId || 0),
        title: item.title || item.name || 'Untitled',
        author: item.author || '',
        image: item.image || '',
        unit_price: Number(item.unit_price || item.unitPrice || 0),
        quantity: Math.max(1, Number(item.quantity || 1))
      };
    }

    function renderCart(items) {
      const cartContainer = document.getElementById('bagContent');
      const normalizedItems = items.map(normalizeCartItem);

      if (!normalizedItems.length) {
        cartContainer.innerHTML = '<p>Your shopping bag is empty.</p>';
        document.getElementById('totalPrice').textContent = 'Total Price: EGP 0.00';
        return;
      }

      let total = 0;
      cartContainer.innerHTML = '';
      normalizedItems.forEach(function (item) {
        total += item.unit_price * item.quantity;
        cartContainer.insertAdjacentHTML('beforeend', `
          <div class="bag-item">
            <img src="${item.image}" alt="${item.title}" class="bag-item-img">
            <div class="bag-item-details">
              <h3>${item.title}</h3>
              <p>Quantity: <span>${item.quantity}</span></p>
              <p>Price: EGP ${Number(item.unit_price || 0).toFixed(2)}</p>
              <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.75rem;">
                <button class="btn cart-action-btn" data-book-id="${item.book_id}" data-cart-action="decrease" type="button">-</button>
                <button class="btn cart-action-btn" data-book-id="${item.book_id}" data-cart-action="increase" type="button">+</button>
                <button class="btn cart-action-btn" data-book-id="${item.book_id}" data-cart-action="remove" type="button">Remove</button>
              </div>
            </div>
          </div>
        `);
      });

      document.getElementById('totalPrice').textContent = 'Total Price: EGP ' + total.toFixed(2);
    }

    function setCartAndSync(cart) {
      writeLocalCart(cart);
      renderCart(cart);
      return syncCart(cart);
    }

    window.addEventListener('load', function () {
      const localCart = readLocalCart();
      const initialCart = checkoutSuccess ? [] : (serverCartItems.length ? serverCartItems : localCart);
      renderCart(initialCart);

      if (checkoutSuccess) {
        writeLocalCart([]);
        syncCart([]);
        setTimeout(function () {
          alert('Ordered successfully!');
        }, 50);
        return;
      }

      if (localCart.length && !serverCartItems.length) {
        syncCart(localCart);
      }

      // Clicks update localStorage; if the bag was rendered from the server session
      // but the user key was empty/stale, +/- / Remove had nothing to match.
      if (initialCart.length) {
        writeLocalCart(initialCart.map(normalizeCartItem));
      }
    });

    document.getElementById('bagContent').addEventListener('click', function (event) {
      const button = event.target.closest('.cart-action-btn');
      if (!button) return;

      const action = button.getAttribute('data-cart-action');
      const bookId = Number(button.getAttribute('data-book-id'));
      const cart = readLocalCart().map(normalizeCartItem);
      const index = cart.findIndex(function (item) {
        return Number(item.book_id) === bookId;
      });

      if (index === -1) return;

      if (action === 'increase') {
        cart[index].quantity += 1;
      } else if (action === 'decrease') {
        cart[index].quantity = Math.max(1, cart[index].quantity - 1);
      } else if (action === 'remove') {
        cart.splice(index, 1);
      }

      setCartAndSync(cart);
    });

    function toggleFavorite(el) {
      if (el) {
        el.classList.toggle('active');
      }
    }
  </script>

<?php require __DIR__ . '/../partials/site_footer.php'; ?>

</body>
</html>