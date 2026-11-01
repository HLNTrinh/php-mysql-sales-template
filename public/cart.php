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

<main class="cart-page">
    <div class="container cart-container">
        <a class="cart-back-link" href="/products.php">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
            <span>Giỏ hàng</span>
        </a>

        <?php if (empty($cartItems)): ?>
            <section class="cart-empty">
                <div class="cart-empty-icon" aria-hidden="true">
                    <i class="bi bi-cart3"></i>
                </div>
                <h1>Giỏ hàng đang trống</h1>
                <p>Hãy khám phá sản phẩm và thêm món bạn yêu thích vào giỏ hàng.</p>
                <a href="/products.php" class="btn btn-primary cart-primary-button">
                    Tiếp tục mua sắm
                </a>
            </section>
        <?php else: ?>
            <form method="post" action="/cart.php" class="cart-layout">
                <section class="cart-items-panel" aria-labelledby="cart-items-heading">
                    <div class="cart-panel-heading">
                        <div>
                            <h1 id="cart-items-heading">Danh sách sản phẩm</h1>
                            <p><?= count($cartItems) ?> sản phẩm trong giỏ hàng</p>
                        </div>
                        <a href="/products.php" class="cart-add-more">
                            Tiếp tục mua sắm
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>

                    <div class="cart-items-list">
                        <?php foreach ($cartItems as $item): ?>
                            <article class="cart-item">
                                <a
                                    class="cart-item-image"
                                    href="/product-detail.php?id=<?= (int) $item['ProductID'] ?>"
                                    aria-label="Xem <?= htmlspecialchars($item['ProductName'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <?php if (!empty($item['ImageFile'])): ?>
                                        <img
                                            src="/uploads/products/<?= htmlspecialchars($item['ImageFile'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt="<?= htmlspecialchars($item['ProductName'], ENT_QUOTES, 'UTF-8') ?>"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <span>Chưa có ảnh</span>
                                    <?php endif; ?>
                                </a>

                                <div class="cart-item-details">
                                    <a
                                        class="cart-item-name"
                                        href="/product-detail.php?id=<?= (int) $item['ProductID'] ?>"
                                    >
                                        <?= htmlspecialchars($item['ProductName'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <p class="cart-item-code">
                                        Mã sản phẩm: <?= htmlspecialchars($item['ProductCode'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                    <p class="cart-item-stock">
                                        Còn <?= (int) $item['StockQuantity'] ?> sản phẩm
                                    </p>
                                    <strong class="cart-item-price">
                                        <?= number_format((float) $item['Price'], 0, ',', '.') ?> đ
                                    </strong>
                                </div>

                                <div class="cart-item-controls">
                                    <label class="visually-hidden" for="quantity-<?= (int) $item['ProductID'] ?>">
                                        Số lượng <?= htmlspecialchars($item['ProductName'], ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input
                                        id="quantity-<?= (int) $item['ProductID'] ?>"
                                        type="number"
                                        name="quantities[<?= (int) $item['ProductID'] ?>]"
                                        value="<?= (int) $item['Quantity'] ?>"
                                        min="1"
                                        max="<?= (int) $item['StockQuantity'] ?>"
                                        step="1"
                                        required
                                        class="form-control cart-quantity-input"
                                    >
                                    <button
                                        type="submit"
                                        name="remove_product"
                                        value="<?= (int) $item['ProductID'] ?>"
                                        class="cart-remove-button"
                                        aria-label="Xóa <?= htmlspecialchars($item['ProductName'], ENT_QUOTES, 'UTF-8') ?> khỏi giỏ"
                                        formnovalidate
                                        onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?');"
                                    >
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                        <span>Xóa</span>
                                    </button>
                                </div>

                                <div class="cart-item-subtotal">
                                    <?= number_format((float) $item['Subtotal'], 0, ',', '.') ?> đ
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="cart-update-row">
                        <button type="submit" name="update_cart" value="1" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>
                            Cập nhật số lượng
                        </button>
                    </div>
                </section>

                <aside class="cart-summary-column" aria-label="Tóm tắt đơn hàng">
                    <section class="cart-summary-card">
                        <h2>Chi tiết thanh toán</h2>
                        <div class="cart-summary-line">
                            <span>Tạm tính (<?= array_sum(array_column($cartItems, 'Quantity')) ?> sản phẩm)</span>
                            <span><?= number_format($totalAmount, 0, ',', '.') ?> đ</span>
                        </div>
                        <div class="cart-summary-line">
                            <span>Phí vận chuyển</span>
                            <span class="cart-shipping-note">Tính khi đặt hàng</span>
                        </div>
                        <div class="cart-summary-total">
                            <strong>Tổng tiền hàng</strong>
                            <strong><?= number_format($totalAmount, 0, ',', '.') ?> đ</strong>
                        </div>
                        <a href="/checkout.php" class="btn btn-primary cart-checkout-button">
                            Tiến hành đặt hàng
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                        <p class="cart-checkout-note">
                            <i class="bi bi-shield-check" aria-hidden="true"></i>
                            Kiểm tra thông tin trước khi xác nhận đơn hàng
                        </p>
                    </section>
                </aside>
            </form>
        <?php endif; ?>
    </div>
</main>

<style>
    .cart-page {
        min-height: 65vh;
        background: #f1f3f7;
    }

    .cart-container {
        max-width: 1320px;
        padding-top: 30px;
        padding-bottom: 60px;
    }

    .cart-back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 22px;
        color: #17243b;
        font-size: 24px;
        font-weight: 750;
        text-decoration: none;
    }

    .cart-back-link i {
        font-size: 19px;
    }

    .cart-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.8fr) minmax(300px, 0.95fr);
        align-items: start;
        gap: 20px;
    }

    .cart-items-panel,
    .cart-delivery-card,
    .cart-summary-card,
    .cart-empty {
        border: 1px solid #e9edf3;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 5px 20px rgb(22 34 51 / 4%);
    }

    .cart-items-panel {
        padding: 22px;
    }

    .cart-panel-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 18px;
        border-bottom: 1px solid #edf0f5;
    }

    .cart-panel-heading h1,
    .cart-summary-card h2 {
        margin: 0;
        color: #202d42;
        font-size: 21px;
        font-weight: 750;
    }

    .cart-panel-heading p {
        margin: 5px 0 0;
        color: #8490a2;
        font-size: 13px;
    }

    .cart-add-more {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #287ee5;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
    }

    .cart-items-list {
        display: grid;
    }

    .cart-item {
        display: grid;
        grid-template-columns: 108px minmax(0, 1fr) auto;
        align-items: center;
        gap: 18px;
        padding: 20px 0;
        border-bottom: 1px solid #edf0f5;
    }

    .cart-item:last-child {
        border-bottom: 0;
    }

    .cart-item-image {
        display: flex;
        width: 108px;
        height: 108px;
        align-items: center;
        justify-content: center;
        padding: 8px;
        border: 1px solid #edf0f5;
        border-radius: 12px;
        background: #fafbfc;
        color: #8490a2;
        font-size: 11px;
        text-decoration: none;
    }

    .cart-item-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .cart-item-details {
        min-width: 0;
    }

    .cart-item-name {
        display: inline-block;
        margin-bottom: 6px;
        color: #202d42;
        font-size: 16px;
        font-weight: 650;
        line-height: 1.4;
        text-decoration: none;
    }

    .cart-item-name:hover {
        color: #287ee5;
    }

    .cart-item-code,
    .cart-item-stock {
        margin: 0 0 4px;
        color: #8490a2;
        font-size: 12px;
    }

    .cart-item-price {
        display: inline-block;
        margin-top: 5px;
        color: #dc3545;
        font-size: 16px;
    }

    .cart-item-controls {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .cart-quantity-input {
        width: 76px;
        min-height: 38px;
        border-color: #e2e7ef;
        text-align: center;
    }

    .cart-remove-button {
        display: inline-flex;
        min-width: 64px;
        height: 38px;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 9px;
        border: 1px solid #f1d8da;
        border-radius: 10px;
        background: #fff;
        color: #dc3545;
        font-size: 12px;
        font-weight: 600;
        transition: background-color 0.2s ease;
    }

    .cart-remove-button i {
        font-size: 14px;
    }

    .cart-remove-button:hover {
        background: #fff1f2;
    }

    .cart-item-subtotal {
        grid-column: 2 / 4;
        justify-self: end;
        margin-top: -12px;
        color: #27364d;
        font-size: 13px;
        font-weight: 650;
    }

    .cart-update-row {
        display: flex;
        justify-content: flex-end;
        padding-top: 16px;
        border-top: 1px solid #edf0f5;
    }

    .cart-update-row .btn {
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
    }

    .cart-summary-column {
        position: sticky;
        top: 96px;
        display: grid;
        gap: 16px;
    }

    .cart-summary-card {
        padding: 22px;
    }

    .cart-summary-card h2 {
        margin-bottom: 20px;
    }

    .cart-summary-line,
    .cart-summary-total {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        padding: 12px 0;
        color: #68758a;
        font-size: 13px;
    }

    .cart-summary-line {
        border-bottom: 1px solid #edf0f5;
    }

    .cart-shipping-note {
        color: #8490a2;
        text-align: right;
    }

    .cart-summary-total {
        align-items: center;
        padding: 17px 0 20px;
        color: #27364d;
        font-size: 15px;
    }

    .cart-summary-total strong:last-child {
        color: #dc3545;
        font-size: 18px;
        white-space: nowrap;
    }

    .cart-checkout-button {
        display: flex;
        min-height: 50px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: 0;
        border-radius: 11px;
        background: #287ee5;
        font-weight: 700;
    }

    .cart-checkout-button:hover {
        background: #176bd0;
    }

    .cart-checkout-note {
        display: flex;
        align-items: flex-start;
        gap: 7px;
        margin: 13px 0 0;
        color: #8490a2;
        font-size: 11px;
        line-height: 1.5;
    }

    .cart-checkout-note i {
        color: #1b9b63;
        font-size: 14px;
    }

    .cart-empty {
        max-width: 560px;
        margin: 40px auto;
        padding: 48px 24px;
        text-align: center;
    }

    .cart-empty-icon {
        display: flex;
        width: 68px;
        height: 68px;
        align-items: center;
        justify-content: center;
        margin: 0 auto 18px;
        border-radius: 50%;
        background: #edf5ff;
        color: #287ee5;
        font-size: 28px;
    }

    .cart-empty h1 {
        color: #202d42;
        font-size: 22px;
        font-weight: 750;
    }

    .cart-empty p {
        margin-bottom: 22px;
        color: #8490a2;
        font-size: 14px;
    }

    .cart-primary-button {
        padding: 11px 20px;
        border-radius: 10px;
        background: #287ee5;
        font-weight: 650;
    }

    @media (max-width: 991.98px) {
        .cart-layout {
            grid-template-columns: minmax(0, 1fr);
        }

        .cart-summary-column {
            position: static;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

    }

    @media (max-width: 575.98px) {
        .cart-container {
            padding-top: 20px;
            padding-bottom: 38px;
        }

        .cart-back-link {
            margin-bottom: 16px;
            font-size: 21px;
        }

        .cart-items-panel {
            padding: 16px;
        }

        .cart-panel-heading {
            align-items: flex-start;
        }

        .cart-panel-heading h1 {
            font-size: 18px;
        }

        .cart-add-more {
            font-size: 0;
        }

        .cart-add-more i {
            font-size: 17px;
        }

        .cart-item {
            grid-template-columns: 78px minmax(0, 1fr);
            align-items: start;
            gap: 12px;
        }

        .cart-item-image {
            width: 78px;
            height: 78px;
        }

        .cart-item-name {
            font-size: 14px;
        }

        .cart-item-controls {
            grid-column: 2;
            justify-content: flex-start;
        }

        .cart-item-subtotal {
            grid-column: 2;
            justify-self: start;
            margin-top: -4px;
        }

        .cart-summary-column {
            grid-template-columns: minmax(0, 1fr);
        }

        .cart-summary-card {
            padding: 18px;
        }
    }
</style>

<?php

require_once '/var/www/src/includes/frontend/footer.php';
