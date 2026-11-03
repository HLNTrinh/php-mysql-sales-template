<footer class="store-footer">
    <div class="container">
        <div class="row g-4 py-5">
            <div class="col-12 col-lg-5">
                <a class="footer-brand" href="/">
                    Sales <span>Management</span>
                </a>
                <p class="footer-description">
                    Khám phá các sản phẩm công nghệ đang được kinh doanh tại cửa hàng.
                </p>
            </div>

            <div class="col-6 col-lg-2">
                <h2 class="footer-heading">Mua sắm</h2>
                <a class="footer-link" href="/products.php">Tất cả sản phẩm</a>
                <a class="footer-link" href="/cart.php">Giỏ hàng</a>
            </div>

            <div class="col-6 col-lg-2">
                <h2 class="footer-heading">Tài khoản</h2>
                <a class="footer-link" href="/login.php">Đăng nhập</a>
                <a class="footer-link" href="/register.php">Đăng ký</a>
            </div>

            <div class="col-12 col-lg-3">
                <h2 class="footer-heading">Quản lý cửa hàng</h2>
                <a class="footer-link" href="/admin/">Trang quản trị</a>
                <a class="footer-link" href="/admin/orders/">Quản lý đơn hàng</a>
            </div>
        </div>

        <div class="footer-bottom">
            <span>
                &copy; <?= date('Y') ?> Sales Management.
                Hệ thống quản lý sản phẩm và đơn hàng.
            </span>
            <a href="/products.php">Tiếp tục mua sắm <span aria-hidden="true">&rarr;</span></a>
        </div>
    </div>
</footer>

<style>
    .store-footer {
        border-top: 1px solid #e8ecf3;
        background: #f3f5fb;
    }

    .footer-brand {
        display: inline-block;
        margin-bottom: 10px;
        color: #17243b;
        font-size: 19px;
        font-weight: 750;
        letter-spacing: -0.5px;
        text-decoration: none;
    }

    .footer-brand span {
        color: #2463eb;
    }

    .footer-description {
        max-width: 300px;
        margin: 0;
        color: #748097;
        font-size: 12px;
        line-height: 1.7;
    }

    .footer-heading {
        margin: 2px 0 13px;
        color: #26344c;
        font-size: 12px;
        font-weight: 750;
    }

    .footer-link {
        display: block;
        width: fit-content;
        margin-top: 9px;
        color: #748097;
        font-size: 11px;
        text-decoration: none;
    }

    .footer-link:hover,
    .footer-bottom a:hover {
        color: #2463eb;
    }

    .footer-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 0;
        border-top: 1px solid #e1e6ef;
        color: #8791a3;
        font-size: 10px;
    }

    .footer-bottom a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #56657e;
        text-decoration: none;
    }

    @media (max-width: 575.98px) {
        .footer-bottom {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

</body>
</html>