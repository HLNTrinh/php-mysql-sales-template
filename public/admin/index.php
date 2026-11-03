<?php
$pageTitle = 'Thống kê';

require_once '/var/www/src/config/database.php';

$summaryResult = $conn->query("
    SELECT
        (
            SELECT COALESCE(SUM(TotalAmount), 0)
            FROM orders
            WHERE Status <> 'Cancelled'
        ) AS Revenue,
        (SELECT COUNT(*) FROM orders) AS TotalOrders,
        (SELECT COUNT(*) FROM customers) AS TotalCustomers,
        (
            SELECT COUNT(*)
            FROM products
            WHERE IsActive = TRUE
        ) AS ActiveProducts
");

if (!$summaryResult) {
    die('Lỗi truy vấn thống kê: ' . $conn->error);
}

$summary = $summaryResult->fetch_assoc();
$summaryResult->free();

$startMonth = new DateTimeImmutable('first day of this month');
$startMonth = $startMonth->modify('-5 months');
$startDate = $startMonth->format('Y-m-d');
$monthlySales = [];

for ($monthOffset = 0; $monthOffset < 6; $monthOffset++) {
    $month = $startMonth->modify('+' . $monthOffset . ' months');
    $monthlySales[$month->format('Y-m')] = [
        'label' => 'Tháng ' . $month->format('m/Y'),
        'revenue' => 0.0
    ];
}

$monthlyStatement = $conn->prepare("
    SELECT
        DATE_FORMAT(OrderDate, '%Y-%m') AS SalesMonth,
        SUM(TotalAmount) AS Revenue
    FROM orders
    WHERE Status <> 'Cancelled'
        AND OrderDate >= ?
    GROUP BY DATE_FORMAT(OrderDate, '%Y-%m')
    ORDER BY SalesMonth
");

if (!$monthlyStatement) {
    die('Lỗi chuẩn bị thống kê doanh thu: ' . $conn->error);
}

$monthlyStatement->bind_param('s', $startDate);
$monthlyStatement->execute();
$monthlyResult = $monthlyStatement->get_result();

while ($month = $monthlyResult->fetch_assoc()) {
    if (isset($monthlySales[$month['SalesMonth']])) {
        $monthlySales[$month['SalesMonth']]['revenue'] =
            (float) $month['Revenue'];
    }
}

$monthlyResult->free();
$monthlyStatement->close();

$statusResult = $conn->query("
    SELECT Status, COUNT(*) AS OrderCount
    FROM orders
    GROUP BY Status
    ORDER BY OrderCount DESC
");

if (!$statusResult) {
    die('Lỗi truy vấn trạng thái đơn hàng: ' . $conn->error);
}

$statusLabels = [
    'Pending' => 'Chờ xác nhận',
    'Confirmed' => 'Đã xác nhận',
    'Shipping' => 'Đang giao',
    'Completed' => 'Hoàn thành',
    'Cancelled' => 'Đã hủy'
];

$statusClasses = [
    'Pending' => 'bg-warning text-dark',
    'Confirmed' => 'bg-primary',
    'Shipping' => 'bg-info text-dark',
    'Completed' => 'bg-success',
    'Cancelled' => 'bg-secondary'
];

$orderStatuses = [];

while ($status = $statusResult->fetch_assoc()) {
    $orderStatuses[] = $status;
}

$statusResult->free();

$recentOrdersResult = $conn->query("
    SELECT
        o.OrderID,
        o.OrderDate,
        o.TotalAmount,
        o.Status,
        c.CustomerName
    FROM orders AS o
    INNER JOIN customers AS c
        ON o.CustomerID = c.CustomerID
    ORDER BY o.OrderDate DESC, o.OrderID DESC
    LIMIT 5
");

if (!$recentOrdersResult) {
    die('Lỗi truy vấn đơn hàng gần đây: ' . $conn->error);
}

$maxMonthlyRevenue = max(
    array_column($monthlySales, 'revenue')
);

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';
?>

<main class="container py-4">
    <section class="row g-3 mb-4" aria-label="Chỉ số tổng quan">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card dashboard-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-2">Doanh thu (đơn không hủy)</p>
                    <p class="h3 mb-0 text-primary">
                        <?= number_format((float) $summary['Revenue'], 0, ',', '.') ?> đ
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card dashboard-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-2">Tổng đơn hàng</p>
                    <p class="h3 mb-0">
                        <?= number_format((int) $summary['TotalOrders'], 0, ',', '.') ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card dashboard-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-2">Khách hàng</p>
                    <p class="h3 mb-0">
                        <?= number_format((int) $summary['TotalCustomers'], 0, ',', '.') ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card dashboard-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-2">Sản phẩm đang bán</p>
                    <p class="h3 mb-0">
                        <?= number_format((int) $summary['ActiveProducts'], 0, ',', '.') ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-1">Doanh thu 6 tháng gần nhất</h2>
                    <p class="text-muted small mb-4">
                        Không tính các đơn hàng đã hủy.
                    </p>

                    <?php foreach ($monthlySales as $month): ?>
                        <?php
                        $barWidth = $maxMonthlyRevenue > 0
                            ? (int) round(
                                $month['revenue'] / $maxMonthlyRevenue * 100
                            )
                            : 0;
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between gap-3 small mb-1">
                                <span><?= htmlspecialchars($month['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="fw-semibold text-nowrap">
                                    <?= number_format($month['revenue'], 0, ',', '.') ?> đ
                                </span>
                            </div>
                            <div
                                class="progress"
                                role="progressbar"
                                aria-label="<?= htmlspecialchars($month['label'], ENT_QUOTES, 'UTF-8') ?>"
                                aria-valuenow="<?= $barWidth ?>"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            >
                                <div
                                    class="progress-bar"
                                    style="width: <?= $barWidth ?>%"
                                ></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Trạng thái đơn hàng</h2>

                    <?php if ($orderStatuses): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($orderStatuses as $status): ?>
                                <?php
                                $statusCode = $status['Status'];
                                $statusLabel = $statusLabels[$statusCode]
                                    ?? $statusCode;
                                $badgeClass = $statusClasses[$statusCode]
                                    ?? 'bg-secondary';
                                ?>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <span>
                                        <span class="badge <?= $badgeClass ?> me-2">
                                            <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </span>
                                    <span class="fw-semibold">
                                        <?= number_format((int) $status['OrderCount'], 0, ',', '.') ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">Chưa có đơn hàng.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">Đơn hàng gần đây</h2>
                <a href="/admin/orders/" class="btn btn-sm btn-outline-primary">
                    Xem tất cả
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mã đơn</th>
                            <th>Ngày đặt</th>
                            <th>Khách hàng</th>
                            <th class="text-end">Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentOrdersResult->num_rows > 0): ?>
                            <?php while ($order = $recentOrdersResult->fetch_assoc()): ?>
                                <?php
                                $statusCode = $order['Status'];
                                $statusLabel = $statusLabels[$statusCode]
                                    ?? $statusCode;
                                $badgeClass = $statusClasses[$statusCode]
                                    ?? 'bg-secondary';
                                ?>
                                <tr>
                                    <td>#<?= (int) $order['OrderID'] ?></td>
                                    <td><?= htmlspecialchars($order['OrderDate'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($order['CustomerName'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end text-nowrap">
                                        <?= number_format((float) $order['TotalAmount'], 0, ',', '.') ?> đ
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a
                                            href="/admin/orders/detail.php?id=<?= (int) $order['OrderID'] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Chi tiết
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Chưa có đơn hàng nào.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<style>
    .dashboard-card {
        border-radius: 0.85rem;
    }

    .progress {
        height: 0.55rem;
    }
</style>

<?php
$recentOrdersResult->free();
$conn->close();

require_once '/var/www/src/includes/admin/footer.php';