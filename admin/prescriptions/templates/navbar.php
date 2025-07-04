<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class=" nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo BASE_URL; ?>manager/" class="nav-link">Trang chủ</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo BASE_URL; ?>manager/prescriptions/" class="nav-link">Đơn thuốc</a>
        </li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <span class="navbar-text">Xin chào, <?php echo htmlspecialchars($user_info['full_name'] ?? 'User'); ?></span>
        </li>
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </a>
        </li>
    </ul>
</nav>