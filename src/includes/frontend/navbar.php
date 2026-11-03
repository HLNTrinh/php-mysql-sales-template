<?php
$cartCount = array_sum($_SESSION['cart'] ?? []);
$isLoggedIn = isset($_SESSION['customer_id']);
$customerName = $_SESSION['customer_name'] ?? '';
$navbarKeyword = $_GET['keyword'] ?? '';
$navbarKeyword = is_string($navbarKeyword) ? $navbarKeyword : '';
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isHomePage = $currentPage === 'index.php';
$isProductsPage = in_array(
    $currentPage,
    ['products.php', 'product-detail.php'],
    true
);
$isCartPage = $currentPage === 'cart.php';
$isOrdersPage = $currentPage === 'orders.php';
?>

<nav class="navbar navbar-expand-lg sticky-top bg-white shadow-sm border-bottom">
    <div class="container py-2">

        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark"
           href="/">
            <span>
                Sales <span class="text-primary">Management</span>
            </span>
        </a>

        <!-- Menu điện thoại -->
        <button class="navbar-toggler border-0 shadow-none"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#frontendNavbar"
                aria-controls="frontendNavbar"
                aria-expanded="false"
                aria-label="Mở menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="frontendNavbar">

            <!-- Menu chính -->
            <ul class="navbar-nav me-lg-3 gap-lg-2 mt-3 mt-lg-0">
                <li class="nav-item">
                    <a
                       class="nav-link px-3 rounded-3 text-dark fw-medium<?= $isHomePage ? ' is-active' : '' ?>"
                       href="/"
                       <?= $isHomePage ? 'aria-current="page"' : '' ?>
                    >
                        <i class="bi bi-house-door me-1"></i>
                        Trang chủ
                    </a>
                </li>

                <li class="nav-item">
                    <a
                       class="nav-link px-3 rounded-3 text-dark fw-medium<?= $isProductsPage ? ' is-active' : '' ?>"
                       href="/products.php"
                       <?= $isProductsPage ? 'aria-current="page"' : '' ?>
                    >
                        <i class="bi bi-grid me-1"></i>
                        Sản phẩm
                    </a>
                </li>
            </ul>

            <form
                action="/products.php"
                method="get"
                class="navbar-search"
                role="search"
                aria-label="Tìm sản phẩm"
            >
                <input
                    type="search"
                    name="keyword"
                    class="form-control"
                    placeholder="Tìm sản phẩm..."
                    aria-label="Nhập tên hoặc mã sản phẩm"
                    value="<?= htmlspecialchars($navbarKeyword, ENT_QUOTES, 'UTF-8') ?>"
                >
                <button
                    type="submit"
                    class="navbar-search-button"
                    aria-label="Tìm kiếm"
                >
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
            </form>

            <!-- Giỏ hàng và tài khoản -->
            <div class="d-flex align-items-center flex-wrap gap-2 mt-3 mt-lg-0">

                <a class="nav-link px-3 rounded-3 text-dark fw-medium navbar-cart-link<?= $isCartPage ? ' is-active' : '' ?>"
                   href="/cart.php"
                   aria-label="Giỏ hàng, <?= (int) $cartCount ?> sản phẩm"
                   <?= $isCartPage ? 'aria-current="page"' : '' ?>>
                   <i class="bi bi-cart3" aria-hidden="true"></i>
                   <span class="navbar-cart-label">Giỏ hàng</span>
                   <span class="navbar-cart-count badge rounded-pill bg-primary">
                       <?= (int) $cartCount ?>
                   </span>
                </a>

                <?php if ($isLoggedIn): ?>
                    <a
                       class="nav-link px-3 rounded-3 text-dark fw-medium<?= $isOrdersPage ? ' is-active' : '' ?>"
                       href="/orders.php"
                       <?= $isOrdersPage ? 'aria-current="page"' : '' ?>
                    >
                        <i class="bi bi-receipt me-1" aria-hidden="true"></i>
                        Đơn hàng
                    </a>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>

                        <span class="fw-semibold">
                            <?= htmlspecialchars(
                                $customerName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                    <a class="btn btn-outline-danger rounded-pill px-3"
                       href="/logout.php">
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Đăng xuất
                    </a>

                <?php else: ?>

                    <a class="btn btn-outline-primary rounded-pill px-3"
                       href="/login.php">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Đăng nhập
                    </a>

                    <a class="btn btn-primary rounded-pill px-3"
                       href="/register.php">
                        <i class="bi bi-person-plus me-1"></i>
                        Đăng ký
                    </a>

                <?php endif; ?>

                <a class="btn btn-light border rounded-pill px-3 text-dark"
                   href="/admin/">
                    <i class="bi bi-speedometer2 me-1"></i>
                    Quản trị
                </a>

            </div>
        </div>
    </div>
</nav>

<style>
    .navbar {
        font-size: 15px;
    }

    .navbar-brand {
        letter-spacing: -0.3px;
        font-size: 19px;
    }

    .navbar .nav-link {
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .navbar .nav-link.is-active {
        color: #0d6efd !important;
        text-decoration: underline;
        text-decoration-color: #0d6efd;
        text-decoration-thickness: 2px;
        text-underline-offset: 6px;
    }

    .navbar .navbar-cart-link.is-active {
        text-decoration: none;
    }

    .navbar .nav-link:hover {
        background-color: #f0f4ff;
        color: #0d6efd !important;
    }

    .navbar .btn {
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .navbar .btn:hover {
        transform: translateY(-1px);
    }

    .navbar-cart-link {
        position: relative;
        display: inline-flex;
        min-height: 42px;
        height: 42px;
        align-items: center;
        gap: 7px;
        margin-right: 4px;
        white-space: nowrap;
    }

    .navbar-cart-count {
        padding: 3px 6px;
        font-size: 10px;
        line-height: 1;
    }

    .navbar-cart-link > i {
        font-size: 20px;
    }

    .navbar-search {
        display: flex;
        width: min(250px, 100%);
        margin-right: 14px;
    }

    .navbar-search .form-control {
        min-width: 0;
        border-color: #e2e7f0;
        border-radius: 999px 0 0 999px;
        font-size: 13px;
    }

    .navbar-search-button {
        display: grid;
        width: 42px;
        flex: 0 0 42px;
        place-items: center;
        border: 1px solid #e2e7f0;
        border-left: 0;
        border-radius: 0 999px 999px 0;
        background: #f7f9fc;
        color: #53647d;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .navbar-search-button:hover {
        background: #287ee5;
        color: #fff;
    }

    @media (min-width: 992px) and (max-width: 1199.98px) {
        .navbar-brand {
            font-size: 16px;
        }

        #frontendNavbar .navbar-nav {
            gap: 0 !important;
        }

        #frontendNavbar .navbar-nav .nav-link {
            padding-right: 0.5rem !important;
            padding-left: 0.5rem !important;
            white-space: nowrap;
        }

        .navbar-search {
            width: 145px;
            margin-right: 6px;
        }

        .navbar-search-button {
            width: 36px;
            flex-basis: 36px;
        }

        .navbar .d-flex.flex-wrap {
            flex-wrap: nowrap !important;
            gap: 0.35rem !important;
        }

        .navbar .d-flex.flex-wrap .btn {
            padding-right: 0.55rem;
            padding-left: 0.55rem;
            font-size: 12px;
            white-space: nowrap;
        }

        .navbar-cart-link {
            height: 34px;
            margin-right: 2px;
        }
    }

    @media (max-width: 991.98px) {
        .navbar .navbar-nav {
            padding-bottom: 12px;
        }

        .navbar-search {
            width: 100%;
            margin: 0 0 10px;
        }

        .navbar .d-flex.flex-wrap {
            align-items: flex-start !important;
        }
    }
</style>