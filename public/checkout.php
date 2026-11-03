<?php
require_once '/var/www/src/config/session.php';
require_once '/var/www/src/config/database.php';

$pageTitle = 'Đặt hàng';

// 1. Bắt buộc đăng nhập
if (
    !isset($_SESSION['customer_id']) ||
    (int) $_SESSION['customer_id'] <= 0
) {
    header('Location: /login.php');
    exit;
}

$customerID = (int) $_SESSION['customer_id'];

// 2. Lấy giỏ hàng
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header('Location: /cart.php');
    exit;
}

// 3. Lấy thông tin tài khoản khách hàng từ database
$sqlCustomerAccount = "
    SELECT
        CustomerID,
        CustomerName,
        Email,
        Phone,
        Address
    FROM customers
    WHERE CustomerID = ?
";

$stmtCustomerAccount = $conn->prepare($sqlCustomerAccount);
$stmtCustomerAccount->bind_param('i', $customerID);
$stmtCustomerAccount->execute();

$customerResult = $stmtCustomerAccount->get_result();
$customer = $customerResult->fetch_assoc();

$customerResult->free();
$stmtCustomerAccount->close();

if (!$customer) {
    unset(
        $_SESSION['customer_id'],
        $_SESSION['customer_name']
    );

    header('Location: /login.php');
    exit;
}

$customerName = $customer['CustomerName'];
$email = $customer['Email'] ?? '';
$phone = $customer['Phone'] ?? '';
$address = $customer['Address'] ?? '';

$errorMessage = '';

// 4. Hàm lấy danh sách sản phẩm trong giỏ hàng
function getCartItems($conn, $cart)
{
    $items = [];
    $total = 0;

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
    ";

    $stmt = $conn->prepare($sql);

    foreach ($cart as $productID => $quantity) {
        $productID = (int) $productID;
        $quantity = (int) $quantity;

        if ($productID <= 0 || $quantity <= 0) {
            continue;
        }

        $stmt->bind_param('i', $productID);
        $stmt->execute();

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $result->free();

        if (!$product) {
            continue;
        }

        $product['Quantity'] = $quantity;
        $product['Subtotal'] =
            (float) $product['Price'] * $quantity;

        $total += $product['Subtotal'];
        $items[] = $product;
    }

    $stmt->close();

    return [
        'items' => $items,
        'total' => $total
    ];
}

$cartData = getCartItems($conn, $cart);
$cartItems = $cartData['items'];
$total = $cartData['total'];

if (empty($cartItems)) {
    header('Location: /cart.php');
    exit;
}

