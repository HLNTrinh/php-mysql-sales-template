<?php

$pageTitle = 'Quản lý đơn hàng';

require_once '/var/www/src/config/database.php';

$sql = "
    SELECT
        o.OrderID,
        o.OrderDate,
        o.TotalAmount,
        o.Status,
        c.CustomerName,
        c.Phone
    FROM
        orders AS o
    INNER JOIN customers AS c
        ON o.CustomerID = c.CustomerID
    ORDER BY
        o.OrderID DESC
";

$result = $conn->query($sql);

if (!$result) {
    die('Lỗi truy vấn đơn hàng: ' . $conn->error);
}

$orders = [];
$statusCounts = [
    'Pending' => 0,
    'Shipping' => 0,
    'Completed' => 0,
    'Cancelled' => 0,
];

while ($order = $result->fetch_assoc()) {
    $orders[] = $order;

    if (isset($statusCounts[$order['Status']])) {
        $statusCounts[$order['Status']]++;
    }
}

$result->free();

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';

$statusLabels = [
    'Pending' => 'Chờ xác nhận',
    'Confirmed' => 'Đã xác nhận',
    'Shipping' => 'Đang giao',
    'Completed' => 'Hoàn thành',
    'Cancelled' => 'Đã hủy',
];

$statusClasses = [
    'Pending' => 'status-pending',
    'Confirmed' => 'status-confirmed',
    'Shipping' => 'status-shipping',
    'Completed' => 'status-completed',
    'Cancelled' => 'status-cancelled',
];

?>

