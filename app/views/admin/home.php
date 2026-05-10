<?php
/**
 * FILE: home.php
 * PURPOSE: Shows the Admin dashboard for bookstore approvals and user/role management.
 * USED BY: `public/admin.php` and `public/admin-handler.php` endpoints after `AdminPanelService` loads view variables.
 * DESIGN PATTERN: None (views do not contain pattern logic)
 */
?>
<?php // VIEW FOR: public/admin.php and public/admin-handler.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css" />
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
    .admin-tabs { display: flex; gap: 1rem; margin: 1.6rem 0; border-bottom: 2px solid #e6e6e6; }
    .admin-tabs button { background: none; border: none; padding: 0.8rem 1.6rem; font-size: 1.3rem; cursor: pointer; border-bottom: 3px solid transparent; color: #666; transition: all 0.3s; }
    .admin-tabs button.active { color: var(--primaryColor); border-bottom-color: var(--primaryColor); }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    .admin-table th { background: #f5f5f5; padding: 1rem; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #e6e6e6; }
    .admin-table td { padding: 1rem; border-bottom: 1px solid #e6e6e6; }
    .admin-table tr:hover { background: #fafafa; }
    .status-badge { display: inline-block; padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.9rem; font-weight: 600; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-approved { background: #d4edda; color: #155724; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .admin-actions { display: flex; gap: 0.5rem; }
    .admin-actions form { display: inline; }
    .admin-actions button { padding: 0.5rem 1rem; font-size: 0.95rem; border: none; border-radius: 4px; cursor: pointer; }
    .btn-approve { background: #28a745; color: #fff; }
    .btn-approve:hover { background: #218838; }
    .btn-reject { background: #dc3545; color: #fff; }
    .btn-reject:hover { background: #c82333; }
    .btn-delete { background: #dc3545; color: #fff; font-size: 0.85rem; }
    .btn-delete:hover { background: #c82333; }
    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
    }
    .modal.show { display: flex; align-items: center; justify-content: center; }
    .modal-content {
      background: #fff;
      padding: 2rem;
      border-radius: 8px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      border-bottom: 2px solid #e6e6e6;
      padding-bottom: 1rem;
    }
    .modal-header h2 { margin: 0; font-size: 1.5rem; color: #333; }
    .modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #666;
    }
    .modal-close:hover { color: #333; }
    .form-group {
      margin-bottom: 1.2rem;
      display: flex;
      flex-direction: column;
    }
    .form-group label {
      font-weight: 600;
      margin-bottom: 0.4rem;
      color: #333;
    }
    .form-group input,
    .form-group select {
      padding: 0.6rem;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 1rem;
    }
    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--primaryColor);
      box-shadow: 0 0 5px rgba(var(--primaryColor-rgb), 0.3);
    }
    .modal-actions {
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
      margin-top: 2rem;
    }
    .modal-actions button {
      padding: 0.6rem 1.2rem;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
    }
    .btn-cancel {
      background: #e6e6e6;
      color: #333;
    }
    .btn-cancel:hover { background: #ccc; }
    .btn-submit {
      background: var(--primaryColor);
      color: #fff;
    }
    .btn-submit:hover { background: #0056b3; }
    .btn-edit {
      background: var(--primaryColor);
      color: #fff;
      font-size: 0.85rem;
      padding: 0.5rem 0.8rem;
    }
    .btn-edit:hover { background: #0056b3; }
    .create-user-section {
      background: #f9f9f9;
      padding: 1.5rem;
      border-radius: 8px;
      margin-bottom: 2rem;
      border-left: 4px solid var(--primaryColor);
    }
    .create-user-section h3 { margin-top: 0; color: #333; }
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    @media (max-width: 600px) {
      .form-row { grid-template-columns: 1fr; }
      .modal-content { width: 95%; padding: 1.5rem; }
    }
  </style>
  <title>Admin Dashboard | IBRCN</title>
</head>
<body>

<header class="header">
  <div class="header-1">
    <a href="./index.php" class="logo"><i class="fas fa-book"></i> IBRCN</a>
    <div class="icons">
      <a href="admin.php" class="fas fa-user-shield" title="Admin"></a>
      <a href="reader.php" class="fas fa-book-open-reader" title="Reader"></a>
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
      <h3>Admin Portal</h3>
      <p>Welcome, <?php echo htmlspecialchars($_SESSION["user"]); ?>. Manage bookstores, users, and system roles.</p>
      <a href="#admin-tools" class="btn">Admin Tools</a>
    </div>
    <div class="image">
      <img src="./img/img5.svg" alt="Admin Dashboard" />
    </div>
  </div>
</section>

<section id="admin-tools" class="member">
  <div class="container">
    <h1 class="heading"><span>System Administration</span></h1>

    <?php if ($message): ?>
      <div class="message-alert message-<?php echo htmlspecialchars($messageType); ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <div class="admin-tabs">
      <button class="tab-btn active" data-tab="stores">Bookstores</button>
      <button class="tab-btn" data-tab="users">Users &amp; Roles</button>
    </div>

    <div id="stores-tab" class="tab-content active">
      <h2>Bookstore Approvals</h2>
      <p style="margin: 0.8rem 0 1.2rem; color: #555; font-size: 1.15rem;">
        <a href="admin-report-pdf.php" class="btn" style="display:inline-block;text-decoration:none;">
          <i class="fas fa-file-pdf"></i> Download PDF — all stores &amp; owners
        </a>
      </p>
      
      <h3 style="margin-top: 1.6rem; color: #333;">Pending Approval (<?php echo count($pendingStores); ?>)</h3>
      <?php if (empty($pendingStores)): ?>
        <p style="color: #666;">No pending bookstore approvals.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Store Name</th>
              <th>Owner</th>
              <th>Email</th>
              <th>Region</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingStores as $store): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($store['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($store['username'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($store['email'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($store['region'] ?? 'N/A'); ?></td>
                <td><span class="status-badge status-pending"><?php echo htmlspecialchars($store['status']); ?></span></td>
                <td>
                  <div class="admin-actions">
                    <form method="post" action="admin.php" style="display:inline;">
                      <input type="hidden" name="action" value="approve_store">
                      <input type="hidden" name="store_id" value="<?php echo (int)$store['store_id']; ?>">
                      <button class="btn-approve" type="submit">Approve</button>
                    </form>
                    <form method="post" action="admin.php" style="display:inline;">
                      <input type="hidden" name="action" value="reject_store">
                      <input type="hidden" name="store_id" value="<?php echo (int)$store['store_id']; ?>">
                      <button class="btn-reject" type="submit">Reject</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <h3 style="margin-top: 1.6rem; color: #333;">Approved Stores (<?php echo count($approvedStores); ?>)</h3>
      <?php if (empty($approvedStores)): ?>
        <p style="color: #666;">No approved bookstores yet.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Store Name</th>
              <th>Owner</th>
              <th>Region</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($approvedStores as $store): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($store['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($store['username'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($store['region'] ?? 'N/A'); ?></td>
                <td><span class="status-badge status-approved"><?php echo htmlspecialchars($store['status']); ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div id="users-tab" class="tab-content">
      <h2>User Management</h2>

      <!-- Create New User Section -->
      <div class="create-user-section">
        <h3>Create New User</h3>
        <form method="post" action="admin.php">
          <input type="hidden" name="action" value="create_user">
          <div class="form-row">
            <div class="form-group">
              <label for="new-username">Username</label>
              <input type="text" id="new-username" name="username" placeholder="Enter username" required>
            </div>
            <div class="form-group">
              <label for="new-email">Email</label>
              <input type="email" id="new-email" name="email" placeholder="Enter email" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="new-password">Password</label>
              <input type="password" id="new-password" name="password" placeholder="Enter password" required>
            </div>
            <div class="form-group">
              <label for="new-role">Role</label>
              <select id="new-role" name="role">
                <option value="Reader">Reader</option>
                <option value="Owner">Owner</option>
                <option value="Admin">Admin</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-submit" style="width: 100%; padding: 0.7rem; font-size: 1rem;">Create User</button>
        </form>
      </div>

      <!-- Users Table -->
      <h3 style="margin-top: 2rem; color: #333;">All Users (<?php echo count($allUsers); ?>)</h3>
      <?php if (empty($allUsers)): ?>
        <p style="color: #666;">No users in the system.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allUsers as $user): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><span class="status-badge" style="background: #e7f3ff; color: #0056b3;"><?php echo htmlspecialchars($user['role']); ?></span></td>
                <td>
                  <div class="admin-actions">
                    <button type="button" class="btn-edit" onclick="openEditModal(<?php echo (int) $user['user_id']; ?>, <?php echo json_encode($user['username'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, <?php echo json_encode($user['email'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, <?php echo json_encode($user['role'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)">Edit</button>
                    <form method="post" action="admin.php" style="display:inline;">
                      <input type="hidden" name="action" value="delete_user">
                      <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>">
                      <button class="btn-delete" type="submit" onclick="return confirm('Are you sure? This cannot be undone.')">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Edit User</h2>
          <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="post" action="admin.php" id="editUserForm">
          <input type="hidden" name="action" value="update_user">
          <input type="hidden" name="user_id" id="edit-user-id" value="">
          
          <div class="form-group">
            <label for="edit-username">Username</label>
            <input type="text" id="edit-username" name="username" required>
          </div>
          
          <div class="form-group">
            <label for="edit-email">Email</label>
            <input type="email" id="edit-email" name="email" required>
          </div>
          
          <div class="form-group">
            <label for="edit-role">Role</label>
            <select id="edit-role" name="role">
              <option value="Reader">Reader</option>
              <option value="Owner">Owner</option>
              <option value="Admin">Admin</option>
            </select>
          </div>
          
          <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
            <button type="submit" class="btn-submit">Update User</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</section>

<?php include_once __DIR__ . '/../partials/site_footer.php'; ?>

<script>
  (function() {
    const toggle = document.getElementById('account-toggle');
    const panel = document.getElementById('account-panel');
    if (toggle && panel) {
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        panel.classList.toggle('show');
      });
      document.addEventListener('click', function(e) {
        if (!panel.contains(e.target) && !toggle.contains(e.target)) {
          panel.classList.remove('show');
        }
      });
    }

    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        document.querySelectorAll('.tab-content').forEach(tab => {
          tab.classList.remove('active');
        });
        tabButtons.forEach(b => b.classList.remove('active'));
        document.getElementById(tabName + '-tab').classList.add('active');
        this.classList.add('active');
      });
    });
  })();

  // Edit User Modal Functions
  function openEditModal(userId, username, email, role) {
    document.getElementById('edit-user-id').value = userId;
    document.getElementById('edit-username').value = username;
    document.getElementById('edit-email').value = email;
    document.getElementById('edit-role').value = role;
    document.getElementById('editUserModal').classList.add('show');
  }

  function closeEditModal() {
    document.getElementById('editUserModal').classList.remove('show');
  }

  // Close modal when clicking outside
  document.getElementById('editUserModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeEditModal();
    }
  });
</script>

</body>
</html>
