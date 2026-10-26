<?php
$cartCount = array_sum($_SESSION['cart'] ?? []);
$isLoggedIn = isset($_SESSION['customer_id']);
$customerName = $_SESSION['customer_name'] ?? '';
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
            <ul class="navbar-nav mx-auto gap-lg-2 mt-3 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link px-3 rounded-3 text-dark fw-medium"
                       href="/">
                        <i class="bi bi-house-door me-1"></i>
                        Trang chủ
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-3 rounded-3 text-dark fw-medium"
                       href="/products.php">
                        <i class="bi bi-grid me-1"></i>
                        Sản phẩm
                    </a>
                </li>
            </ul>

            <!-- Giỏ hàng và tài khoản -->
            <div class="d-flex align-items-center flex-wrap gap-2 mt-3 mt-lg-0">

                <a class="btn btn-outline-primary rounded-pill px-3"
                   href="/cart.php">
                    <i class="bi bi-cart3 me-1"></i>
                    Giỏ hàng
                    <span class="badge rounded-pill bg-primary ms-1">
                        <?= (int) $cartCount ?>
                    </span>
                </a>

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

    @media (max-width: 991.98px) {
        .navbar .navbar-nav {
            padding-bottom: 12px;
        }

        .navbar .d-flex.flex-wrap {
            align-items: flex-start !important;
        }
    }
</style>