<style>
    .orders-page {
        --orders-ink: #172033;
        --orders-muted: #707b8e;
        --orders-border: #e8edf4;
        max-width: 1500px;
        color: var(--orders-ink);
    }

    .orders-page .page-intro {
        background: linear-gradient(120deg, #f3f7ff 0%, #fff 76%);
        border: 1px solid var(--orders-border);
        border-radius: 18px;
        padding: 28px;
    }

    .orders-page .page-intro h1 {
        font-size: clamp(1.45rem, 2.5vw, 1.9rem);
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .orders-page .page-intro p,
    .orders-page .stat-label,
    .orders-page .order-meta {
        color: var(--orders-muted);
    }

    .orders-page .stat-card {
        height: 100%;
        border: 1px solid var(--orders-border);
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(24, 39, 75, 0.035);
    }

    .orders-page .stat-card .card-body {
        padding: 20px;
    }

    .orders-page .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .orders-page .stat-mark {
        display: grid;
        width: 42px;
        height: 42px;
        place-items: center;
        border-radius: 13px;
        font-size: 1.2rem;
        font-weight: 700;
    }

    .orders-page .orders-card {
        overflow: hidden;
        border: 1px solid var(--orders-border);
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(24, 39, 75, 0.035);
    }

    .orders-page .orders-toolbar {
        padding: 20px;
        border-bottom: 1px solid var(--orders-border);
    }

    .orders-page .form-control,
    .orders-page .form-select {
        min-height: 44px;
        border-color: #dfe5ee;
        border-radius: 10px;
    }

    .orders-page .form-control:focus,
    .orders-page .form-select:focus {
        border-color: #86a9f5;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }

    .orders-page .table {
        margin-bottom: 0;
    }

    .orders-page .table thead th {
        padding: 14px 18px;
        color: #647086;
        background: #f8fafd;
        border-bottom: 1px solid var(--orders-border);
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.045em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .orders-page .table tbody td {
        padding: 16px 18px;
        border-color: #eef1f6;
        vertical-align: middle;
    }

    .orders-page .table tbody tr:last-child td {
        border-bottom: 0;
    }

    .orders-page .order-id {
        color: #2563eb;
        font-weight: 700;
        white-space: nowrap;
    }

    .orders-page .customer-name {
        font-weight: 600;
    }

    .orders-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .orders-page .status-pill::before {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
        content: "";
    }

    .orders-page .status-pending {
        color: #996300;
        background: #fff4d6;
    }

    .orders-page .status-confirmed {
        color: #235ac5;
        background: #eaf1ff;
    }

    .orders-page .status-shipping {
        color: #087990;
        background: #e2f7fb;
    }

    .orders-page .status-completed {
        color: #198754;
        background: #e5f6ec;
    }

    .orders-page .status-cancelled,
    .orders-page .status-unknown {
        color: #667085;
        background: #eef0f3;
    }

    .orders-page .view-order {
        border-radius: 9px;
        font-weight: 600;
        white-space: nowrap;
    }

    .orders-page .empty-state {
        padding: 48px 20px !important;
        color: var(--orders-muted);
        text-align: center;
    }

    @media (max-width: 575.98px) {
        .orders-page .page-intro,
        .orders-page .orders-toolbar {
            padding: 18px;
        }

        .orders-page .table thead th,
        .orders-page .table tbody td {
            padding-right: 14px;
            padding-left: 14px;
        }
    }
</style>

<main class="container-fluid orders-page px-3 px-lg-4 py-4">
    <section class="page-intro mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="mb-2">Quản lý đơn hàng</h1>
            </div>
            <div class="text-md-end">
                <div class="small text-secondary mb-1">Tổng số đơn hàng</div>
                <div class="fs-4 fw-bold"><?= count($orders) ?></div>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4" aria-label="Thống kê đơn hàng">
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Tất cả đơn hàng</div>
                        <div class="stat-value"><?= count($orders) ?></div>
                    </div>
                    <span class="stat-mark text-primary bg-primary-subtle" aria-hidden="true">#</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Chờ xác nhận</div>
                        <div class="stat-value"><?= $statusCounts['Pending'] ?></div>
                    </div>
                    <span class="stat-mark text-warning-emphasis bg-warning-subtle" aria-hidden="true">…</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Đang giao</div>
                        <div class="stat-value"><?= $statusCounts['Shipping'] ?></div>
                    </div>
                    <span class="stat-mark text-info-emphasis bg-info-subtle" aria-hidden="true">→</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Hoàn thành</div>
                        <div class="stat-value"><?= $statusCounts['Completed'] ?></div>
                    </div>
                    <span class="stat-mark text-success bg-success-subtle" aria-hidden="true">✓</span>
                </div>
            </div>
        </div>
    </section>

    <section class="card orders-card">
        <div class="orders-toolbar">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Danh sách đơn hàng</h2>
                    <p class="small text-secondary mb-0">
                        Hiển thị <span id="visible-order-count"><?= count($orders) ?></span>
                        / <?= count($orders) ?> đơn hàng
                    </p>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <label class="visually-hidden" for="order-search">Tìm đơn hàng</label>
                    <input
                        type="search"
                        class="form-control"
                        id="order-search"
                        placeholder="Tìm mã đơn, khách hàng, SĐT..."
                        autocomplete="off"
                    >
                    <label class="visually-hidden" for="order-status-filter">Lọc trạng thái</label>
                    <select class="form-select" id="order-status-filter" aria-label="Lọc theo trạng thái">
                        <option value="">Tất cả trạng thái</option>
                        <?php foreach ($statusLabels as $status => $label): ?>
                            <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">Mã đơn</th>
                        <th scope="col">Khách hàng</th>
                        <th scope="col">Điện thoại</th>
                        <th scope="col">Ngày đặt</th>
                        <th scope="col" class="text-end">Tổng tiền</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col" class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="orders-table-body">
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $status = $order['Status'] ?? '';
                        $statusClass = $statusClasses[$status] ?? 'status-unknown';
                        $statusLabel = $statusLabels[$status] ?? $status;
                        $customerName = $order['CustomerName'] ?? '';
                        $phone = $order['Phone'] ?? '';
                        ?>
                        <tr
                            data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                            data-search="<?= htmlspecialchars(
                                '#' . $order['OrderID'] . ' ' . $customerName . ' ' . $phone,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <td>
                                <span class="order-id">#<?= (int) $order['OrderID'] ?></span>
                            </td>
                            <td>
                                <div class="customer-name">
                                    <?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="order-meta small">
                                    <?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?: 'Chưa có SĐT' ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?: '—' ?></td>
                            <td class="text-nowrap">
                                <?= date('d/m/Y', strtotime($order['OrderDate'])) ?>
                            </td>
                            <td class="text-end text-nowrap fw-semibold">
                                <?= number_format((float) $order['TotalAmount'], 0, ',', '.') ?> đ
                            </td>
                            <td>
                                <span class="status-pill <?= $statusClass ?>">
                                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a
                                    href="/admin/orders/detail.php?id=<?= (int) $order['OrderID'] ?>"
                                    class="btn btn-sm btn-outline-primary view-order"
                                >
                                    Xem chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="orders-empty-state" <?= $orders ? 'hidden' : '' ?>>
                        <td colspan="7" class="empty-state">
                            <?= $orders
                                ? 'Không tìm thấy đơn hàng phù hợp với bộ lọc.'
                                : 'Chưa có đơn hàng nào.' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    (() => {
        const searchInput = document.getElementById('order-search');
        const statusFilter = document.getElementById('order-status-filter');
        const rows = Array.from(document.querySelectorAll('#orders-table-body tr[data-status]'));
        const countLabel = document.getElementById('visible-order-count');
        const emptyState = document.getElementById('orders-empty-state');

        const filterOrders = () => {
            const searchTerm = searchInput.value.trim().toLocaleLowerCase('vi');
            const selectedStatus = statusFilter.value;
            let visibleCount = 0;

            rows.forEach((row) => {
                const matchesSearch = row.dataset.search.toLocaleLowerCase('vi').includes(searchTerm);
                const matchesStatus = !selectedStatus || row.dataset.status === selectedStatus;
                const isVisible = matchesSearch && matchesStatus;

                row.hidden = !isVisible;
                visibleCount += isVisible ? 1 : 0;
            });

            countLabel.textContent = visibleCount;
            emptyState.hidden = visibleCount > 0;
        };

        searchInput.addEventListener('input', filterOrders);
        statusFilter.addEventListener('change', filterOrders);
    })();
</script>

<?php

require_once '/var/www/src/includes/admin/footer.php';

$conn->close();
