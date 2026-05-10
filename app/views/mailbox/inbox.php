<?php
/**
 * FILE: inbox.php
 * PURPOSE: Shows the mailbox inbox list and the selected message details for the signed-in user.
 * USED BY: `public/mailbox.php` endpoint after `MailboxService` prepares `$messages` and `$detail`.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */
?>
<?php // VIEW FOR: public/mailbox.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css" />
  <link rel="icon" type="image/svg" href="./img/bookfavicon.svg" />
  <style>
    .mail-wrap { max-width: 920px; margin: 2rem auto 4rem; padding: 0 1.5rem; }
    .mail-head { margin-bottom: 1.5rem; }
    .mail-head h1 { margin: 0 0 0.35rem; font-size: 2rem; }
    .mail-muted { color: #666; font-size: 1.2rem; margin: 0; }
    .mail-table { width: 100%; border-collapse: collapse; font-size: 1.25rem; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
    .mail-table th, .mail-table td { border: 1px solid #e8e8e8; padding: 0.75rem 1rem; text-align: left; vertical-align: top; }
    .mail-table th { background: #f8f8f8; font-weight: 600; }
    .mail-table a.subject-link { color: var(--primaryColor, #2e7d32); font-weight: 600; text-decoration: none; }
    .mail-table a.subject-link:hover { text-decoration: underline; }
    .mail-preview { color: #555; font-size: 1.05rem; max-width: 36ch; }
    .mail-detail { background: #fff; border: 1px solid #e8e8e8; border-radius: 10px; padding: 1.25rem 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
    .mail-detail-meta { font-size: 1.1rem; color: #666; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #eee; }
    .mail-detail-body { font-size: 1.3rem; line-height: 1.55; color: #222; }
    .mail-back { display: inline-block; margin-bottom: 1rem; font-size: 1.2rem; }
    .mail-empty { padding: 2rem; text-align: center; color: #666; font-size: 1.35rem; background: #fafafa; border-radius: 8px; }
    .mail-flash { padding: 1rem 1.2rem; border-radius: 8px; margin-bottom: 1rem; font-size: 1.2rem; background: #fdecec; color: #842029; border: 1px solid #f0b4bb; }
  </style>
  <title>Mail | IBRCN</title>
</head>
<body>
  <header class="header">
    <div class="header-1">
      <a href="<?php echo htmlspecialchars($backHref); ?>" class="logo"><i class="fas fa-book"></i> IBRCN</a>
      <div class="icons">
        <a href="mailbox.php" class="fas fa-envelope" title="Mail"></a>
        <a href="<?php echo htmlspecialchars($readerHref); ?>" class="fas fa-book-open-reader" title="Shop"></a>
        <div class="account-menu">
          <a id="account-toggle" href="#" class="fas fa-user" title="Account"></a>
          <div id="account-panel" class="account-panel">
            <div class="account-name"><?php echo htmlspecialchars((string) ($_SESSION['user'] ?? '')); ?></div>
            <div class="account-role"><?php echo htmlspecialchars((string) ($_SESSION['role'] ?? '')); ?></div>
            <a class="account-logout" href="logout.php">Logout</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="mail-wrap">
    <div class="mail-head">
      <h1><i class="fas fa-envelope" style="margin-right:0.35rem"></i> Your mail</h1>
      <p class="mail-muted">Messages sent to <strong><?php echo htmlspecialchars($userEmail); ?></strong> (IBRCN notifications and alerts).</p>
    </div>

    <?php if (!empty($invalidSelection)): ?>
      <div class="mail-flash">That message was not found or is not addressed to your account.</div>
    <?php endif; ?>

    <?php if ($detail !== null): ?>
      <a class="mail-back btn" href="mailbox.php"><i class="fas fa-arrow-left"></i> All messages</a>
      <article class="mail-detail">
        <h2 style="margin:0 0 0.75rem;font-size:1.65rem"><?php echo htmlspecialchars($detail['subject'] ?: '(No subject)'); ?></h2>
        <div class="mail-detail-meta">
          <?php echo htmlspecialchars($detail['date'] ?? ''); ?>
          <?php if (!empty($detail['to_raw'])): ?>
            <br /><span style="font-size:1rem">To: <?php echo htmlspecialchars($detail['to_raw']); ?></span>
          <?php endif; ?>
        </div>
        <div class="mail-detail-body">
          <?php if (($detail['text_body'] ?? '') !== ''): ?>
            <?php echo nl2br(htmlspecialchars($detail['text_body'])); ?>
          <?php elseif (($detail['html_body'] ?? '') !== ''): ?>
            <?php echo nl2br(htmlspecialchars(strip_tags($detail['html_body']))); ?>
          <?php else: ?>
            <em style="color:#999">(Empty message)</em>
          <?php endif; ?>
        </div>
      </article>
    <?php elseif (empty($messages)): ?>
      <div class="mail-empty">No messages stored for your account yet. Order updates and notifications will appear here when mail is sent.</div>
    <?php else: ?>
      <table class="mail-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Subject</th>
            <th>Preview</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($messages as $row): ?>
            <tr>
              <td style="white-space:nowrap;font-size:1.05rem;color:#555"><?php echo htmlspecialchars($row['date']); ?></td>
              <td>
                <a class="subject-link" href="mailbox.php?m=<?php echo rawurlencode($row['filename']); ?>">
                  <?php echo htmlspecialchars($row['subject']); ?>
                </a>
              </td>
              <td><span class="mail-preview"><?php echo htmlspecialchars($row['preview']); ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

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
  <script>
    (function () {
      var t = document.getElementById('account-toggle');
      var p = document.getElementById('account-panel');
      if (!t || !p) return;
      t.addEventListener('click', function (e) {
        e.preventDefault();
        p.classList.toggle('show');
      });
      document.addEventListener('click', function (e) {
        if (!t.contains(e.target) && !p.contains(e.target)) p.classList.remove('show');
      });
    })();
  </script>
</body>
</html>
