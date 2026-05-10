<?php
/**
 * FILE: site_footer.php
 * PURPOSE: Shared site footer shown at the bottom of public pages (about, address, contact form).
 * USED BY: Included as a partial from multiple views (for example reader cart and reading clubs pages).
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */
?>
<?php // VIEW FOR: shared footer partial (member.php and others) ?>
    <!-- Footer Start -->
    <footer class="footer" id="footer">
      <div class="main-content">
        <div class="left box">
          <h2>About us</h2>
          <div class="content">
            <p>
              IBRCN (Independent Bookstore & Reader’s Club Network) is a web platform
      designed to connect readers with independent local bookstores through a
      unified digital experience. Our goal is to support small bookstores while
      making it easier for readers to discover, explore, and access books across
      multiple locations.
            </p>
            <br />
            <p>
              The platform allows users to browse books from different stores, place
                online orders for local pickup, and engage in community activities such as
                book clubs and reading discussions. By combining e-commerce with social
                interaction, IBRCN creates a space where readers and bookstores grow
                together in a shared ecosystem.
            </p>
            <div class="social">
              <a href="#footer"><span class="fab fa-facebook-f"></span></a>
              <a href="#footer"><span class="fab fa-twitter"></span></a>
              <a href="#footer"><span class="fab fa-instagram"></span></a>
              <a href="#footer"><span class="fa-brands fa-youtube"></span></a>
            </div>
          </div>
        </div>
        <div class="center box">
          <h2>Address</h2>
          <div class="content">
            <div class="place">
              <span class="fas fa-map-marker-alt"></span>
              <span class="text">Cairo, Egypt</span>
            </div>
            <div class="phone">
              <span class="fas fa-phone-alt"></span>
              <span class="text">+1203589391</span>
            </div>
            <div class="email">
              <span class="fas fa-envelope"></span>
              <span class="text">IBRCN@helwan.edu.eg.com</span>
            </div>
          </div>
        </div>
        <div class="right box">
          <h2>Contact us</h2>
          <div class="content">
            <form action="">
              <div class="email">
                <div class="text">Email</div>
                <input type="email"placeholder="Email address..." required />
              </div>
              <div class="msg">
                <div class="text">Message</div>
                <input type="text" placeholder="Your message..." required /></input>
              </div>
              <div class="btn">
                <button type="submit">Send</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="bottom">
          <span class="credit">Copyright <span class="far fa-copyright"></span> 2026 IBRCN_TEAM | All rights reserved.</span>
      </div>
    </footer>
    <!-- Footer End  -->
