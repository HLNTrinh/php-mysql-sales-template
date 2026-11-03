<?php
require_once '/var/www/src/config/session.php';
require_once '/var/www/src/config/database.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$productID = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($productID <= 0) {
    header('Location: /products.php');
    exit;
}
$sql = "
    SELECT
        p.ProductID,
        p.ProductCode,
        p.ProductName,
        p.Description,
        p.Unit,
        p.Price,
        p.StockQuantity,
        c.CategoryName,
        s.SupplierName
    FROM products p
    INNER JOIN categories c
        ON p.CategoryID = c.CategoryID
    INNER JOIN suppliers s
        ON p.SupplierID = s.SupplierID
    WHERE p.ProductID = ?
      AND p.IsActive = 1
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $productID);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

$result->free();
$stmt->close();

if (!$product) {
    header('Location: /products.php');
    exit;
}
$sqlImages = "
    SELECT
        ProductImageID AS ImageID,
        ImageFile,
        AltText,
        IsPrimary
    FROM product_images
    WHERE ProductID = ?
    ORDER BY IsPrimary DESC, SortOrder ASC, ProductImageID ASC
";

$stmtImages = $conn->prepare($sqlImages);
$stmtImages->bind_param('i', $productID);
$stmtImages->execute();

$imageResult = $stmtImages->get_result();

$images = [];

while ($image = $imageResult->fetch_assoc()) {
    $images[] = $image;
}

$imageResult->free();
$stmtImages->close();

/*
|--------------------------------------------------------------------------
| 3. Xử lý thêm sản phẩm vào giỏ hàng
|--------------------------------------------------------------------------
*/

$pageTitle = $product['ProductName'];
$cartMessage = '';
$cartMessageType = 'info';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (isset($_POST['add_to_cart']) || isset($_POST['buy_now']))
) {
    $quantity = filter_input(
        INPUT_POST,
        'quantity',
        FILTER_VALIDATE_INT
    );

    if ($quantity === false || $quantity === null || $quantity < 1) {

        $cartMessage = 'Vui lòng nhập số lượng hợp lệ.';
        $cartMessageType = 'danger';

    } else {

        // Kiểm tra lại trạng thái và tồn kho trong cơ sở dữ liệu.
        $sqlStock = "
            SELECT StockQuantity
            FROM products
            WHERE ProductID = ?
              AND IsActive = 1
            LIMIT 1
        ";

        $stmtStock = $conn->prepare($sqlStock);
        $stmtStock->bind_param('i', $productID);
        $stmtStock->execute();

        $stockResult = $stmtStock->get_result();
        $stockRow = $stockResult->fetch_assoc();

        $stmtStock->close();

        if (!$stockRow) {

            $cartMessage = 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.';
            $cartMessageType = 'danger';

        } else {

            $stockQuantity = (int) $stockRow['StockQuantity'];

            if ($stockQuantity <= 0) {

                $cartMessage = 'Sản phẩm hiện đã hết hàng.';
                $cartMessageType = 'warning';

            } else {

                $currentQuantity = (int) (
                    $_SESSION['cart'][$productID] ?? 0
                );

                if ($currentQuantity >= $stockQuantity) {

                    $cartMessage = 'Số lượng trong giỏ đã đạt mức tồn kho.';
                    $cartMessageType = 'warning';

                } else {

                    $newQuantity = min(
                        $currentQuantity + $quantity,
                        $stockQuantity
                    );

                    $_SESSION['cart'][$productID] = $newQuantity;

                    $cartMessage = 'Đã thêm sản phẩm vào giỏ hàng.';
                    $cartMessageType = 'success';

                    // Cập nhật tồn kho hiển thị.
                    $product['StockQuantity'] = $stockQuantity;

                    if (isset($_POST['buy_now'])) {
                        header('Location: /checkout.php');
                        exit;
                    }
                }
            }
        }
    }
}
require_once '/var/www/src/includes/frontend/header.php';
require_once '/var/www/src/includes/frontend/navbar.php';

?>

