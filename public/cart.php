<?php
require_once '/var/www/src/config/session.php';
require_once '/var/www/src/config/database.php';
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_product'])) {

        $productID = filter_var(
            $_POST['remove_product'],
            FILTER_VALIDATE_INT
        );

        if ($productID !== false && $productID > 0) {
            unset($_SESSION['cart'][$productID]);
        }

        header('Location: /cart.php');
        exit;
    }
    if (isset($_POST['update_cart'])) {

        $quantities = $_POST['quantities'] ?? [];

        if (!is_array($quantities)) {
            $quantities = [];
        }

        foreach ($quantities as $productID => $quantity) {

            $productID = filter_var(
                (string) $productID,
                FILTER_VALIDATE_INT
            );

            if ($productID === false || $productID <= 0) {
                continue;
            }

            // Chỉ chấp nhận số lượng là số nguyên dương hợp lệ.
            $quantity = filter_var(
                $quantity,
                FILTER_VALIDATE_INT
            );

            // Số lượng không hợp lệ hoặc nhỏ hơn 1: xóa sản phẩm.
            if ($quantity === false || $quantity < 1) {
                unset($_SESSION['cart'][$productID]);
                continue;
            }

            /*
             * Kiểm tra sản phẩm còn hoạt động và lấy tồn kho.
             */

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

            // Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.
            if (!$stockRow) {
                unset($_SESSION['cart'][$productID]);
                continue;
            }

            $stockQuantity = (int) $stockRow['StockQuantity'];

            // Hết hàng thì loại khỏi giỏ.
            if ($stockQuantity <= 0) {
                unset($_SESSION['cart'][$productID]);
                continue;
            }

            // Không cho phép đặt số lượng vượt tồn kho.
            $_SESSION['cart'][$productID] = min(
                $quantity,
                $stockQuantity
            );
        }

        header('Location: /cart.php');
        exit;
    }
}
$cart = $_SESSION['cart'];

$cartItems = [];
$totalAmount = 0;

if (!empty($cart)) {

    $sql = "
        SELECT
            p.ProductID,
            p.ProductCode,
            p.ProductName,
            p.Price,
            p.StockQuantity,
            (
                SELECT pi.ImageFile
                FROM product_images pi
                WHERE pi.ProductID = p.ProductID
                  AND pi.IsPrimary = 1
                LIMIT 1
            ) AS ImageFile
        FROM products p
        WHERE p.ProductID = ?
          AND p.IsActive = 1
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    foreach ($cart as $productID => $quantity) {

        $productID = filter_var(
            (string) $productID,
            FILTER_VALIDATE_INT
        );

        $quantity = filter_var(
            $quantity,
            FILTER_VALIDATE_INT
        );

        if (
            $productID === false ||
            $productID <= 0 ||
            $quantity === false ||
            $quantity <= 0
        ) {
            if ($productID !== false && $productID > 0) {
                unset($_SESSION['cart'][$productID]);
            }

            continue;
        }

        $stmt->bind_param('i', $productID);
        $stmt->execute();

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        // Không hiển thị sản phẩm đã bị xóa hoặc ngừng kinh doanh.
        if (!$product) {
            unset($_SESSION['cart'][$productID]);
            continue;
        }

        $stockQuantity = (int) $product['StockQuantity'];

        // Loại sản phẩm hết hàng khỏi giỏ.
        if ($stockQuantity <= 0) {
            unset($_SESSION['cart'][$productID]);
            continue;
        }

        // Đồng bộ số lượng với tồn kho hiện tại.
        $quantity = min($quantity, $stockQuantity);

        $_SESSION['cart'][$productID] = $quantity;

        $product['Quantity'] = $quantity;

        $product['Subtotal'] =
            (float) $product['Price'] * $quantity;

        $cartItems[] = $product;

        $totalAmount += $product['Subtotal'];
    }

    $stmt->close();
}

$pageTitle = 'Giỏ hàng';

require_once '/var/www/src/includes/frontend/header.php';
require_once '/var/www/src/includes/frontend/navbar.php';

?>

