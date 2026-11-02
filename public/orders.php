<?php
require_once '/var/www/src/config/session.php';
require_once '/var/www/src/config/database.php';

if (
    !isset($_SESSION['customer_id']) ||
    (int) $_SESSION['customer_id'] <= 0
) {
    header('Location: /login.php');
    exit;
}

$customerID = (int) $_SESSION['customer_id'];
$itemsPerPage = 10;
$page = filter_var(
    $_GET['page'] ?? 1,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$page = $page === false ? 1 : $page;

$stmtCount = $conn->prepare(
    'SELECT COUNT(*) AS TotalOrders
     FROM orders
     WHERE CustomerID = ?'
);
$stmtCount->bind_param('i', $customerID);
$stmtCount->execute();
$countResult = $stmtCount->get_result();
$totalOrders = (int) $countResult->fetch_assoc()['TotalOrders'];
$countResult->free();
$stmtCount->close();

$totalPages = (int) ceil($totalOrders / $itemsPerPage);
if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $itemsPerPage;
$stmtOrders = $conn->prepare(
    'SELECT
        o.OrderID,
        o.OrderDate,
        o.TotalAmount,
        o.Status,
        COALESCE(items.ItemCount, 0) AS ItemCount
     FROM orders o
     LEFT JOIN (
         SELECT OrderID, SUM(Quantity) AS ItemCount
         FROM orderdetail
         GROUP BY OrderID
     ) items ON items.OrderID = o.OrderID
     WHERE o.CustomerID = ?
     ORDER BY o.OrderDate DESC, o.OrderID DESC
     LIMIT ? OFFSET ?'
);
$stmtOrders->bind_param('iii', $customerID, $itemsPerPage, $offset);
$stmtOrders->execute();
$ordersResult = $stmtOrders->get_result();

$statusLabels = [
    'Pending' => 'Chờ xác nhận',
    'Confirmed' => 'Đã xác nhận',
    'Shipping' => 'Đang giao',
    'Completed' => 'Hoàn thành',
    'Cancelled' => 'Đã hủy'
];
$statusClasses = [
    'Pending' => 'text-bg-warning',
    'Confirmed' => 'text-bg-primary',
    'Shipping' => 'text-bg-info',
    'Completed' => 'text-bg-success',
    'Cancelled' => 'text-bg-secondary'
];

$pageTitle = 'Đơn hàng của tôi';
require_once '/var/www/src/includes/frontend/header.php';
require_once '/var/www/src/includes/frontend/navbar.php';
?>

<main class="orders-page">
    <div class="container orders-container">
        <section class="orders-hero">
            <div class="orders-heading-copy">
                <span class="orders-eyebrow">TÀI KHOẢN CỦA BẠN</span>
                <h1>Lịch sử mua hàng</h1>
                <p>Theo dõi trạng thái và xem lại các đơn hàng bạn đã đặt.</p>
            </div>
            <div class="orders-heading-actions">
                <span class="orders-count">
                    <?= $totalOrders ?> đơn hàng
                </span>
                <a href="/products.php" class="orders-continue-link">
                    Tiếp tục mua sắm
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>
        </section>

        <?php if ($ordersResult->num_rows === 0): ?>
            <section class="orders-empty">
                <div class="orders-empty-icon" aria-hidden="true">
                    <i class="bi bi-bag"></i>
                </div>
                <h2>Bạn chưa có đơn hàng nào</h2>
                <p>Đơn hàng bạn đặt sẽ xuất hiện tại đây để tiện theo dõi.</p>
                <a href="/products.php" class="btn orders-primary-button">
                    Khám phá sản phẩm
                    <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                </a>
            </section>
        <?php else: ?>
            <div class="orders-list">
                <?php while ($order = $ordersResult->fetch_assoc()): ?>
                    <?php
                    $status = $order['Status'];
                    $statusLabel = $statusLabels[$status] ?? $status;
                    $statusClass = $statusClasses[$status] ?? 'text-bg-secondary';
                    ?>
                    <article class="order-card">
                        <div class="order-card-top">
                            <div class="order-card-identity">
                                <div>
                                    <span class="order-meta-label">MÃ ĐƠN HÀNG</span>
                                    <h3>#<?= (int) $order['OrderID'] ?></h3>
                                </div>
                            </div>
                            <span class="badge order-status <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <div class="order-card-details">
                            <div class="order-detail">
                                <span class="order-meta-label">Ngày đặt</span>
                                <strong>
                                    <?= htmlspecialchars($order['OrderDate'], ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                            </div>
                            <div class="order-detail">
                                <span class="order-meta-label">Sản phẩm</span>
                                <strong>
                                    <?= (int) $order['ItemCount'] ?> món
                                </strong>
                            </div>
                            <div class="order-detail order-detail-total">
                                <span class="order-meta-label">Tổng thanh toán</span>
                                <strong>
                                    <?= number_format((float) $order['TotalAmount'], 0, ',', '.') ?> đ
                                </strong>
                            </div>
                        </div>

                        <div class="order-card-footer">
                            <span>Chi tiết đơn hàng và sản phẩm</span>
                            <a
                                href="/order-success.php?id=<?= (int) $order['OrderID'] ?>"
                                class="btn order-detail-button"
                            >
                                Xem chi tiết
                                <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-4" aria-label="Phân trang đơn hàng">
                    <ul class="pagination justify-content-center flex-wrap">
                        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                            <li class="page-item <?= $page === $pageNumber ? 'active' : '' ?>">
                                <a
                                    class="page-link"
                                    href="?page=<?= $pageNumber ?>"
                                    <?= $page === $pageNumber ? 'aria-current="page"' : '' ?>
                                >
                                    <?= $pageNumber ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<style>
    .orders-page {
        min-height: 65vh;
        padding: 44px 0 72px;
        background:
            radial-gradient(ellipse at 50% 0, rgb(225 235 251 / 42%), transparent 48%),
            #f5f7fb;
    }

    .orders-container {
        max-width: 1120px;
    }

    .orders-hero {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 26px;
        padding: 0 0 22px;
        border-bottom: 1px solid #e1e7f0;
    }

    .orders-eyebrow,
    .order-meta-label {
        color: #78869d;
        font-size: 11px;
        font-weight: 750;
        letter-spacing: 0.08em;
    }

    .orders-heading-copy h1 {
        margin: 7px 0 5px;
        color: #1d2b45;
        font-size: clamp(27px, 4vw, 35px);
        font-weight: 760;
        letter-spacing: -0.8px;
    }

    .orders-heading-copy p {
        margin: 0;
        color: #718099;
        font-size: 14px;
    }

    .orders-heading-actions {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .orders-continue-link {
        color: #2874d8;
        font-size: 14px;
        font-weight: 650;
        text-decoration: none;
        white-space: nowrap;
    }

    .orders-continue-link:hover {
        color: #155bb7;
    }

    .orders-count {
        padding: 7px 12px;
        border: 1px solid #e0e7f1;
        border-radius: 999px;
        background: rgb(255 255 255 / 75%);
        color: #53647d;
        font-size: 13px;
        font-weight: 650;
        white-space: nowrap;
    }

    .orders-list {
        display: grid;
        gap: 16px;
    }

    .order-card {
        padding: 22px 24px 12px;
        border: 1px solid #e3e9f2;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 7px 22px rgb(34 54 88 / 5%);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .order-card:hover {
        transform: translateY(-2px);
        border-color: #d3e1f4;
        box-shadow: 0 12px 28px rgb(34 72 130 / 9%);
    }

    .order-card-top,
    .order-card-identity,
    .order-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .order-card-identity {
        justify-content: flex-start;
    }

    .order-card-identity h3 {
        margin: 4px 0 0;
        color: #25334b;
        font-size: 19px;
        font-weight: 760;
        letter-spacing: -0.2px;
    }

    .order-status {
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .order-card-details {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 24px;
        margin-top: 18px;
        padding: 18px 0 16px;
        border-top: 1px solid #edf1f6;
    }

    .order-detail {
        display: grid;
        align-content: start;
        gap: 8px;
    }

    .order-detail strong {
        display: block;
        color: #34425a;
        font-size: 15px;
        font-weight: 680;
    }

    .order-detail-total strong {
        color: #1e65c1;
        font-size: 19px;
        font-weight: 760;
        letter-spacing: -0.2px;
    }

    .order-card-footer {
        min-height: 58px;
        padding: 12px 0 0;
        border-top: 1px solid #edf1f6;
        color: #78869d;
    }

    .order-card-footer > span {
        color: #8792a3;
        font-size: 12px;
    }

    .order-detail-button,
    .orders-primary-button {
        border: 0;
        border-radius: 9px;
        background: #287ee5;
        color: #fff;
        font-size: 13px;
        font-weight: 650;
    }

    .order-detail-button {
        padding: 9px 15px;
        box-shadow: 0 4px 10px rgb(40 126 229 / 18%);
        transition: background-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .order-detail-button:hover,
    .orders-primary-button:hover {
        transform: translateY(-1px);
        background: #176bd0;
        color: #fff;
        box-shadow: 0 6px 14px rgb(40 126 229 / 24%);
    }

    .orders-empty {
        display: grid;
        justify-items: center;
        padding: 54px 24px;
        text-align: center;
    }

    .orders-empty-icon {
        display: grid;
        width: 70px;
        height: 70px;
        margin-bottom: 18px;
        place-items: center;
        border-radius: 22px;
        background: #eef5ff;
        color: #287ee5;
        font-size: 30px;
    }

    .orders-empty h2 {
        margin: 0 0 8px;
        color: #26344c;
        font-size: 21px;
        font-weight: 750;
    }

    .orders-empty p {
        margin: 0 0 20px;
        color: #7b8799;
        font-size: 14px;
    }

    .orders-primary-button {
        padding: 10px 17px;
    }

    @media (max-width: 575.98px) {
        .orders-page {
            padding: 22px 0 42px;
        }

        .orders-page {
            padding: 28px 0 48px;
        }

        .orders-hero {
            align-items: flex-start;
            flex-direction: column;
            gap: 14px;
            margin-bottom: 20px;
            padding-bottom: 18px;
        }

        .orders-heading-actions {
            width: 100%;
            justify-content: space-between;
            gap: 10px;
        }

        .orders-continue-link {
            font-size: 13px;
        }

        .order-card {
            padding: 17px 16px 10px;
            border-radius: 14px;
        }

        .order-card-details {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 12px;
            margin-top: 14px;
            padding: 15px 0 13px;
        }

        .order-detail-total {
            grid-column: 1 / -1;
        }

        .order-card-footer {
            min-height: auto;
            gap: 10px;
            padding-top: 12px;
        }

        .order-card-footer > span {
            max-width: 130px;
            font-size: 11px;
        }

        .order-detail-total strong {
            font-size: 18px;
        }
    }
</style>

<?php
$ordersResult->free();
$stmtOrders->close();
require_once '/var/www/src/includes/frontend/footer.php';
?>