<main class="product-detail-page">
    <div class="container product-detail-container">
        <nav class="product-breadcrumb" aria-label="Điều hướng">
            <a href="/products.php">Sản phẩm</a>
            <span aria-hidden="true">/</span>
            <span><?= htmlspecialchars($product['CategoryName'], ENT_QUOTES, 'UTF-8') ?></span>
        </nav>

        <section class="row g-4 g-lg-5 product-overview">
            <div class="col-lg-5">
                <?php if (!empty($images)): ?>
                    <div class="product-gallery">
                        <div class="product-main-image">
                            <img
                                id="productMainImage"
                                src="/uploads/products/<?= htmlspecialchars($images[0]['ImageFile'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($images[0]['AltText'] ?: $product['ProductName'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>

                        <?php if (count($images) > 1): ?>
                            <div class="product-thumbnails" aria-label="Ảnh sản phẩm">
                                <?php foreach ($images as $index => $image): ?>
                                    <button
                                        type="button"
                                        class="product-thumbnail<?= $index === 0 ? ' is-selected' : '' ?>"
                                        data-image-src="/uploads/products/<?= htmlspecialchars($image['ImageFile'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-image-alt="<?= htmlspecialchars($image['AltText'] ?: $product['ProductName'], ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="Xem ảnh <?= $index + 1 ?>"
                                        aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"
                                    >
                                        <img
                                            src="/uploads/products/<?= htmlspecialchars($image['ImageFile'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt=""
                                            loading="lazy"
                                        >
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="product-main-image product-image-placeholder">
                        <span>Chưa có hình ảnh</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-7">
                <div class="product-summary">
                    <p class="product-category">
                        <?= htmlspecialchars($product['CategoryName'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <h1><?= htmlspecialchars($product['ProductName'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="product-code">
                        Mã sản phẩm:
                        <span><?= htmlspecialchars($product['ProductCode'], ENT_QUOTES, 'UTF-8') ?></span>
                    </p>
                    <p class="product-price">
                        <?= number_format((float) $product['Price'], 0, ',', '.') ?> đ
                    </p>

                    <div class="product-stock <?= (int) $product['StockQuantity'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                        <span class="stock-indicator" aria-hidden="true"></span>
                        <?= (int) $product['StockQuantity'] > 0
                            ? 'Còn hàng: ' . (int) $product['StockQuantity'] . ' ' . htmlspecialchars($product['Unit'] ?? 'sản phẩm', ENT_QUOTES, 'UTF-8')
                            : 'Sản phẩm hiện đã hết hàng' ?>
                    </div>

                    <?php if ($cartMessage !== ''): ?>
                        <div class="alert alert-<?= htmlspecialchars($cartMessageType, ENT_QUOTES, 'UTF-8') ?> mt-3 mb-0" role="status">
                            <?= htmlspecialchars($cartMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ((int) $product['StockQuantity'] > 0): ?>
                        <form method="post" action="/product-detail.php?id=<?= (int) $productID ?>" class="product-purchase-form">
                            <div class="quantity-control">
                                <label for="quantity">Số lượng</label>
                                <input
                                    type="number"
                                    name="quantity"
                                    id="quantity"
                                    class="form-control"
                                    value="1"
                                    min="1"
                                    max="<?= (int) $product['StockQuantity'] ?>"
                                    step="1"
                                    required
                                >
                            </div>
                            <div class="product-purchase-actions">
                                <button type="submit" name="add_to_cart" value="1" class="btn btn-outline-dark">
                                    <i class="bi bi-cart-plus" aria-hidden="true"></i>
                                    Thêm vào giỏ hàng
                                </button>
                                <button type="submit" name="buy_now" value="1" class="btn btn-primary">
                                    Mua ngay
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" disabled>Hết hàng</button>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if (!empty($product['Description'])): ?>
            <section class="product-description">
                <h2>Mô tả sản phẩm</h2>
                <p><?= nl2br(htmlspecialchars($product['Description'], ENT_QUOTES, 'UTF-8')) ?></p>
            </section>
        <?php endif; ?>

        <section class="product-specifications">
            <h2>Thông tin sản phẩm</h2>
            <dl class="specification-list">
                <div>
                    <dt>Mã sản phẩm</dt>
                    <dd><?= htmlspecialchars($product['ProductCode'], ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                    <dt>Danh mục</dt>
                    <dd><?= htmlspecialchars($product['CategoryName'], ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                    <dt>Nhà cung cấp</dt>
                    <dd><?= htmlspecialchars($product['SupplierName'], ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                    <dt>Đơn vị tính</dt>
                    <dd><?= htmlspecialchars($product['Unit'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div>
                    <dt>Tình trạng</dt>
                    <dd><?= (int) $product['StockQuantity'] > 0 ? 'Còn hàng' : 'Hết hàng' ?></dd>
                </div>
            </dl>
        </section>
    </div>
</main>

<style>
    .product-detail-page {
        min-height: 60vh;
        background: #fff;
    }

    .product-detail-container {
        max-width: 1120px;
        padding-top: 30px;
        padding-bottom: 64px;
    }

    .product-breadcrumb {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 26px;
        color: #788397;
        font-size: 13px;
    }

    .product-breadcrumb a {
        color: #788397;
        text-decoration: none;
    }

    .product-breadcrumb a:hover {
        color: #2463eb;
    }

    .product-gallery {
        position: sticky;
        top: 100px;
    }

    .product-main-image {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 420px;
        padding: 18px;
        border: 1px solid #edf0f5;
        border-radius: 14px;
        background: #fff;
    }

    .product-main-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .product-image-placeholder {
        color: #8a94a6;
        background: #f7f8fb;
    }

    .product-thumbnails {
        display: flex;
        gap: 12px;
        margin-top: 14px;
        overflow-x: auto;
        padding: 2px 2px 5px;
    }

    .product-thumbnail {
        flex: 0 0 76px;
        height: 76px;
        padding: 5px;
        border: 1px solid #e6e9ef;
        border-radius: 9px;
        background: #fff;
        cursor: pointer;
    }

    .product-thumbnail.is-selected,
    .product-thumbnail:hover {
        border-color: #2463eb;
        box-shadow: 0 0 0 2px rgb(36 99 235 / 10%);
    }

    .product-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .product-summary {
        padding: 7px 0;
    }

    .product-category {
        margin-bottom: 7px;
        color: #748097;
        font-size: 14px;
    }

    .product-summary h1 {
        margin-bottom: 8px;
        color: #17243b;
        font-size: clamp(26px, 3vw, 34px);
        font-weight: 750;
        letter-spacing: -0.8px;
    }

    .product-code {
        margin-bottom: 18px;
        color: #788397;
        font-size: 13px;
    }

    .product-code span {
        color: #35425a;
        font-weight: 600;
    }

    .product-price {
        margin-bottom: 20px;
        color: #2463eb;
        font-size: 30px;
        font-weight: 750;
        letter-spacing: -0.5px;
    }

    .product-stock {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 22px;
        color: #59677f;
        font-size: 14px;
    }

    .stock-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #1b9b63;
    }

    .out-of-stock .stock-indicator {
        background: #dc3545;
    }

    .product-purchase-form {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: 16px;
        padding-top: 20px;
        border-top: 1px solid #edf0f5;
    }

    .quantity-control label {
        display: block;
        margin-bottom: 7px;
        color: #26344c;
        font-size: 13px;
        font-weight: 650;
    }

    .quantity-control input {
        width: 90px;
        min-height: 42px;
    }

    .product-purchase-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .product-purchase-actions .btn {
        min-height: 42px;
        padding-inline: 17px;
        font-size: 14px;
        font-weight: 650;
    }

    .product-description,
    .product-specifications {
        max-width: 760px;
        margin: 42px auto 0;
    }

    .product-description h2,
    .product-specifications h2 {
        margin-bottom: 16px;
        color: #17243b;
        font-size: 23px;
        font-weight: 750;
    }

    .product-description p {
        margin: 0;
        color: #59677f;
        line-height: 1.75;
    }

    .specification-list {
        margin: 0;
        border: 1px solid #e8ecf3;
        border-radius: 10px;
        overflow: hidden;
    }

    .specification-list > div {
        display: grid;
        grid-template-columns: minmax(140px, 34%) 1fr;
        border-bottom: 1px solid #e8ecf3;
    }

    .specification-list > div:last-child {
        border-bottom: 0;
    }

    .specification-list dt,
    .specification-list dd {
        margin: 0;
        padding: 11px 14px;
        font-size: 13px;
    }

    .specification-list dt {
        background: #f7f8fb;
        color: #35425a;
        font-weight: 650;
    }

    .specification-list dd {
        color: #59677f;
    }

    @media (max-width: 767.98px) {
        .product-detail-container {
            padding-top: 20px;
            padding-bottom: 42px;
        }

        .product-gallery {
            position: static;
        }

        .product-main-image {
            height: min(390px, 90vw);
        }

        .product-summary {
            padding-top: 14px;
        }

        .product-price {
            font-size: 27px;
        }

        .product-description,
        .product-specifications {
            margin-top: 34px;
        }
    }
</style>

<script>
    document.querySelectorAll('.product-thumbnail').forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => {
            const mainImage = document.getElementById('productMainImage');

            if (!mainImage) {
                return;
            }

            mainImage.src = thumbnail.dataset.imageSrc;
            mainImage.alt = thumbnail.dataset.imageAlt;

            document.querySelectorAll('.product-thumbnail').forEach((item) => {
                const isSelected = item === thumbnail;
                item.classList.toggle('is-selected', isSelected);
                item.setAttribute('aria-pressed', String(isSelected));
            });
        });
    });
</script>

<?php

require_once '/var/www/src/includes/frontend/footer.php';