<main class="container py-5">

    <!-- Tiêu đề -->
    <div class="mb-4">
        <h1>Giỏ hàng</h1>

        <p class="text-muted">
            Các sản phẩm bạn đã chọn.
        </p>
    </div>

    <?php if (empty($cartItems)): ?>

        <!-- Giỏ hàng trống -->
        <div class="alert alert-info">
            Giỏ hàng của bạn đang trống.
        </div>

        <a
            href="/products.php"
            class="btn btn-primary"
        >
            Tiếp tục mua hàng
        </a>

    <?php else: ?>

        <form method="post" action="/cart.php">

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-end">Thành tiền</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($cartItems as $item): ?>

                            <tr>

                                <!-- Thông tin sản phẩm -->
                                <td>
                                    <div class="d-flex align-items-center gap-3">

                                        <?php if (!empty($item['ImageFile'])): ?>

                                            <img
                                                src="/uploads/products/<?= htmlspecialchars(
                                                    $item['ImageFile'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                alt="<?= htmlspecialchars(
                                                    $item['ProductName'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                loading="lazy"
                                                style="
                                                    width: 80px;
                                                    height: 80px;
                                                    object-fit: contain;
                                                "
                                            >

                                        <?php else: ?>

                                            <div
                                                class="bg-light border rounded d-flex align-items-center justify-content-center"
                                                style="
                                                    width: 80px;
                                                    height: 80px;
                                                "
                                            >
                                                <span class="text-muted small">
                                                    Không có ảnh
                                                </span>
                                            </div>

                                        <?php endif; ?>

                                        <div>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $item['ProductName'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </strong>

                                            <div class="text-muted small">
                                                Mã sản phẩm:
                                                <?= htmlspecialchars(
                                                    $item['ProductCode'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </div>

                                            <div class="text-muted small">
                                                Tồn kho:
                                                <?= (int) $item['StockQuantity'] ?>
                                            </div>

                                        </div>

                                    </div>
                                </td>

                                <!-- Đơn giá -->
                                <td class="text-end">
                                    <?= number_format(
                                        (float) $item['Price'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?> đ
                                </td>

                                <!-- Số lượng -->
                                <td class="text-center">

                                    <input
                                        type="number"
                                        name="quantities[<?= (int) $item['ProductID'] ?>]"
                                        value="<?= (int) $item['Quantity'] ?>"
                                        min="1"
                                        max="<?= (int) $item['StockQuantity'] ?>"
                                        step="1"
                                        required
                                        class="form-control mx-auto"
                                        style="width: 90px;"
                                        aria-label="Số lượng <?= htmlspecialchars(
                                            $item['ProductName'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >

                                </td>

                                <!-- Thành tiền -->
                                <td class="text-end fw-bold">
                                    <?= number_format(
                                        (float) $item['Subtotal'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?> đ
                                </td>

                                <!-- Xóa sản phẩm -->
                                <td class="text-center">

                                    <button
                                        type="submit"
                                        name="remove_product"
                                        value="<?= (int) $item['ProductID'] ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        formnovalidate
                                        onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?');"
                                    >
                                        Xóa
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                    <!-- Tổng tiền -->
                    <tfoot>

                        <tr>

                            <th colspan="3" class="text-end">
                                Tổng cộng
                            </th>

                            <th class="text-end fs-5 text-primary">
                                <?= number_format(
                                    $totalAmount,
                                    0,
                                    ',',
                                    '.'
                                ) ?> đ
                            </th>

                            <th></th>

                        </tr>

                    </tfoot>

                </table>

            </div>

            <!-- Nút cập nhật -->
            <div class="d-flex justify-content-end mt-3">

                <button
                    type="submit"
                    name="update_cart"
                    value="1"
                    class="btn btn-primary"
                >
                    Cập nhật giỏ hàng
                </button>

            </div>

        </form>

        <!-- Nút tiếp tục mua hàng -->
        <div class="mt-4 d-flex flex-wrap gap-2">

            <a
                href="/products.php"
                class="btn btn-outline-secondary"
            >
                <span aria-hidden="true">&larr;</span>
                Tiếp tục mua hàng
            </a>

        </div>

    <?php endif; ?>

</main>

<?php

require_once '/var/www/src/includes/frontend/footer.php';
