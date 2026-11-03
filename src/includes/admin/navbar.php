<nav class="navbar navbar-expand-lg sticky-top bg-white shadow-sm border-bottom">
    <div class="container py-2">

        <!-- Tên website -->
        <a class="navbar-brand fw-bold text-dark"
           href="/admin/">
            Sales <span class="text-primary">Management</span>
        </a>

        <!-- Nút menu trên điện thoại -->
        <button
            class="navbar-toggler border-0 shadow-none"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Mở menu"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Các chức năng quản trị -->
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto gap-lg-2 mt-3 mt-lg-0">

                <li class="nav-item">
                    <a class="nav-link px-3 rounded-3 text-dark"
                       href="/admin/">
                        Tổng quan
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-3 rounded-3 text-dark"
                       href="/">
                        Trang chủ
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-3 rounded-3 text-dark"
                       href="/admin/categories/">
                        Danh mục
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-3 rounded-3 text-dark"
                       href="/admin/products/">
                        Sản phẩm
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-3 rounded-3 text-dark"
                       href="/admin/orders/">
                        Đơn hàng
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-3 rounded-3 text-dark"
                       href="/admin/shippers/">
                        Đơn vị vận chuyển
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-3 rounded-3 text-dark"
                       href="/admin/customers/">
                        Khách hàng
                    </a>
                </li>

            </ul>
        </div>
    </div>
</nav>

<style>
    .navbar-brand {
        font-size: 20px;
        letter-spacing: -0.4px;
    }

    #mainNavbar .nav-link {
        font-size: 14px;
        font-weight: 500;
        white-space: nowrap;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    #mainNavbar .nav-link:hover {
        background-color: #f0f4ff;
        color: #0d6efd !important;
    }

    @media (max-width: 991.98px) {
        #mainNavbar .navbar-nav {
            padding-bottom: 8px;
        }
    }
</style>