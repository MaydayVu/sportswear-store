<?php
// ĐÃ XÓA session_start() Ở ĐÂY VÌ ĐÃ GỌI Ở FILE CHÍNH
?>

<header class="main-header">

    <!-- TOP BAR - hiển thị cho cả đăng nhập & chưa đăng nhập -->
    <div class="top-bar">
        <div class="container top-bar-flex">
            <a href="/manguonmo/sportshop/help.php">Trợ giúp</a>
            <a href="/manguonmo/sportshop/tracking.php">Theo dõi đơn hàng</a>
            <a href="/manguonmo/sportshop/register.php">Đăng ký hội viên</a>
        </div>
    </div>

    <!-- MAIN HEADER -->
    <div class="header-content container">

        <!-- LOGO -->
        <a href="/manguonmo/sportshop/index.php" class="logo">
            <img src="/manguonmo/sportshop/assets/images/logo.png" alt="Logo" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjUwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiMzNDk4ZGIiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPlNwb3J0IEZhc2hpb248L3RleHQ+PC9zdmc+'">
        </a>

        <!-- NAVIGATION -->
        <nav class="navbar">
            <?php include __DIR__ . "/navbar.php"; ?>
        </nav>

        <!-- RIGHT ICONS -->
        <div class="header-icons">

            <!-- SEARCH -->
            <div class="search-box">
                <input type="text" placeholder="Tìm kiếm sản phẩm..." id="searchInput">
                <button id="searchBtn">🔍</button>
            </div>

            <!-- USER AREA -->
            <?php if (isset($_SESSION['fullname'])): ?>
                
                <!-- Nếu đã đăng nhập -->
                <div class="user-info">
                    <span class="hello">👤 Xin chào, <strong><?php echo htmlspecialchars($_SESSION['fullname']); ?></strong></span>
                    <a href="/manguonmo/sportshop/auth/logout.php" class="logout-btn">(Đăng xuất)</a>
                </div>

            <?php else: ?>

                <!-- Nếu chưa đăng nhập -->
                <a href="/manguonmo/sportshop/auth/login.php" class="icon-item">👤</a>

            <?php endif; ?>

            <!-- WISHLIST -->
            <a href="/manguonmo/sportshop/wishlist.php" class="icon-item">❤</a>

            <!-- CART -->
            <a href="/manguonmo/sportshop/cart/cart.php" class="icon-item">🛒</a>

        </div>
    </div>

</header>

<script>
// Xử lý tìm kiếm sản phẩm
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    
    function performProductSearch() {
        const searchTerm = searchInput.value.trim();
        if (searchTerm) {
            window.location.href = '/manguonmo/sportshop/products.php?search=' + encodeURIComponent(searchTerm);
        }
    }
    
    if (searchBtn) {
        searchBtn.addEventListener('click', performProductSearch);
    }
    
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performProductSearch();
            }
        });
    }
});
</script>