<?php
require_once '/var/www/src/config/session.php';
require_once '/var/www/src/config/database.php';

$productResult = $conn->query("
    SELECT
        p.ProductID,
        p.ProductCode,
        p.ProductName,
        p.Price,
        p.StockQuantity,
        c.CategoryName,
        (
            SELECT pi.ImageFile
            FROM product_images AS pi
            WHERE pi.ProductID = p.ProductID
            ORDER BY
                pi.IsPrimary DESC,
                pi.SortOrder ASC,
                pi.ProductImageID ASC
            LIMIT 1
        ) AS ImageFile
    FROM products AS p
    INNER JOIN categories AS c
        ON c.CategoryID = p.CategoryID
    WHERE p.IsActive = TRUE
    ORDER BY p.ProductID DESC
    LIMIT 13
");

if (!$productResult) {
    die('Lỗi truy vấn sản phẩm trang chủ: ' . $conn->error);
}

$products = [];

while ($product = $productResult->fetch_assoc()) {
    $products[] = $product;
}

$productResult->free();
$conn->close();

$newProducts = array_slice($products, 0, 4);
$products = array_slice($products, 4);

$promoProduct = $products
    ? $products[min(3, count($products) - 1)]
    : null;

$pageTitle = 'Công nghệ cho cuộc sống hiện đại';
require_once '/var/www/src/includes/frontend/header.php';
require_once '/var/www/src/includes/frontend/navbar.php';
?>

<main class="store-home">
    <section class="home-hero-section">
        <div class="container">
            <?php if ($newProducts): ?>
                <?php $heroProducts = $newProducts; ?>
                <div
                    id="storeHeroCarousel"
                    class="carousel slide hero-carousel"
                    data-bs-ride="carousel"
                    data-bs-interval="5000"
                    data-bs-pause="hover"
                >
                    <div class="carousel-inner">
                        <?php foreach ($heroProducts as $slideIndex => $featuredProduct): ?>
                            <div class="carousel-item <?= $slideIndex === 0 ? 'active' : '' ?>">
                                <article class="hero-feature">
                                    <div class="hero-copy">
                                        <span class="hero-eyebrow">
                                            <span class="eyebrow-dot"></span>
                                            <?= htmlspecialchars(
                                                $featuredProduct['CategoryName'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                        <h1>
                                            Thiết bị tốt hơn,<br>
                                            <span>tiện ích mỗi ngày.</span>
                                        </h1>
                                        <p>
                                            Khám phá sản phẩm đang có tại cửa hàng,
                                            chọn món phù hợp cho công việc và cuộc sống mỗi ngày.
                                        </p>
                                        <div class="hero-product-note">
                                            <span>Đang có tại cửa hàng</span>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $featuredProduct['ProductName'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </strong>
                                            <strong class="hero-price">
                                                <?= number_format(
                                                    (float) $featuredProduct['Price'],
                                                    0,
                                                    ',',
                                                    '.'
                                                ) ?> đ
                                            </strong>
                                        </div>
                                        <a
                                            class="btn hero-cta"
                                            href="/product-detail.php?id=<?= (int) $featuredProduct['ProductID'] ?>"
                                        >
                                            Khám phá sản phẩm
                                        </a>
                                    </div>

                                    <a
                                        class="hero-image"
                                        href="/product-detail.php?id=<?= (int) $featuredProduct['ProductID'] ?>"
                                        aria-label="Xem <?= htmlspecialchars(
                                            $featuredProduct['ProductName'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                    <?php if (!empty($featuredProduct['ImageFile'])): ?>
                                        <img
                                            src="/uploads/products/<?= htmlspecialchars(
                                                $featuredProduct['ImageFile'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            alt="<?= htmlspecialchars(
                                                $featuredProduct['ProductName'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >
                                    <?php else: ?>
                                        <span class="image-placeholder" aria-hidden="true">
                                            <?= htmlspecialchars(
                                                $featuredProduct['ProductCode'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </article>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($heroProducts) > 1): ?>
                        <div class="hero-indicators" aria-label="Chọn sản phẩm giới thiệu">
                            <?php foreach ($heroProducts as $slideIndex => $featuredProduct): ?>
                                <button
                                    type="button"
                                    data-bs-target="#storeHeroCarousel"
                                    data-bs-slide-to="<?= $slideIndex ?>"
                                    class="<?= $slideIndex === 0 ? 'active' : '' ?>"
                                    aria-current="<?= $slideIndex === 0 ? 'true' : 'false' ?>"
                                    aria-label="Sản phẩm <?= $slideIndex + 1 ?>"
                                ></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-hero">
                    <span class="section-kicker">SALES MANAGEMENT</span>
                    <h1>Chào mừng bạn đến với cửa hàng.</h1>
                    <p>
                        Sản phẩm đang được cập nhật. Hãy quay lại sau để khám phá danh mục mới.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($newProducts): ?>
        <section class="home-section new-products-section">
            <div class="container">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">VỪA CẬP NHẬT</span>
                        <h2>Sản phẩm mới</h2>
                        <p>Những sản phẩm mới nhất đang có tại cửa hàng.</p>
                    </div>
                </div>

                <div class="product-grid">
                    <?php foreach ($newProducts as $product): ?>
                        <article class="product-card">
                            <a
                                class="product-image"
                                href="/product-detail.php?id=<?= (int) $product['ProductID'] ?>"
                            >
                                <?php if (!empty($product['ImageFile'])): ?>
                                    <img
                                        src="/uploads/products/<?= htmlspecialchars(
                                            $product['ImageFile'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $product['ProductName'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <span class="image-placeholder" aria-hidden="true">
                                        <?= htmlspecialchars(
                                            $product['ProductCode'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="product-category">
                                    <?= htmlspecialchars(
                                        $product['CategoryName'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </a>

                            <div class="product-card-body">
                                <span class="product-code">
                                    Mã <?= htmlspecialchars(
                                        $product['ProductCode'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                                <h3>
                                    <a href="/product-detail.php?id=<?= (int) $product['ProductID'] ?>">
                                        <?= htmlspecialchars(
                                            $product['ProductName'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </a>
                                </h3>
                                <div class="product-card-bottom">
                                    <strong class="product-price">
                                        <?= number_format(
                                            (float) $product['Price'],
                                            0,
                                            ',',
                                            '.'
                                        ) ?> đ
                                    </strong>
                                    <?php if ((int) $product['StockQuantity'] > 0): ?>
                                        <span class="stock-status">
                                            Còn <?= (int) $product['StockQuantity'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="stock-status stock-empty">
                                            Tạm hết hàng
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <a
                                    class="product-action"
                                    href="/product-detail.php?id=<?= (int) $product['ProductID'] ?>"
                                >
                                    Xem chi tiết
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($promoProduct): ?>
    <section class="feature-promo-section">
        <div class="feature-promo container">
    <a
        class="promo-image"
        href="/product-detail.php?id=<?= (int) $promoProduct['ProductID'] ?>"
        aria-label="Xem <?= htmlspecialchars(
            $promoProduct['ProductName'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >
        <?php if (!empty($promoProduct['ImageFile'])): ?>
            <img
                src="/uploads/products/<?= htmlspecialchars(
                    $promoProduct['ImageFile'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                alt="<?= htmlspecialchars(
                    $promoProduct['ProductName'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                loading="lazy"
            >
        <?php else: ?>
            <span class="image-placeholder" aria-hidden="true">
                <?= htmlspecialchars(
                    $promoProduct['ProductCode'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        <?php endif; ?>
    </a>
    <div class="promo-content">
        <span class="section-kicker">GỢI Ý TỪ CỬA HÀNG</span>
        <h2>
            <?= htmlspecialchars(
                $promoProduct['ProductName'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h2>
        <p>
            Một lựa chọn từ danh mục
            <?= htmlspecialchars(
                $promoProduct['CategoryName'],
                ENT_QUOTES,
                'UTF-8'
            ) ?> đang có tại cửa hàng.
        </p>
        <strong class="promo-price">
            <?= number_format(
                (float) $promoProduct['Price'],
                0,
                ',',
                '.'
            ) ?> đ
        </strong>
        <a
            class="promo-cta"
            href="/product-detail.php?id=<?= (int) $promoProduct['ProductID'] ?>"
        >
            Tìm hiểu sản phẩm <span aria-hidden="true">&rarr;</span>
        </a>
    </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="home-section products-section<?= $promoProduct ? ' products-after-promo' : '' ?>">
        <div class="container">
    <div class="section-heading">
        <div>
            <span class="section-kicker">SẢN PHẨM ĐANG KINH DOANH</span>
            <h2>Sản phẩm nổi bật</h2>
            <p>Khám phá sản phẩm, giá bán và tình trạng tồn kho mới nhất.</p>
                </div>
                <a class="section-link" href="/products.php">
                    Xem tất cả <span aria-hidden="true">&rarr;</span>
                </a>
            </div>

            <?php if ($products): ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <article class="product-card">
                            <a
                                class="product-image"
                                href="/product-detail.php?id=<?= (int) $product['ProductID'] ?>"
                            >
                                <?php if (!empty($product['ImageFile'])): ?>
                                    <img
                                        src="/uploads/products/<?= htmlspecialchars(
                                            $product['ImageFile'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $product['ProductName'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <span class="image-placeholder" aria-hidden="true">
                                        <?= htmlspecialchars(
                                            $product['ProductCode'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="product-category">
                                    <?= htmlspecialchars(
                                        $product['CategoryName'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </a>

                            <div class="product-card-body">
                                <span class="product-code">
                                    Mã <?= htmlspecialchars(
                                        $product['ProductCode'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                                <h3>
                                    <a href="/product-detail.php?id=<?= (int) $product['ProductID'] ?>">
                                        <?= htmlspecialchars(
                                            $product['ProductName'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </a>
                                </h3>
                                <div class="product-card-bottom">
                                    <strong class="product-price">
                                        <?= number_format(
                                            (float) $product['Price'],
                                            0,
                                            ',',
                                            '.'
                                        ) ?> đ
                                    </strong>
                                    <?php if ((int) $product['StockQuantity'] > 0): ?>
                                        <span class="stock-status">
                                            Còn <?= (int) $product['StockQuantity'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="stock-status stock-empty">
                                            Tạm hết hàng
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <a
                                    class="product-action"
                                    href="/product-detail.php?id=<?= (int) $product['ProductID'] ?>"
                                >
                                    Xem chi tiết
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-products">
                    Chưa có sản phẩm nào đang được kinh doanh.
                </div>
            <?php endif; ?>
        </div>
    </section>

</main>

<style>
    :root {
        --store-ink: #17243b;
        --store-muted: #748097;
        --store-blue: #2463eb;
        --store-pale: #f5f7fc;
        --store-border: #e9edf4;
    }

    body {
        background: #fff;
        color: var(--store-ink);
    }

    .store-home {
        overflow: hidden;
    }

    .home-hero-section {
        padding: 28px 0 34px;
        background:
            radial-gradient(ellipse at 82% 0%, rgba(222, 232, 255, 0.66), transparent 34%),
            #f5f7fc;
    }

    .hero-carousel {
        position: relative;
    }

    .hero-carousel .carousel-item {
        transition: transform 0.65s ease-in-out;
    }

    .hero-feature {
        position: relative;
        display: flex;
        min-height: 390px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        background:
            radial-gradient(ellipse at 72% 45%, rgba(43, 82, 139, 0.44), transparent 39%),
            linear-gradient(120deg, #121c2e 0%, #172943 58%, #1c3150 100%);
        box-shadow: 0 18px 42px rgba(27, 44, 71, 0.12);
        isolation: isolate;
    }

    .hero-copy {
        position: relative;
        z-index: 2;
        display: flex;
        width: 56%;
        flex-direction: column;
        align-items: flex-start;
        padding: 40px 0 34px 42px;
    }

    .hero-eyebrow,
    .section-kicker {
        color: var(--store-blue);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1.3px;
    }

    .hero-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 20px;
        color: #b8d0ff;
    }

    .eyebrow-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #67a4ff;
        box-shadow: 0 0 12px rgba(103, 164, 255, 0.8);
    }

    .hero-copy h1 {
        max-width: 470px;
        margin: 0 0 15px;
        color: #fff;
        font-size: clamp(32px, 4vw, 48px);
        font-weight: 750;
        letter-spacing: -1.6px;
        line-height: 1.11;
    }

    .hero-copy h1 span {
        color: #a9c7ff;
    }

    .hero-copy > p {
        max-width: 390px;
        margin: 0 0 20px;
        color: #c3ccda;
        font-size: 14px;
        line-height: 1.75;
    }

    .hero-product-note {
        display: grid;
        gap: 3px;
        margin-bottom: 18px;
    }

    .hero-product-note > span {
        color: #aebbd0;
        font-size: 11px;
    }

    .hero-product-note > strong {
        max-width: 330px;
        overflow: hidden;
        color: #fff;
        font-size: 14px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .hero-product-note > .hero-price {
        color: #a9c7ff;
        font-size: 17px;
    }

    .hero-cta {
        display: inline-flex;
        align-items: center;
        gap: 18px;
        padding: 11px 16px;
        border: 0;
        border-radius: 8px;
        background: #fff;
        color: var(--store-ink);
        font-size: 13px;
        font-weight: 700;
    }

    .hero-cta:hover {
        background: #eaf1ff;
        color: var(--store-blue);
        transform: translateY(-1px);
    }

    .hero-cta span,
    .section-link span,
    .product-action span {
        font-size: 17px;
        line-height: 1;
    }

    .hero-image {
        position: absolute;
        top: 8%;
        right: 4%;
        display: flex;
        width: 40%;
        height: 84%;
        align-items: center;
        justify-content: center;
        padding: 14px;
        border: 1px solid rgba(255, 255, 255, 0.55);
        border-radius: 19px;
        background: rgba(255, 255, 255, 0.97);
        text-decoration: none;
        box-shadow: 0 18px 34px rgba(0, 0, 0, 0.16);
    }

    .hero-image::before {
        position: absolute;
        width: 82%;
        height: 80%;
        border-radius: 50%;
        background: rgba(215, 228, 250, 0.5);
        content: "";
        filter: blur(26px);
    }

    .hero-image img {
        position: relative;
        width: 100%;
        height: 100%;
        object-fit: contain;
        filter: drop-shadow(0 22px 30px rgba(0, 0, 0, 0.24));
        transition: transform 0.3s ease;
    }

    .hero-image:hover img {
        transform: scale(1.035);
    }

    .image-placeholder {
        display: grid;
        width: 100%;
        height: 100%;
        place-items: center;
        border-radius: 12px;
        background: linear-gradient(145deg, #eef2fa, #dce5f5);
        color: #667895;
        font-size: 13px;
        font-weight: 700;
    }

    .hero-image .image-placeholder {
        position: relative;
        width: 75%;
        height: 65%;
        background: rgba(255, 255, 255, 0.12);
        color: #d3def1;
    }

    .hero-indicators {
        position: absolute;
        z-index: 4;
        bottom: 17px;
        left: 42px;
        display: flex;
        gap: 7px;
    }

    .hero-indicators button {
        width: 7px;
        height: 7px;
        padding: 0;
        border: 0;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.5);
        transition: width 0.2s ease, background-color 0.2s ease;
    }

    .hero-indicators button.active {
        width: 23px;
        background: #fff;
    }

    .empty-hero {
        min-height: 300px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        padding: 40px;
        border-radius: 20px;
        background: linear-gradient(125deg, #121c2e, #1d365b);
        color: #fff;
    }

    .empty-hero h1 {
        margin: 15px 0 8px;
        font-size: clamp(30px, 4vw, 46px);
    }

    .empty-hero p {
        max-width: 520px;
        margin: 0;
        color: #c3ccda;
    }

    .home-section {
        padding-top: 54px;
        padding-bottom: 10px;
    }

    .section-heading {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 23px;
    }

    .section-heading h2 {
        margin: 6px 0 5px;
        color: var(--store-ink);
        font-size: clamp(22px, 3vw, 28px);
        font-weight: 750;
        letter-spacing: -0.75px;
    }

    .section-heading p {
        margin: 0;
        color: var(--store-muted);
        font-size: 13px;
    }

    .section-link {
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        gap: 8px;
        margin-bottom: 3px;
        color: var(--store-blue);
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
    }

    .section-link:hover {
        color: #1248bb;
    }

    .feature-promo-section {
        padding: 50px 0;
        background: #f5f7fc;
    }

    .feature-promo {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
        min-height: 245px;
        overflow: hidden;
        border: 1px solid var(--store-border);
        border-radius: 17px;
        background: #f3f6fc;
        box-shadow: 0 10px 28px rgba(27, 44, 71, 0.06);
    }

    .promo-image {
        display: grid;
        min-height: 245px;
        overflow: hidden;
        place-items: center;
        background: #fff;
    }

    .promo-image img {
        width: 100%;
        height: 100%;
        max-height: 300px;
        padding: 14px;
        object-fit: contain;
        mix-blend-mode: multiply;
        transition: transform 0.3s ease;
    }

    .promo-image:hover img {
        transform: scale(1.035);
    }

    .promo-content {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        padding: 28px clamp(22px, 5vw, 58px);
    }

    .promo-content h2 {
        display: -webkit-box;
        max-width: 430px;
        margin: 10px 0 7px;
        overflow: hidden;
        color: var(--store-ink);
        font-size: clamp(20px, 2.4vw, 30px);
        font-weight: 750;
        letter-spacing: -0.7px;
        line-height: 1.3;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .promo-content p {
        margin: 0 0 10px;
        color: var(--store-muted);
        font-size: 12px;
        line-height: 1.7;
    }

    .promo-price {
        margin-bottom: 16px;
        color: var(--store-blue);
        font-size: 18px;
    }

    .promo-cta {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 7px;
        background: var(--store-blue);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        transition: background-color 0.2s ease;
    }

    .promo-cta:hover {
        background: #164fc8;
        color: #fff;
    }

    .products-section {
        margin-top: 50px;
        padding-top: 48px;
        padding-bottom: 52px;
        background: #f5f7fc;
    }

    .new-products-section {
        padding-bottom: 48px;
        background: #fff;
    }

    .products-after-promo {
        margin-top: 0;
        padding-top: 48px;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 17px;
    }

    .product-card {
        min-width: 0;
        overflow: hidden;
        border: 1px solid var(--store-border);
        border-radius: 14px;
        background: #fff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .product-card:hover {
        box-shadow: 0 12px 26px rgba(35, 57, 91, 0.09);
        transform: translateY(-3px);
    }

    .product-image {
        position: relative;
        display: grid;
        aspect-ratio: 1 / 0.88;
        overflow: hidden;
        place-items: center;
        background:
            radial-gradient(ellipse at center, #fff 20%, #f5f7fb 100%);
        text-decoration: none;
    }

    .product-image > img {
        width: 100%;
        height: 100%;
        padding: 15px;
        object-fit: contain;
        mix-blend-mode: multiply;
        transition: transform 0.25s ease;
    }

    .product-card:hover .product-image > img {
        transform: scale(1.04);
    }

    .product-image > .image-placeholder {
        width: 76%;
        height: 76%;
    }

    .product-category {
        position: absolute;
        top: 11px;
        left: 11px;
        max-width: calc(100% - 22px);
        overflow: hidden;
        padding: 5px 8px;
        border: 1px solid rgba(231, 236, 247, 0.9);
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.92);
        color: #52617b;
        font-size: 9px;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .product-card-body {
        padding: 15px;
    }

    .product-code {
        color: #8994a8;
        font-size: 10px;
        letter-spacing: 0.3px;
    }

    .product-card h3 {
        display: -webkit-box;
        min-height: 43px;
        margin: 7px 0 12px;
        overflow: hidden;
        font-size: 14px;
        font-weight: 650;
        line-height: 1.5;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .product-card h3 a {
        color: var(--store-ink);
        text-decoration: none;
    }

    .product-card h3 a:hover {
        color: var(--store-blue);
    }

    .product-card-bottom {
        display: flex;
        min-height: 24px;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        margin-bottom: 13px;
    }

    .product-price {
        color: #1c2b45;
        font-size: 15px;
        font-weight: 750;
        white-space: nowrap;
    }

    .stock-status {
        color: #258257;
        font-size: 10px;
        text-align: right;
    }

    .stock-empty {
        color: #a45b3e;
    }

    .product-action {
        display: flex;
        min-height: 37px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: 1px solid #dfe7fa;
        border-radius: 8px;
        background: #f6f8ff;
        color: #315eb7;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .product-action:hover {
        border-color: var(--store-blue);
        background: var(--store-blue);
        color: #fff;
    }

    .empty-products {
        padding: 36px;
        border: 1px dashed #cbd4e4;
        border-radius: 12px;
        background: #fff;
        color: var(--store-muted);
        text-align: center;
    }

    @media (max-width: 991.98px) {
        .hero-feature {
            min-height: 365px;
        }

        .hero-copy {
            width: 55%;
            padding-left: 28px;
        }

        .hero-image {
            width: 38%;
            padding: 14px;
        }

        .hero-indicators {
            left: 28px;
        }

        .product-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .home-hero-section {
            padding-top: 17px;
            padding-bottom: 25px;
        }

        .hero-feature {
            min-height: 360px;
            border-radius: 16px;
        }

        .hero-copy {
            width: 58%;
            padding: 27px 0 25px 24px;
        }

        .hero-copy h1 {
            font-size: clamp(29px, 6vw, 40px);
        }

        .hero-cta {
            gap: 8px;
            padding: 9px 10px;
            font-size: 11px;
            white-space: nowrap;
        }

        .hero-copy > p {
            max-width: 290px;
            font-size: 12px;
        }

        .hero-image {
            top: 22%;
            right: 3%;
            width: 34%;
            height: 60%;
            padding: 8px;
            border-radius: 13px;
        }

        .hero-indicators {
            left: 24px;
        }

        .feature-promo {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }

        .feature-promo-section {
            padding: 36px 0;
        }

        .promo-image {
            min-height: 210px;
        }

        .home-section {
            padding-top: 39px;
        }

        .new-products-section {
            padding-bottom: 36px;
        }

        .products-section {
            margin-top: 35px;
            padding-top: 37px;
            padding-bottom: 38px;
        }

        .products-after-promo {
            margin-top: 0;
            padding-top: 37px;
        }

        .product-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 11px;
        }

        .product-card-body {
            padding: 12px;
        }

        .product-price {
            font-size: 13px;
        }

    }

    @media (max-width: 420px) {
        .hero-feature {
            min-height: 340px;
        }

        .hero-copy {
            width: 58%;
            padding-left: 18px;
        }

        .hero-eyebrow {
            font-size: 8px;
        }

        .hero-copy h1 {
            font-size: 21px;
        }

        .feature-promo {
            grid-template-columns: 1fr;
        }

        .feature-promo-section {
            padding: 30px 0;
        }

        .promo-image {
            min-height: 180px;
            max-height: 220px;
        }

        .promo-content {
            padding: 22px;
        }

        .hero-copy h1 {
            font-size: 25px;
        }

        .hero-product-note > strong {
            max-width: 220px;
        }

        .hero-mini-card {
            padding: 10px;
        }

        .hero-mini-image {
            width: 54px;
            height: 54px;
            flex-basis: 54px;
        }

        .section-heading {
            align-items: flex-start;
        }

        .section-link {
            gap: 4px;
            font-size: 10px;
            white-space: nowrap;
        }

        .product-card h3 {
            font-size: 12px;
        }
    }
</style>

<?php
require_once '/var/www/src/includes/frontend/footer.php';
