<?php
// ĐÃ XÓA session_start() Ở ĐÂY VÌ ĐÃ GỌI Ở FILE CHÍNH
?>

<header class="main-header">

    <!-- TOP BAR - hiển thị cho cả đăng nhập & chưa đăng nhập -->
    <div class="top-bar">
        <div class="container top-bar-flex">
            <a href="/manguonmo/sportshop/help.php">Trợ giúp</a>
            <a href="/manguonmo/sportshop/order_tracking.php">Theo dõi đơn hàng</a>
            <a href="/manguonmo/sportshop/auth/register.php">Đăng ký hội viên</a>
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

            <!-- SEARCH BOX -->
            <div class="search-container">
                <div class="search-box">
                    <input type="text" 
                           placeholder="Tìm kiếm sản phẩm, thương hiệu..." 
                           id="searchInput"
                           autocomplete="off">
                    <button id="searchBtn" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <!-- Search Results Dropdown -->
                <div class="search-results" id="searchResults"></div>
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

<style>
/* Search Container Styles */
.search-container {
    position: relative;
    width: 300px;
}

.search-box {
    display: flex;
    background: white;
    border-radius: 25px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.search-box:focus-within {
    border-color: #000;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

#searchInput {
    flex: 1;
    border: none;
    padding: 12px 20px;
    font-size: 14px;
    outline: none;
    background: transparent;
}

#searchInput::placeholder {
    color: #6c757d;
}

.search-btn {
    background: #000;
    border: none;
    padding: 12px 20px;
    color: white;
    cursor: pointer;
    transition: background 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-btn:hover {
    background: #333;
}

/* Search Results Dropdown */
.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    margin-top: 5px;
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.search-results.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.search-result-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid #f8f9fa;
    cursor: pointer;
    transition: background 0.2s ease;
    text-decoration: none;
    color: inherit;
}

.search-result-item:hover {
    background: #f8f9fa;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    margin-right: 15px;
    background: #f8f9fa;
}

.search-result-info {
    flex: 1;
}

.search-result-name {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
    color: #000;
}

.search-result-price {
    font-size: 13px;
    color: #e4002b;
    font-weight: 700;
}

.search-result-original-price {
    font-size: 12px;
    color: #6c757d;
    text-decoration: line-through;
    margin-left: 8px;
}

.search-result-category {
    font-size: 12px;
    color: #6c757d;
    margin-top: 2px;
}

.search-no-results {
    padding: 20px;
    text-align: center;
    color: #6c757d;
    font-size: 14px;
}

.search-view-all {
    display: block;
    text-align: center;
    padding: 12px;
    background: #f8f9fa;
    color: #000;
    text-decoration: none;
    font-weight: 600;
    border-top: 1px solid #e9ecef;
    transition: background 0.3s ease;
}

.search-view-all:hover {
    background: #e9ecef;
    color: #000;
}

/* Loading State */
.search-loading {
    padding: 20px;
    text-align: center;
    color: #6c757d;
}

.search-loading::after {
    content: '';
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #000;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .search-container {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .header-content {
        flex-wrap: wrap;
    }
    
    .search-box {
        border-radius: 8px;
    }
}

/* Header Icons Adjustments */
.header-icons {
    display: flex;
    align-items: center;
    gap: 15px;
}

.icon-item {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f8f9fa;
    text-decoration: none;
    font-size: 16px;
    transition: all 0.3s ease;
}

.icon-item:hover {
    background: #000;
    color: white;
    transform: translateY(-2px);
}
</style>