// 5. Xử lý đặt hàng
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['place_order'])
) {
    // Không nhận CustomerID hoặc tên khách hàng từ form.
    // Danh tính khách hàng lấy từ Session.
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($phone === '' || $address === '') {
        $errorMessage =
            'Vui lòng nhập đầy đủ số điện thoại và địa chỉ giao hàng.';
    } else {
        $transactionStarted = false;

        try {
            $conn->begin_transaction();
            $transactionStarted = true;

            // 5.1. Khóa và kiểm tra tài khoản khách hàng
            $sqlAccount = "
                SELECT CustomerID
                FROM customers
                WHERE CustomerID = ?
                FOR UPDATE
            ";

            $stmtAccount = $conn->prepare($sqlAccount);
            $stmtAccount->bind_param('i', $customerID);
            $stmtAccount->execute();

            $accountResult = $stmtAccount->get_result();
            $account = $accountResult->fetch_assoc();

            $accountResult->free();
            $stmtAccount->close();

            if (!$account) {
                throw new Exception(
                    'Tài khoản khách hàng không còn hợp lệ.'
                );
            }

            // 5.2. Kiểm tra sản phẩm và tồn kho
            $orderItems = [];
            $orderTotal = 0;

            $sqlProduct = "
                SELECT
                    ProductID,
                    ProductName,
                    Price,
                    StockQuantity
                FROM products
                WHERE ProductID = ?
                  AND IsActive = 1
                FOR UPDATE
            ";

            $stmtProduct = $conn->prepare($sqlProduct);

            foreach ($cart as $productID => $quantity) {
                $productID = (int) $productID;
                $quantity = (int) $quantity;

                if ($productID <= 0 || $quantity <= 0) {
                    throw new Exception(
                        'Dữ liệu giỏ hàng không hợp lệ.'
                    );
                }

                $stmtProduct->bind_param('i', $productID);
                $stmtProduct->execute();

                $productResult = $stmtProduct->get_result();
                $product = $productResult->fetch_assoc();

                $productResult->free();

                if (!$product) {
                    throw new Exception(
                        'Có sản phẩm không còn khả dụng.'
                    );
                }

                if (
                    $quantity >
                    (int) $product['StockQuantity']
                ) {
                    throw new Exception(
                        'Sản phẩm "' .
                        $product['ProductName'] .
                        '" không đủ số lượng tồn kho.'
                    );
                }

                $unitPrice = (float) $product['Price'];
                $subtotal = $unitPrice * $quantity;

                $orderTotal += $subtotal;

                $orderItems[] = [
                    'ProductID' => $productID,
                    'Quantity' => $quantity,
                    'UnitPrice' => $unitPrice
                ];
            }

            $stmtProduct->close();

            // 5.3. Lấy nhân viên xử lý đơn hàng
            $employeeResult = $conn->query(
                'SELECT EmployeeID
                 FROM employees
                 ORDER BY EmployeeID
                 LIMIT 1'
            );

            $employee = $employeeResult->fetch_assoc();
            $employeeResult->free();

            if (!$employee) {
                throw new Exception(
                    'Hiện chưa có nhân viên để xử lý đơn hàng.'
                );
            }

            // 5.4. Lấy đơn vị vận chuyển
            $shipperResult = $conn->query(
                'SELECT ShipperID
                 FROM shippers
                 ORDER BY ShipperID
                 LIMIT 1'
            );

            $shipper = $shipperResult->fetch_assoc();
            $shipperResult->free();

            if (!$shipper) {
                throw new Exception(
                    'Hiện chưa có đơn vị vận chuyển để giao hàng.'
                );
            }

            $employeeID = (int) $employee['EmployeeID'];
            $shipperID = (int) $shipper['ShipperID'];

            // 5.5. Cập nhật thông tin giao hàng
            // Không INSERT khách hàng mới.
            $sqlUpdateCustomer = "
                UPDATE customers
                SET Phone = ?, Address = ?
                WHERE CustomerID = ?
            ";

            $stmtUpdateCustomer =
                $conn->prepare($sqlUpdateCustomer);

            $stmtUpdateCustomer->bind_param(
                'ssi',
                $phone,
                $address,
                $customerID
            );

            $stmtUpdateCustomer->execute();
            $stmtUpdateCustomer->close();

            // 5.6. Tạo đơn hàng cho CustomerID hiện tại
            $status = 'Pending';

            $sqlOrder = "
                INSERT INTO orders (
                    OrderDate,
                    TotalAmount,
                    Status,
                    CustomerID,
                    EmployeeID,
                    ShipperID
                )
                VALUES (CURRENT_DATE, ?, ?, ?, ?, ?)
            ";

            $stmtOrder = $conn->prepare($sqlOrder);

            $stmtOrder->bind_param(
                'dsiii',
                $orderTotal,
                $status,
                $customerID,
                $employeeID,
                $shipperID
            );

            $stmtOrder->execute();

            $orderID = $conn->insert_id;
            $stmtOrder->close();

            // 5.7. Thêm chi tiết đơn hàng
            $sqlDetail = "
                INSERT INTO orderdetail (
                    Quantity,
                    UnitPrice,
                    OrderID,
                    ProductID
                )
                VALUES (?, ?, ?, ?)
            ";

            $stmtDetail = $conn->prepare($sqlDetail);

            // 5.8. Trừ số lượng tồn kho
            $sqlStock = "
                UPDATE products
                SET StockQuantity = StockQuantity - ?
                WHERE ProductID = ?
                  AND StockQuantity >= ?
            ";

            $stmtStock = $conn->prepare($sqlStock);

            foreach ($orderItems as $item) {
                $quantity = $item['Quantity'];
                $unitPrice = $item['UnitPrice'];
                $productID = $item['ProductID'];

                $stmtDetail->bind_param(
                    'idii',
                    $quantity,
                    $unitPrice,
                    $orderID,
                    $productID
                );

                $stmtDetail->execute();

                $stmtStock->bind_param(
                    'iii',
                    $quantity,
                    $productID,
                    $quantity
                );

                $stmtStock->execute();

                if ($stmtStock->affected_rows !== 1) {
                    throw new Exception(
                        'Không thể cập nhật tồn kho sản phẩm.'
                    );
                }
            }

            $stmtDetail->close();
            $stmtStock->close();

            // 5.9. Hoàn tất giao dịch
            $conn->commit();
            $transactionStarted = false;

            // Cập nhật Session và xóa giỏ hàng
            $_SESSION['customer_name'] = $customerName;
            $_SESSION['cart'] = [];

            header(
                'Location: /order-success.php?id=' . $orderID
            );
            exit;

        } catch (Throwable $e) {
            if ($transactionStarted) {
                $conn->rollback();
            }

            error_log(
                'Checkout error: ' . $e->getMessage()
            );

            $errorMessage = $e instanceof Exception
                ? $e->getMessage()
                : 'Không thể đặt hàng. Vui lòng thử lại.';

            // Không mất dữ liệu đã nhập khi đặt hàng thất bại.
        }
    }
}

