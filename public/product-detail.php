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
        IsPrimary
    FROM product_images
    WHERE ProductID = ?
    ORDER BY IsPrimary DESC, ProductImageID ASC
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
    && isset($_POST['add_to_cart'])
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
                }
            }
        }
    }
}
require_once '/var/www/src/includes/frontend/header.php';
require_once '/var/www/src/includes/frontend/navbar.php';

?>

<main class="container py-5">

    <!-- Đường dẫn quay lại -->
    <div class="mb-4">
        <a href="/products.php" class="text-decoration-none">
            &larr; Quay lại danh sách sản phẩm
        </a>
    </div>

    <div class="row g-5">

        <!-- Hình ảnh sản phẩm -->
        <div class="col-lg-6">

            <?php if (!empty($images)): ?>

                <div
                    class="bg-light d-flex align-items-center justify-content-center p-3 mb-3 rounded"
                    style="height: 420px;"
                >
                    <img
                        src="/uploads/products/<?= htmlspecialchars(
                            $images[0]['ImageFile'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        alt="<?= htmlspecialchars(
                            $product['ProductName'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        style="
                            width: 100%;
                            height: 100%;
                            object-fit: contain;
                        "
                    >
                </div>

                <?php if (count($images) > 1): ?>

                    <div class="row g-2">

                        <?php foreach ($images as $image): ?>

                            <div class="col-4">

                                <div
                                    class="border rounded bg-light d-flex align-items-center justify-content-center p-2"
                                    style="height: 120px;"
                                >
                                    <img
                                        src="/uploads/products/<?= htmlspecialchars(
                                            $image['ImageFile'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $product['ProductName'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        loading="lazy"
                                        style="
                                            width: 100%;
                                            height: 100%;
                                            object-fit: contain;
                                        "
                                    >
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            <?php else: ?>

                <div
                    class="bg-light d-flex align-items-center justify-content-center text-muted rounded"
                    style="height: 420px;"
                >
                    Chưa có hình ảnh
                </div>

            <?php endif; ?>

        </div>

        <!-- Thông tin sản phẩm -->
        <div class="col-lg-6">

            <p class="text-muted mb-2">
                <?= htmlspecialchars(
                    $product['CategoryName'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <h1 class="mb-3">
                <?= htmlspecialchars(
                    $product['ProductName'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h1>

            <p class="text-muted">
                Mã sản phẩm:
                <?= htmlspecialchars(
                    $product['ProductCode'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <p class="fs-3 fw-bold text-primary">
                <?= number_format(
                    (float) $product['Price'],
                    0,
                    ',',
                    '.'
                ) ?> đ
            </p>

            <hr>

            <dl class="row">

                <dt class="col-sm-4">Danh mục</dt>
                <dd class="col-sm-8">
                    <?= htmlspecialchars(
                        $product['CategoryName'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </dd>

                <dt class="col-sm-4">Nhà cung cấp</dt>
                <dd class="col-sm-8">
                    <?= htmlspecialchars(
                        $product['SupplierName'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </dd>

                <dt class="col-sm-4">Đơn vị tính</dt>
                <dd class="col-sm-8">
                    <?= htmlspecialchars(
                        $product['Unit'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </dd>

                <dt class="col-sm-4">Tồn kho</dt>
                <dd class="col-sm-8">
                    <?= (int) $product['StockQuantity'] ?>
                </dd>

            </dl>

            <?php if (!empty($product['Description'])): ?>

                <hr>

                <h5>Mô tả sản phẩm</h5>

                <p>
                    <?= nl2br(htmlspecialchars(
                        $product['Description'],
                        ENT_QUOTES,
                        'UTF-8'
                    )) ?>
                </p>

            <?php endif; ?>

            <!-- Thông báo thêm giỏ hàng -->
            <?php if ($cartMessage !== ''): ?>

                <div class="alert alert-<?= $cartMessageType ?>">
                    <?= htmlspecialchars(
                        $cartMessage,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            <?php endif; ?>

            <!-- Form thêm giỏ hàng -->
            <?php if ((int) $product['StockQuantity'] > 0): ?>

                <form method="post" action="/product-detail.php?id=<?= (int) $productID ?>" class="mb-4">

                    <div class="row g-3 align-items-end">

                        <div class="col-auto">

                            <label for="quantity" class="form-label">
                                Số lượng
                            </label>

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
                                style="width: 110px;"
                            >

                        </div>

                        <div class="col-auto">

                            <button
                                type="submit"
                                name="add_to_cart"
                                value="1"
                                class="btn btn-primary"
                            >
                                <i class="bi bi-cart-plus"></i>
                                Thêm vào giỏ hàng
                            </button>

                        </div>

                    </div>

                </form>

            <?php else: ?>

                <div class="alert alert-warning">
                    Sản phẩm hiện đã hết hàng.
                </div>

            <?php endif; ?>

        </div>

    </div>

</main>

<?php

require_once '/var/www/src/includes/frontend/footer.php';