<script>
// Xử lý tìm kiếm sản phẩm với autocomplete
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const searchResults = document.getElementById('searchResults');
    
    let searchTimeout;
    let currentSearchTerm = '';

    // Hàm thực hiện tìm kiếm
    function performProductSearch() {
        const searchTerm = searchInput.value.trim();
        if (searchTerm) {
            window.location.href = '/manguonmo/sportshop/products.php?search=' + encodeURIComponent(searchTerm);
        }
    }

    // Hàm tìm kiếm autocomplete
    function performAutocompleteSearch(searchTerm) {
        if (searchTerm.length < 2) {
            searchResults.classList.remove('active');
            return;
        }

        // Hiển thị loading
        searchResults.innerHTML = '<div class="search-loading">Đang tìm kiếm...</div>';
        searchResults.classList.add('active');

        // Hủy request trước đó nếu có
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        // Debounce search
        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch('/manguonmo/sportshop/search_products.php?q=' + encodeURIComponent(searchTerm));
                const data = await response.json();
                
                displaySearchResults(data, searchTerm);
            } catch (error) {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="search-no-results">Lỗi tìm kiếm</div>';
            }
        }, 300);
    }

    // Hiển thị kết quả tìm kiếm
    function displaySearchResults(products, searchTerm) {
        if (!products || products.length === 0) {
            searchResults.innerHTML = `
                <div class="search-no-results">
                    <i class="fas fa-search" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                    Không tìm thấy sản phẩm phù hợp
                </div>
            `;
            return;
        }

        let resultsHTML = '';
        
        // Hiển thị tối đa 5 sản phẩm
        const displayProducts = products.slice(0, 5);
        
        displayProducts.forEach(product => {
            const currentPrice = product.discount_percent > 0 
                ? product.price * (1 - product.discount_percent / 100)
                : product.price;
                
            const originalPrice = product.discount_percent > 0 ? product.price : null;

            resultsHTML += `
                <a href="/manguonmo/sportshop/product_detail.php?id=${product.id}" class="search-result-item">
                    <img src="/manguonmo/sportshop/assets/images/products/${product.image}" 
                         alt="${product.name}" 
                         class="search-result-image"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iOCIgZmlsbD0iIzY2NiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPsSQ4bqjaCBz4bqjbiBwaOG6p208L3RleHQ+PC9zdmc+'">
                    <div class="search-result-info">
                        <div class="search-result-name">${highlightSearchTerm(product.name, searchTerm)}</div>
                        <div class="search-result-price">
                            ${formatPrice(currentPrice)}₫
                            ${originalPrice ? `<span class="search-result-original-price">${formatPrice(originalPrice)}₫</span>` : ''}
                        </div>
                        <div class="search-result-category">${product.category_name}</div>
                    </div>
                </a>
            `;
        });

        // Thêm nút xem tất cả
        if (products.length > 5) {
            resultsHTML += `<a href="/manguonmo/sportshop/products.php?search=${encodeURIComponent(searchTerm)}" class="search-view-all">Xem tất cả ${products.length} kết quả</a>`;
        } else {
            resultsHTML += `<a href="/manguonmo/sportshop/products.php?search=${encodeURIComponent(searchTerm)}" class="search-view-all">Xem tất cả kết quả</a>`;
        }

        searchResults.innerHTML = resultsHTML;
    }

    // Highlight từ khóa tìm kiếm
    function highlightSearchTerm(text, searchTerm) {
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    // Định dạng giá
    function formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price);
    }

    // Event Listeners
    if (searchBtn) {
        searchBtn.addEventListener('click', performProductSearch);
    }
    
    if (searchInput) {
        // Tìm kiếm khi nhấn Enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performProductSearch();
            }
        });

        // Autocomplete khi nhập
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.trim();
            if (searchTerm !== currentSearchTerm) {
                currentSearchTerm = searchTerm;
                performAutocompleteSearch(searchTerm);
            }
        });

        // Ẩn kết quả khi click ra ngoài
        document.addEventListener('click', function(e) {
            if (!searchContainer.contains(e.target)) {
                searchResults.classList.remove('active');
            }
        });

        // Hiển thị lại kết quả khi focus vào input
        searchInput.addEventListener('focus', function() {
            if (currentSearchTerm && currentSearchTerm.length >= 2) {
                searchResults.classList.add('active');
            }
        });
    }

    // Close search results on ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchResults.classList.remove('active');
            searchInput.blur();
        }
    });
});
</script>