// Tải lại giỏ hàng sau khi POST thất bại
$cartData = getCartItems($conn, $_SESSION['cart'] ?? []);
$cartItems = $cartData['items'];
$total = $cartData['total'];

require_once '/var/www/src/includes/frontend/header.php';
require_once '/var/www/src/includes/frontend/navbar.php';
?>

<main class="checkout-page">
    <div class="container checkout-container">
        <a class="checkout-back-link" href="/cart.php">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
            Giỏ hàng
        </a>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger checkout-error" role="alert">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" class="checkout-layout">
            <div class="checkout-details">
                <section class="checkout-card">
                    <h1>Thông tin khách hàng</h1>
                    <div class="checkout-customer-grid">
                        <div class="checkout-readonly-field">
                            <span>Họ tên</span>
                            <strong><?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="checkout-readonly-field">
                            <span>Số điện thoại</span>
                            <strong><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="checkout-readonly-field checkout-email-field">
                            <span>Email</span>
                            <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                </section>

                <section class="checkout-card">
                    <h2>Thông tin nhận hàng</h2>
                    <div class="checkout-form-grid">
                        <div class="checkout-field">
                            <label for="recipient_name">Tên người nhận</label>
                            <input
                                type="text"
                                class="form-control"
                                id="recipient_name"
                                value="<?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?>"
                                readonly
                            >
                        </div>
                        <div class="checkout-field">
                            <label for="phone">Số điện thoại người nhận</label>
                            <input
                                type="tel"
                                class="form-control"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="tel"
                                required
                            >
                        </div>
                        <div class="checkout-field checkout-full-width">
                            <label for="address">Địa chỉ giao hàng</label>
                            <textarea
                                class="form-control"
                                id="address"
                                name="address"
                                rows="3"
                                autocomplete="street-address"
                                required
                            ><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="checkout-order-column" aria-label="Thông tin đơn hàng">
                <section class="checkout-card checkout-order-card">
                    <div class="checkout-order-heading">
                        <h2>Đơn hàng</h2>
                        <span>Sản phẩm trong giỏ hàng</span>
                    </div>

                    <div class="checkout-order-table-heading">
                        <span>Sản phẩm</span>
                        <span>Thành tiền</span>
                    </div>

                    <div class="checkout-order-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="checkout-order-item">
                                <div class="checkout-order-product">
                                    <strong><?= htmlspecialchars($item['ProductName'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span>
                                        Đơn giá:
                                        <?= number_format((float) $item['Price'], 0, ',', '.') ?> đ
                                        × <?= (int) $item['Quantity'] ?>
                                    </span>
                                </div>
                                <strong class="checkout-order-subtotal">
                                    <?= number_format((float) $item['Subtotal'], 0, ',', '.') ?> đ
                                </strong>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="checkout-total-line">
                        <span>Tạm tính</span>
                        <strong><?= number_format((float) $total, 0, ',', '.') ?> đ</strong>
                    </div>
                    <div class="checkout-total-line checkout-grand-total">
                        <span>Tổng tiền</span>
                        <strong><?= number_format((float) $total, 0, ',', '.') ?> đ</strong>
                    </div>

                    <button
                        type="submit"
                        name="place_order"
                        value="1"
                        class="btn btn-primary checkout-submit-button"
                    >
                        Đặt hàng
                    </button>
                    <p class="checkout-payment-note">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        Thanh toán khi nhận hàng
                    </p>
                </section>
            </aside>
        </form>
    </div>
</main>

<style>
    .checkout-page {
        min-height: 65vh;
        background: #f1f3f7;
    }

    .checkout-container {
        max-width: 1160px;
        padding-top: 24px;
        padding-bottom: 56px;
    }

    .checkout-back-link {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        color: #17243b;
        font-size: 23px;
        font-weight: 750;
        text-decoration: none;
    }

    .checkout-back-link i {
        font-size: 18px;
    }

    .checkout-error {
        margin-bottom: 18px;
        border-radius: 12px;
    }

    .checkout-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.8fr) minmax(310px, 1fr);
        align-items: start;
        gap: 16px;
    }

    .checkout-details,
    .checkout-order-column {
        display: grid;
        gap: 14px;
    }

    .checkout-order-column {
        position: sticky;
        top: 96px;
    }

    .checkout-card {
        padding: 18px;
        border: 1px solid #e8ecf2;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 4px 16px rgb(22 34 51 / 4%);
    }

    .checkout-card h1,
    .checkout-card h2 {
        margin: 0 0 14px;
        color: #202d42;
        font-size: 20px;
        font-weight: 750;
    }

    .checkout-customer-grid,
    .checkout-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 22px;
    }

    .checkout-readonly-field {
        display: grid;
        gap: 6px;
        min-width: 0;
        padding: 10px 4px;
        border-bottom: 1px solid #edf0f5;
    }

    .checkout-readonly-field span,
    .checkout-field label {
        color: #8490a2;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }

    .checkout-readonly-field strong {
        overflow-wrap: anywhere;
        color: #35425a;
        font-size: 16px;
        font-weight: 500;
    }

    .checkout-email-field {
        grid-column: 1 / -1;
    }

    .checkout-form-grid {
        row-gap: 12px;
    }

    .checkout-field {
        display: grid;
        gap: 6px;
    }

    .checkout-field .form-control {
        min-height: 48px;
        border-color: #e4e9f0;
        border-radius: 8px;
        color: #35425a;
        font-size: 16px;
    }

    .checkout-field textarea.form-control {
        min-height: 96px;
        resize: vertical;
    }

    .checkout-field .form-control:focus {
        border-color: #83b7f5;
        box-shadow: 0 0 0 3px rgb(40 126 229 / 12%);
    }

    .checkout-field .form-control[readonly] {
        background: #f7f8fb;
    }

    .checkout-full-width {
        grid-column: 1 / -1;
    }

    .checkout-order-card {
        padding: 18px;
    }

    .checkout-order-heading {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px;
    }

    .checkout-order-heading h2 {
        margin-bottom: 3px;
    }

    .checkout-order-heading > span {
        color: #8490a2;
        font-size: 13px;
    }

    .checkout-order-table-heading,
    .checkout-order-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
    }

    .checkout-order-table-heading {
        padding: 9px 0;
        border-bottom: 1px solid #edf0f5;
        color: #8490a2;
        font-size: 13px;
    }

    .checkout-order-items {
        max-height: 340px;
        overflow-y: auto;
    }

    .checkout-order-item {
        align-items: flex-start;
        padding: 10px 0;
        border-bottom: 1px solid #edf0f5;
    }

    .checkout-order-product {
        display: grid;
        gap: 4px;
        min-width: 0;
    }

    .checkout-order-product strong {
        color: #27364d;
        font-size: 16px;
        line-height: 1.45;
    }

    .checkout-order-product span {
        color: #8490a2;
        font-size: 13px;
    }

    .checkout-order-subtotal {
        flex: 0 0 auto;
        color: #35425a;
        font-size: 14px;
        white-space: nowrap;
    }

    .checkout-total-line {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding-top: 11px;
        color: #59677f;
        font-size: 15px;
    }

    .checkout-total-line strong {
        color: #35425a;
        white-space: nowrap;
    }

    .checkout-grand-total {
        padding-top: 7px;
        color: #27364d;
        font-weight: 700;
    }

    .checkout-grand-total strong {
        font-size: 18px;
    }

    .checkout-submit-button {
        width: 100%;
        min-height: 50px;
        margin-top: 14px;
        border: 0;
        border-radius: 9px;
        background: #287ee5;
        font-size: 16px;
        font-weight: 700;
    }

    .checkout-submit-button:hover {
        background: #176bd0;
    }

    .checkout-payment-note {
        margin: 8px 0 0;
        color: #8490a2;
        font-size: 13px;
    }

    .checkout-payment-note i {
        margin-right: 4px;
    }

    @media (max-width: 767.98px) {
        .checkout-container {
            padding-top: 18px;
            padding-bottom: 36px;
        }

        .checkout-layout {
            grid-template-columns: minmax(0, 1fr);
        }

        .checkout-order-column {
            position: static;
        }

        .checkout-back-link {
            font-size: 21px;
        }
    }

    @media (max-width: 575.98px) {
        .checkout-card {
            padding: 15px;
        }

        .checkout-customer-grid,
        .checkout-form-grid {
            column-gap: 12px;
        }

        .checkout-order-heading {
            align-items: flex-start;
            flex-direction: column;
            gap: 0;
        }
    }
</style>

<?php
require_once '/var/www/src/includes/frontend/footer.php';
?>
