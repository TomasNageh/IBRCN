<?php
/**
 * FILE: home.php
 * PURPOSE: Shows the Reader storefront homepage with featured/recommended books and (if signed in) a summary of the reader’s clubs.
 * USED BY: `HomeController::index()` which prepares `$books` and optional `$myClubs` before including this view.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */

// VIEW FOR: HomeController::index
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$readerStorageUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$isLoggedIn = isset($_SESSION['user']) && isset($_SESSION['role']);
$accountTitle = $isLoggedIn ? 'Account' : 'Sign In';
$books = $books ?? array();
$myClubs = $myClubs ?? array();
?>
<!-- Reader storefront view: expects $books and optional $myClubs from HomeController -->
<!DOCTYPE html>
<html lang="en" data-user-id="<?php echo (int) $readerStorageUserId; ?>">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
    <link rel="icon" type="image/svg"  href="./img/bookfavicon.svg" />
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
      .reader-search-results { padding: 2rem 9% !important; }
      .reader-wishlist { padding: 2rem 9% !important; }
      .reader-book-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
        justify-content: center;
        align-items: stretch;
        padding: 2rem 0;
      }
      .reader-wishlist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(28rem, 1fr));
        gap: 1.8rem;
        align-items: stretch;
        padding: 2rem 0;
      }
      .wishlist-card {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 1.4rem;
        overflow: hidden;
        box-shadow: 0 1rem 2.5rem rgba(0, 0, 0, 0.08);
      }
      .wishlist-card__image {
        background: linear-gradient(15deg, #f2f2f2 30%, #fff 30.2%);
        padding: 1.4rem;
        display: flex;
        justify-content: center;
      }
      .wishlist-card__image img {
        width: 100%;
        max-width: 18rem;
        aspect-ratio: 3 / 4;
        object-fit: cover;
        border-radius: 0.8rem;
        box-shadow: 0 0.8rem 1.8rem rgba(0, 0, 0, 0.14);
      }
      .wishlist-card__content {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        padding: 1.4rem 1.6rem 1.6rem;
      }
      .wishlist-card__title {
        font-size: 2rem;
        color: var(--black);
        margin: 0;
        line-height: 1.2;
      }
      .wishlist-card__meta {
        font-size: 1.3rem;
        color: var(--light-color);
      }
      .wishlist-card__price {
        font-size: 1.8rem;
        color: var(--primaryColor);
        font-weight: 600;
      }
      .wishlist-card__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 0.4rem;
      }
      .wishlist-card__actions .btn,
      .wishlist-card__actions .wishlist-remove-btn {
        flex: 1 1 14rem;
        text-align: center;
        border-radius: 0.8rem;
      }
      .wishlist-remove-btn {
        display: inline-block;
        border: 1px solid #d9534f;
        background: transparent;
        color: #d9534f;
        padding: 1rem 1.4rem;
        font-size: 1.5rem;
        cursor: pointer;
      }
      .wishlist-remove-btn:hover {
        background: #d9534f;
        color: #fff;
      }
    </style>
    <title>IBRCN</title>
  </head>
  <body>

    <!-- Header Start  -->
    <header class="header">
      <!-- Header 1 Start  -->
      <div class="header-1">
        <a href="./index.php" class="logo"><i class="fas fa-book"></i> IBRCN</a>
        <form action="#" class="search-form" id="reader-search-form" autocomplete="off">
          <input
            type="search"
            name="q"
            placeholder="Search books by title, author, or ISBN..."
            id="search-box"
          />
          <label for="search-box" class="fas fa-search" id="reader-search-submit" title="Search"></label>
        </form>
        <div class="icons">
          <div id="search-btn" class="fas fa-search"></div>
          <a href="#wishlist-section" class="fas fa-heart-circle-check" id="reader-wishlist-nav" title="Wishlist"></a>
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
              <a class="account-logout" href="./logout.php">Logout</a>
            </div>
          </div>
          <?php else: ?>
          <a id="login-btn" class="fa-solid fa-user" title="<?php echo $accountTitle; ?>" href="./login.php"></a>
          <?php endif; ?>
        </div>
      </div>
      <!-- Header 1 End -->

      <!-- Header 2 Start -->
      <div class="header-2">
        <div class="navbar">
          <a class="active" href="#home">Home</a>
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

    <!-- Home Section Start -->
    <section class="home" id="home">
      <div class="row">
        <div class="content">
          <h3>Books Feed Your Soul</h3>
          <p>Exercise to the body is the same as reading to the mind.
            Prepare to enter the incredible world of literature.
          </p>
          <a href="#populer" class="btn">Shop Now !</a>
        </div>

        <div class="swiper books-slider">
          <div class="swiper-wrapper">
            <a href="#populer" class="swiper-slide"
              ><img src="./img/book-1.png" alt=""
            /></a>
            <a href="#populer" class="swiper-slide"
              ><img src="./img/book-2.png" alt=""
            /></a>
            <a href="#populer" class="swiper-slide"
              ><img src="./img/book-3.png" alt=""
            /></a>
            <a href="#populer" class="swiper-slide"
              ><img src="./img/book-4.png" alt=""
            /></a>
            <a href="#populer" class="swiper-slide"
              ><img src="./img/book-5.png" alt=""
            /></a>
            <a href="#populer" class="swiper-slide"
              ><img src="./img/book-6.png" alt=""
            /></a>
          </div>
          <img src="./img/stand.png" class="stand" alt="" />
        </div>
      </div>
    </section>
    <!-- Home Section End -->

    <!-- About Us Start -->
    <section id="about" class="about">
      <div class="container">
        <h1>WHY CHOOSE US?</h1>
        <div class="row">
          <div class="image">
            <img src="./img/img4.svg" alt="" />
          </div>

          <div class="content">
            <h3>best book store in the world</h3>
            <p>We have the books from the best authors of the world with their latest works that will captivate
              your senses to imagine the world writer wants to see.
            </p>
            <p>
              Always be ready to check our latest stock so you don't miss out on the great works of the best seller authors.
            </p>
            <div class="icons-container">
              <div class="icons">
                <i class="fas fa-shield"></i>
                <span>save delivery</span>
              </div>
              <div class="icons">
                <i class="fas fa-wallet"></i>
                <span>easy payments</span>
              </div>
              <div class="icons">
                <i class="fas fa-headset"></i>
                <span>24/7 service</span>
              </div>
            </div>
            <a href="./404Error.php" class="btn">learn more</a>
          </div>
        </div>
      </div>
    </section>
    <!-- About Us End -->

    <section id="search-results-section" class="reader-search-results" style="display: none;">
      <h1 class="heading"><span>Search Results</span></h1>
      <p id="search-results-status" style="text-align: center; font-size: 1.4rem; color: #666; display: none;"></p>
      <p id="search-results-empty" style="display: none; text-align: center; font-size: 1.5rem;">No books found.</p>
      <div id="search-results-grid" class="reader-book-grid"></div>
    </section>

    <!-- Popular Book Start -->
    <section class="populer" id="populer">
      <h1 class="heading"><span> Popular Books </span></h1>
      <div class="swiper populer-slider">
        <div class="swiper-wrapper">
          <?php if (!empty($books)): ?>
            <?php foreach ($books as $book): ?>
              <?php
              $safeTitle = htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8');
              $safeAuthor = htmlspecialchars(isset($book['author']) ? $book['author'] : '', ENT_QUOTES, 'UTF-8');
              $safePrice = htmlspecialchars($book['price_egp_formatted'], ENT_QUOTES, 'UTF-8');
              $safePriceValue = htmlspecialchars((string) $book['price_value'], ENT_QUOTES, 'UTF-8');
              $safeImg = htmlspecialchars($book['cover_image'], ENT_QUOTES, 'UTF-8');
              $safeId = (int) $book['book_id'];
              ?>
              <div class="swiper-slide box">
                <div class="icons">
                  <a href="#" class="fas fa-search reader-prefill-search" aria-label="Search this title" data-book-title="<?php echo $safeTitle; ?>"></a>
                  <a href="#" class="fas fa-heart-circle-plus reader-wishlist-toggle" aria-label="Wishlist"
                     data-book-id="<?php echo $safeId; ?>"
                     data-book-title="<?php echo $safeTitle; ?>"
                     data-book-price="<?php echo $safePrice; ?>"
                     data-book-price-value="<?php echo $safePriceValue; ?>"
                     data-book-img="<?php echo $safeImg; ?>"
                     data-book-author="<?php echo $safeAuthor; ?>"></a>
                  <a href="#" class="fas fa-info reader-book-info" aria-label="Book info"
                     data-book-title="<?php echo $safeTitle; ?>"
                     data-book-author="<?php echo $safeAuthor; ?>"
                     data-book-price="<?php echo $safePrice; ?>"></a>
                </div>
                <div class="image">
                  <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="" />
                </div>
                <div class="content">
                  <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                  <div class="price"><?php echo htmlspecialchars($book['price_egp_formatted']); ?></div>
                  <a href="book-stores.php?book_id=<?php echo $safeId; ?>"
                     style="display:block;text-align:center;font-size:1.25rem;margin:0.4rem 0 0.75rem;color:#27ae60;">Pickup locations</a>
                  <a href="#populer" class="btn reader-add-cart"
                     data-book-id="<?php echo $safeId; ?>"
                     data-book-title="<?php echo $safeTitle; ?>"
                     data-book-price="<?php echo $safePrice; ?>"
                     data-book-price-value="<?php echo $safePriceValue; ?>"
                     data-book-img="<?php echo $safeImg; ?>">Add To Cart</a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="text-align: center; font-size: 1.5rem;">No books available right now.</p>
          <?php endif; ?>
        </div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
      </div>
    </section>
    <!-- Popular Book End -->

    <section id="wishlist-section" class="reader-wishlist">
      <h1 class="heading"><span>Your Wishlist</span></h1>
      <p id="wishlist-empty" style="text-align: center; font-size: 1.5rem;">
        No books in your wishlist yet. Tap the heart on a book to save it.
      </p>
      <div id="wishlist-grid" class="reader-wishlist-grid"></div>
    </section>

    <!-- Member Start -->
    <section id="member" class="member">
      <div class="container">
        <h1>Join or Create a Reading Club</h1>
        <div class="row">
          <div class="content">
            <h3>
              Join or <span>create a reading club</span> with your friends<br />
              and share books, discussions, and events together!
            </h3>
            <p>
              By starting or joining a club you can organize group reads, share recommendations, and get
              exclusive coupons of up to 40% for club members. Refer friends to grow your club and earn
              a coupon of EGP 50 for successful referrals.
            </p>
            <p>
              Stay informed about club meetups, reading schedules, and special offers — invite friends and
              make reading social again.
            </p>
            <form action="notify_subscribers.php" method="post">
              <input
                type="email"
                name="email"
                placeholder="Enter your email..."
                class="box"
                required
              />
              <input type="submit" value="Get Notified" class="btn" />
              <a href="./member.php" class="btn">Create / Join Club</a>
            </form>
          </div>
          <div class="image">
            <img src="./img/img5.svg" alt="" />
          </div>
        </div>
      </div>
    </section>
    <!-- Member End -->

    <?php if (!empty($myClubs)): ?>
    <section id="my-reading-clubs" class="member" style="padding-top: 0;">
      <div class="container">
        <h1 class="heading"><span>Your Reading Clubs</span></h1>
        <p style="font-size: 1.35rem; color: #555; max-width: 62ch;">
          You are a member of these clubs — see what everyone is reading in each club.
        </p>
        <ul style="font-size: 1.4rem; line-height: 1.7; margin-top: 1rem;">
          <?php foreach ($myClubs as $club): ?>
            <li style="margin-bottom: 1.25rem;">
              <strong><?php echo htmlspecialchars((string) $club['name']); ?></strong>
              <?php if (!empty($club['description'])): ?>
                <span style="color: #666"> — <?php echo htmlspecialchars((string) $club['description']); ?></span>
              <?php endif; ?>
              <?php if (!empty($club['member_reads'])): ?>
                <ul style="font-size: 1.2rem; color: #444; margin: 0.5rem 0 0; padding-left: 1.35rem; list-style: disc;">
                  <?php foreach ($club['member_reads'] as $mr): ?>
                    <li style="margin: 0.25rem 0;">
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
        <p style="margin-top: 1rem;">
          <a href="./member.php" class="btn">Manage clubs</a>
        </p>
      </div>
    </section>
    <?php endif; ?>

    <!-- New Book Start -->
    <section class="new" id="new">
      <h1 class="heading"><span>New Books</span></h1>

      <!-- New Books  Section 1 Starts-->
      <div class="swiper new-slider">
        <div class="swiper-wrapper">
          <a href="#new" class="swiper-slide box">
            <div class="image">
              <img src="./img/book-1.png" alt="" />
            </div>
            <div class="content">
              <h3>New Books</h3>
              <div class="price">EGP 75 <span>EGP 90</span></div>
              <div class="stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </a>

          <a href="#new" class="swiper-slide box">
            <div class="image">
              <img src="./img/book-2.png" alt="" />
            </div>
            <div class="content">
              <h3>New Books</h3>
              <div class="price">EGP 75 <span>EGP 90</span></div>
              <div class="stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </a>

          <a href="#new" class="swiper-slide box">
            <div class="image">
              <img src="./img/book-3.png" alt="" />
            </div>
            <div class="content">
              <h3>New Books</h3>
              <div class="price">EGP 75 <span>EGP 90</span></div>
              <div class="stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </a>

          <a href="#new" class="swiper-slide box">
            <div class="image">
              <img src="./img/book-4.png" alt="" />
            </div>
            <div class="content">
              <h3>New Books</h3>
              <div class="price">EGP 75 <span>EGP 90</span></div>
              <div class="stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </a>

          <a href="#new" class="swiper-slide box">
            <div class="image">
              <img src="./img/book-5.png" alt="" />
            </div>
            <div class="content">
              <h3>New Books</h3>
              <div class="price">EGP 75 <span>EGP 90</span></div>
              <div class="stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </a>
        </div>
      </div>
      <!-- New Books Section 1 End -->

      <!-- New Books Section 2 Start  -->
      <div class="swiper new-slider-2">
        <div class="swiper-wrapper">
          <a href="#new" class="swiper-slide box">
            <div class="image">
              <img src="./img/book-6.png" alt="" />
            </div>
            <div class="content">
              <h3>New Books</h3>
              <div class="price">EGP 75 <span>EGP 90</span></div>
              <div class="stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </a>

          <a href="#new" class="swiper-slide box">
            <div class="image">
              <img src="./img/book-7.png" alt="" />
            </div>
            <div class="content">
              <h3>New Books</h3>
              <div class="price">EGP 75 <span>EGP 90</span></div>
              <div class="stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </a>

          <a href="#new" class="swiper-slide box">
            <div class="image">
              <img src="./img/book-8.png" alt="" />
            </div>
            <div class="content">
              <h3>New Books</h3>
              <div class="price">EGP 75 <span>EGP 90</span></div>
              <div class="stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </a>

          <a href="#new" class="swiper-slide box">
            <div class="image">
              <img src="./img/book-9.png" alt="" />
            </div>
            <div class="content">
              <h3>New Books</h3>
              <div class="price">EGP 75 <span>EGP 90</span></div>
              <div class="stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </a>

          <a href="#new" class="swiper-slide box">
            <div class="image">
              <img src="./img/book-10.png" alt="" />
            </div>
            <div class="content">
              <h3>New Books</h3>
              <div class="price">EGP 75 <span>EGP 90</span></div>
              <div class="stars">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </a>
        </div>
      </div>
      <!-- New Books Section 2 End-->
    </section>
    <!-- New Book End -->

    <!-- Review Start -->
    <section class="reviews" id="reviews">
      <h1>client's reviews</h1>
      <div class="swiper reviews-slider">
        <div class="swiper-wrapper">
          <div class="swiper-slide box">
            <i class="fas fa-quote-left quote"></i>
            <p>
              One of the best emerging platforms for purchasing books and 3 day delivery if you are living in urban 
              areas.Just excellent service.
            </p>
            <div class="content">
              <div class="info">
                <div class="name">Chetan Vemula</div>
                <div class="job">Web Dev</div>
                <div class="stars">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                </div>
              </div>
              <div class="image">
                <img src="./img/avatar4.png" alt="" />
              </div>
            </div>
          </div>
          <div class="swiper-slide box">
            <i class="fas fa-quote-left quote"></i>
            <p>
              The best ever platform for complaint redressal and return policy, respect for the customers.Hope this will 
              become my go-to site for book purchase.
            </p>
            <div class="content">
              <div class="info">
                <div class="name">Sapare Aravind</div>
                <div class="job">YouTuber</div>
                <div class="stars">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star-half-alt"></i>
                  <i class="far fa-star"></i>
                </div>
              </div>
              <div class="image">
                <img src="./img/avatar2.svg" alt="" />
              </div>
            </div>
          </div>

          <div class="swiper-slide box">
            <i class="fas fa-quote-left quote"></i>
            <p>
              This website has not only has the fiction and Novels but also the books on education, entreprenaurship 
              and leadership. Just thought that it is another website with inflated prices, that was far from it.
            </p>
            <div class="content">
              <div class="info">
                <div class="name">Abhinav Vuddagiri</div>
                <div class="job">Programmer</div>
                <div class="stars">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="far fa-star"></i>
                </div>
              </div>
              <div class="image">
                <img src="./img/avatar5.svg" alt="" />
              </div>
            </div>
          </div>
          <div class="swiper-slide box">
            <i class="fas fa-quote-left quote"></i>
            <p>
              Always with the good recommendation for buying books, when I come to browse I always find at least one book 
              interesting to read. Worth the time spent. 
            </p>
            <div class="content">
              <div class="info">
                <div class="name">Siva Gopal</div>
                <div class="job">Freelancer</div>
                <div class="stars">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="far fa-star"></i>
                </div>
              </div>
              <div class="image">
                <img src="./img/avatar3.svg" alt="" />
              </div>
            </div>
          </div>

          <div class="swiper-slide box">
            <i class="fas fa-quote-left quote"></i>
            <p>
              I'm thrilled to be your go-to for book recommendations! There's just something magical about stumbling upon that perfect book, isn't there? Whether it's a captivating story or a mind-blowing non-fiction piece.
            <div class="content">
              <div class="info">
                <div class="name">Nithish Reddy</div>
                <div class="job">Doctor</div>
                <div class="stars">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="far fa-star"></i>
                </div>
              </div>
              <div class="image">
                <img src="./img/avatar6.svg" alt="" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- Review End -->

    <!-- Blogs Start -->
    <section class="blogs" id="blogs">
      <div class="container">
        <h1 class="heading"><span>our daily posts</span></h1>

        <div class="box-container">
          <div class="box">
            <div class="image">
              <img src="./img/blog1.jpg" alt="" />
            </div>
            <div class="content">
              <h3>Experiencing the joy of literature in Nature</h3>
              <p>
                Lorem ipsum dolor sit amet consectetur, adipisicing elit. Quod,
                adipisci!
              </p>
              <a href="./404Error.php" class="btn">read more</a>
              <div class="icons">
                <span> <i class="fas fa-calendar"></i> 12st sep, 2022 </span>
                <span> <i class="fas fa-user"></i> by admin </span>
              </div>
            </div>
          </div>

          <div class="box">
            <div class="image">
              <img src="./img/blog3.jpg" alt="" />
            </div>
            <div class="content">
              <h3>blog title goes here</h3>
              <p>
                Lorem ipsum dolor sit amet consectetur, adipisicing elit. Quod,
                adipisci!
              </p>
              <a href="./404Error.php" class="btn">read more</a>
              <div class="icons">
                <span> <i class="fas fa-calendar"></i> 12st sep, 2022 </span>
                <span> <i class="fas fa-user"></i> by admin </span>
              </div>
            </div>
          </div>

          <div class="box">
            <div class="image">
              <img src="./img/blog2.jpg" alt="" />
            </div>
            <div class="content">
              <h3>blog title goes here</h3>
              <p>
                Lorem ipsum dolor sit amet consectetur, adipisicing elit. Quod,
                adipisci!
              </p>
              <a href="./404Error.php" class="btn">read more</a>
              <div class="icons">
                <span> <i class="fas fa-calendar"></i> 12st sep, 2022 </span>
                <span> <i class="fas fa-user"></i> by admin </span>
              </div>
            </div>
          </div>

          <div class="box">
            <div class="image">
              <img src="./img/blog4.jpg" alt="" />
            </div>
            <div class="content">
              <h3>blog title goes here</h3>
              <p>
                Lorem ipsum dolor sit amet consectetur, adipisicing elit. Quod,
                adipisci!
              </p>
              <a href="./404Error.php" class="btn">read more</a>
              <div class="icons">
                <span> <i class="fas fa-calendar"></i> 12st sep, 2022 </span>
                <span> <i class="fas fa-user"></i> by admin </span>
              </div>
            </div>
          </div>

          <div class="box">
            <div class="image">
              <img src="./img/blog5.jpg" alt="" />
            </div>
            <div class="content">
              <h3>blog title goes here</h3>
              <p>
                Lorem ipsum dolor sit amet consectetur, adipisicing elit. Quod,
                adipisci!
              </p>
              <a href="./404Error.php" class="btn">read more</a>
              <div class="icons">
                <span> <i class="fas fa-calendar"></i> 12st sep, 2022 </span>
                <span> <i class="fas fa-user"></i> by admin </span>
              </div>
            </div>
          </div>

          <div class="box">
            <div class="image">
              <img src="./img/blog6.jpg" alt="" />
            </div>
            <div class="content">
              <h3>blog title goes here</h3>
              <p>
                Lorem ipsum dolor sit amet consectetur, adipisicing elit. Quod,
                adipisci!
              </p>
              <a href="./404Error.php" class="btn">read more</a>
              <div class="icons">
                <span> <i class="fas fa-calendar"></i> 12st sep, 2022 </span>
                <span> <i class="fas fa-user"></i> by admin </span>
              </div>
            </div>
          </div>

          <div class="box">
            <div class="image">
              <img src="./img/blog7.jpg" alt="" />
            </div>
            <div class="content">
              <h3>blog title goes here</h3>
              <p>
                Lorem ipsum dolor sit amet consectetur, adipisicing elit. Quod,
                adipisci!
              </p>
              <a href="./404Error.php" class="btn">read more</a>
              <div class="icons">
                <span> <i class="fas fa-calendar"></i> 12st sep, 2022 </span>
                <span> <i class="fas fa-user"></i> by admin </span>
              </div>
            </div>
          </div>

          <div class="box">
            <div class="image">
              <img src="./img/blog8.jpg" alt="" />
            </div>
            <div class="content">
              <h3>blog title goes here</h3>
              <p>
                Lorem ipsum dolor sit amet consectetur, adipisicing elit. Quod,
                adipisci!
              </p>
              <a href="./404Error.php" class="btn">read more</a>
              <div class="icons">
                <span> <i class="fas fa-calendar"></i> 12st sep, 2022 </span>
                <span> <i class="fas fa-user"></i> by admin </span>
              </div>
            </div>
          </div>

          <div class="box">
            <div class="image">
              <img src="./img/blog9.jpg" alt="" />
            </div>
            <div class="content">
              <h3>blog title goes here</h3>
              <p>
                Lorem ipsum dolor sit amet consectetur, adipisicing elit. Quod,
                adipisci!
              </p>
              <a href="./404Error.php" class="btn">read more</a>
              <div class="icons">
                <span> <i class="fas fa-calendar"></i> 12st sep, 2022 </span>
                <span> <i class="fas fa-user"></i> by admin </span>
              </div>
            </div>
          </div>
        </div>
        <div id="load-more">load more</div>
      </div>
    </section>
    <!-- Blogs End -->

    <?php require __DIR__ . '/../partials/site_footer.php'; ?>

    <!-- Loader Start-->
    <div class="loader-container">
      <!-- Gif Source Link: https://tenor.com/view/gb-notebook-laptop-gif-17733403 -->
      <img src="./img/mainLoader.gif" alt="">
    </div>
    <!-- Loader End -->

    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
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
    <script src="./js/script.js"></script>
  </body>
</html>
