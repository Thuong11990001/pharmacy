<?php
require_once '../includes/config.php';
require_once '../includes/session.php';

$current_page = basename($_SERVER['PHP_SELF']);
$user_info = SessionManager::getUserInfo();
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <!-- Brand Logo -->
  <a href="<?php echo BASE_URL; ?>admin/" class="brand-link">
    <img src="<?php echo BASE_URL; ?>assets/dist/img/AdminLTELogo.png" 
         alt="Pharmacy Logo" 
         class="brand-image img-circle elevation-3" 
         style="opacity: .8">
    <span class="brand-text font-weight-light">Pharmacy Management</span>
  </a>

  <!-- Sidebar -->
  <div class="sidebar">
    <!-- Sidebar user panel (optional) -->
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
      <div class="image">
        <img src="<?php echo BASE_URL; ?>assets/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
      </div>
      <div class="info">
        <a href="#" class="d-block">
          <?php echo htmlspecialchars($user_info['full_name'] ?? $user_info['username'] ?? 'User'); ?>
        </a>
        <small class="text-muted">
          <span class="badge badge-<?php echo $user_info['role'] === 'admin' ? 'danger' : 'primary'; ?>">
            <?php echo ucfirst($user_info['role']); ?>
          </span>
        </small>
      </div>
    </div>

    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <!-- Dashboard -->
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>admin/" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- Quản lý -->
        <li class="nav-item has-treeview <?php echo in_array($current_page, ['medicines', 'categories', 'suppliers', 'users', 'inventory', 'prescriptions']) ? 'menu-open' : ''; ?>">
          <a href="#" class="nav-link <?php echo in_array($current_page, ['medicines', 'categories', 'suppliers', 'users', 'inventory', 'prescriptions']) ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-cogs"></i>
            <p>
              Quản lý
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/medicines/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/medicines/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Thuốc</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/categories/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/categories/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Danh mục</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/suppliers/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/suppliers/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Nhà cung cấp</p>
              </a>
            </li>
            <?php if ($user_info['role'] === 'admin'): ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/users/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/users/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Người dùng</p>
              </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/inventory/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/inventory/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Lô hàng</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/prescriptions/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/prescriptions/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Đơn thuốc</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/patients/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/patients/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Quản lý Bệnh nhân</p>
              </a>
            </li>
          </ul>
        </li>

        <!-- Giao dịch -->
        <li class="nav-item has-treeview <?php echo in_array($current_page, ['sales', 'controlled-drugs', 'sale-details', 'prescription-details']) ? 'menu-open' : ''; ?>">
          <a href="#" class="nav-link <?php echo in_array($current_page, ['sales', 'controlled-drugs', 'sale-details', 'prescription-details']) ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-exchange-alt"></i>
            <p>
              Giao dịch
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/sales/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/sales/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Giao dịch Bán hàng</p>
              </a>
            </li>
            <?php if ($user_info['can_sell_controlled']): ?>
            <li class="nav-item">
              <a href="<?php echo BASE_URL; ?>admin/controlled-drugs/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/controlled-drugs/') !== false ? 'active' : ''; ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>Nhật ký Thuốc Kiểm soát</p>
              </a>
            </li>
            <?php endif; ?>
           
          </ul>
        </li>

        <!-- Báo cáo -->
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>admin/reports/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/reports/') !== false ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>Báo cáo</p>
          </a>
        </li>

        <!-- Phân cách -->
        <li class="nav-header">HỆ THỐNG</li>

        <!-- Cài đặt (chỉ admin) -->
        <?php if ($user_info['role'] === 'admin'): ?>
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>admin/settings/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/settings/') !== false ? 'active' : ''; ?>">
            <i class="nav-icon fas fa-tools"></i>
            <p>Cài đặt hệ thống</p>
          </a>
        </li>
        <?php endif; ?>

        <!-- Thông tin -->
        <li class="nav-item">
          <a href="#" class="nav-link" onclick="showSystemInfo()">
            <i class="nav-icon fas fa-info-circle"></i>
            <p>Thông tin hệ thống</p>
          </a>
        </li>

        <!-- Đăng xuất -->
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất?')">
            <i class="nav-icon fas fa-sign-out-alt text-danger"></i>
            <p class="text-danger">Đăng xuất</p>
          </a>
        </li>
      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
  <!-- /.sidebar -->
</aside>

<script>
function showSystemInfo() {
    alert('Pharmacy Management System v1.0.0\nDeveloped for pharmacy operations\nUser: <?php echo htmlspecialchars($user_info['full_name'] ?? $user_info['username'] ?? 'User'); ?>\nRole: <?php echo ucfirst($user_info['role']); ?>');
}
</script>