<?php
/**
 * FILE: register.php
 * PURPOSE: Shows the registration form where new users create an account (Reader/Owner/Admin).
 * USED BY: `AuthController::showRegister()` and `AuthController::register()` rendering flows.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */
?>
<?php // VIEW FOR: AuthController::showRegister / AuthController::register ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"
      crossorigin="anonymous"
    />

    <link rel="stylesheet" href="./css/loginReg.css" />
    <script src="./js/script.js" defer></script>

    <title>Register | IBRCN</title>
  </head>
  <body>
    <div class="container">
      <?php if (!empty($errorMessage)): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
      <?php endif; ?>

      <div class="row">
        <div class="mx-auto mt-2 text-center mt-3">
          <img src="./img/booklogo.png" width="4%" alt="Logo Barber" />
        </div>
      </div>
      <div class="row mt-5">
        <div class="col-md-6">
          <img
            class="img-register img-fluid"
            src="./img/img2.svg"
            width="435px"
            alt="Register Image"
          />
        </div>
        <div class="col-md-6 mt-3">
          <div class="card shadow-sm p-3">
            <div class="card-body">
              <h4>Sign Up</h4>
              <p class="text-muted">Sign Up for the Best Book!</p>
              <!-- Your HTML code for the registration form goes here -->
<form action="register.php" class="mt-4" method="post">
    <div class="form-group">
        <input type="text" class="form-control" name="username" id="username" placeholder="Name" value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" required />
    </div>
    <div class="form-group mt-2">
        <input type="text" class="form-control" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required />
    </div>
    <div class="input-group mt-2">
        <input type="password" class="form-control" placeholder="Password" id="password" name="password" required />
        <div class="input-group-append">
            <span class="input-group-text" onclick="displayPassword()">
                <i id="display-pass" data-feather="eye"></i>
                <i id="hiden-pass" data-feather="eye-off"></i>
            </span>
        </div>
    </div>
    <div class="input-group mt-2">
        <input
            type="password"
            class="form-control"
            placeholder="Confirm Password"
            id="passwordConfirm"
            name="password2"
            required
        />
        <div class="input-group-append">
            <span
                class="input-group-text"
                onclick="displayPasswordConfirm()"
            >
                <i id="display-passConfirm" data-feather="eye"></i>
                <i id="hiden-passConfirm" data-feather="eye-off"></i>
            </span>
        </div>
    </div>
    <div class="form-group mt-2">
        <select class="form-control" name="role" required>
            <option value="Reader" <?php echo (($formData['role'] ?? 'Reader') === 'Reader') ? 'selected' : ''; ?>>Reader</option>
            <option value="Owner" <?php echo (($formData['role'] ?? 'Reader') === 'Owner') ? 'selected' : ''; ?>>Owner</option>
            <option value="Admin" <?php echo (($formData['role'] ?? 'Reader') === 'Admin') ? 'selected' : ''; ?>>Admin</option>
        </select>
    </div>
    <div class="d-grid gap-2">
        <button class="btn btn-primary mt-2 btn-color-theme" type="submit" name="register">Sign Up</button>
        <p class="text-center">
            Already have an account?
            <a class="text-theme" href="login.php">Sign In</a>
        </p>
    </div>
</form>

            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="https://unpkg.com/feather-icons"></script>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
      crossorigin="anonymous"
    ></script>

    <script>
      feather.replace();
    </script>
  </body>
</html>
