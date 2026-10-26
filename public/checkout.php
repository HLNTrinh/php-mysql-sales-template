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

<div class="container py-4">

    <h1 class="h3 mb-4">Đặt hàng</h1>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars(
                $errorMessage,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">

                    <h2 class="h5 mb-3">
                        Thông tin khách hàng
                    </h2>

                    <form method="post">

                        <div class="mb-3">
                            <label class="form-label">
                                Họ và tên
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $customerName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                readonly
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $email,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                readonly
                            >
                        </div>

                        <div class="mb-3">
                            <label
                                for="phone"
                                class="form-label"
                            >
                                Số điện thoại
                            </label>

                            <input
                                type="tel"
                                class="form-control"
                                id="phone"
                                name="phone"
                                value="<?= htmlspecialchars(
                                    $phone,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label
                                for="address"
                                class="form-label"
                            >
                                Địa chỉ giao hàng
                            </label>

                            <textarea
                                class="form-control"
                                id="address"
                                name="address"
                                rows="3"
                                required
                            ><?= htmlspecialchars(
                                $address,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>
                        </div>

                        <button
                            type="submit"
                            name="place_order"
                            value="1"
                            class="btn btn-success"
                        >
                            Xác nhận đặt hàng
                        </button>

                    </form>

                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">

                    <h2 class="h5 mb-3">
                        Đơn hàng của bạn
                    </h2>

                    <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex justify-content-between border-bottom py-2">

                            <div>
                                <strong>
                                    <?= htmlspecialchars(
                                        $item['ProductName'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <div class="small text-muted">
                                    <?= (int) $item['Quantity'] ?>
                                    ×
                                    <?= number_format(
                                        (float) $item['Price'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?> đ
                                </div>
                            </div>

                            <div>
                                <?= number_format(
                                    (float) $item['Subtotal'],
                                    0,
                                    ',',
                                    '.'
                                ) ?> đ
                            </div>

                        </div>
                    <?php endforeach; ?>

                    <div class="d-flex justify-content-between fw-bold fs-5 pt-3">
                        <span>Tổng cộng</span>

                        <span>
                            <?= number_format(
                                (float) $total,
                                0,
                                ',',
                                '.'
                            ) ?> đ
                        </span>
                    </div>

                    <a
                        href="/cart.php"
                        class="btn btn-outline-secondary mt-3"
                    >
                        Quay lại giỏ hàng
                    </a>

                </div>
            </div>
        </div>

    </div>
</div>

<?php
require_once '/var/www/src/includes/frontend/footer.php';
?>
