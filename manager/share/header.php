<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

if (!isset($_SESSION)) {
    session_start();
}

$user_info = SessionManager::getUserInfo();
?>

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
    </li>
    <li class="nav-item d-none d-sm-inline-block">
      <a href="<?php echo BASE_URL; ?>manager/" class="nav-link">Trang chủ</a>
    </li>
  </ul>

  <!-- Right navbar links -->
  <ul class="navbar-nav ml-auto">
    <!-- User Info Dropdown -->
    <li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#" title="Thông tin người dùng">
        <i class="fas fa-user"></i>
        <?php echo htmlspecialchars($user_info['full_name'] ?? $user_info['username'] ?? 'User'); ?>
        <i class="fas fa-caret-down ml-1"></i>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <div class="dropdown-header">
          <strong><?php echo htmlspecialchars($user_info['full_name'] ?? 'User'); ?></strong>
          <br>
          <small class="text-muted">
            <span class="badge badge-<?php echo $user_info['role'] === 'admin' ? 'danger' : 'primary'; ?>">
              <?php echo ucfirst($user_info['role']); ?>
            </span>
          </small>
        </div>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item">
          <i class="fas fa-user mr-2"></i> Thông tin cá nhân
        </a>
        <a href="#" class="dropdown-item">
          <i class="fas fa-cog mr-2"></i> Cài đặt
        </a>
        <div class="dropdown-divider"></div>
        <a href="<?php echo BASE_URL; ?>logout.php" class="dropdown-item dropdown-footer">
          <i class="fas fa-sign-out-alt mr-2"></i> Đăng xuất
        </a>
      </div>
    </li>
  </ul>
</nav>
<!-- /.navbar -->