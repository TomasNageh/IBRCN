<?php
/**
 * FILE: 404Error.php
 * PURPOSE: Shows the 404 “Page Not Found” screen for users when a route does not exist.
 * USED BY: `public/404Error.php` endpoint.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */
?>
<?php // VIEW FOR: public/404Error.php ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="./css/404Style.css" />
    <title>404 Error | IBRCN</title>
  </head>
  <body>
    <!-- Nav Start -->
    <nav>
      <a href="#" class="logo">IBRCN</a>
      <a href="./login.php">
        <button>Sign In</button>
      </a>
    </nav>
    <!-- Nav End -->

    <!-- Main Content Start -->
    <section class="page-not-found">
      <img src="./img/404Image.svg" alt="" />
      <h1>Page Not Found</h1>
      <p>
        Sorry can't find or reach the page you are looking for!
        <a href="./index.php">Click Here</a> to come back to home page
      </p>
      <a href="index.php">
        <button type="button" href="./index.php" class="btn-home">
          Back To Home
        </button>
      </a>
    </section>
    <!-- Main Content End -->
  </body>
</html>
