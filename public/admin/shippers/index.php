<?php

$pageTitle = 'Quản lý đơn vị vận chuyển';

require_once '/var/www/src/config/database.php';

$sql = "
    SELECT
        ShipperID,
        ShipperName,
        Phone
    FROM shippers
    ORDER BY ShipperName, ShipperID
";

$result = $conn->query($sql);

if (!$result) {
    die('Lỗi truy vấn đơn vị vận chuyển: ' . $conn->error);
}

$shippers = [];
$withPhoneCount = 0;

while ($shipper = $result->fetch_assoc()) {
    $shippers[] = $shipper;

    if (!empty($shipper['Phone'])) {
        $withPhoneCount++;
    }
}

$result->free();

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';

?>

<style>
    .shippers-page {
        --shippers-ink: #172033;
        --shippers-muted: #707b8e;
        --shippers-border: #e8edf4;
        max-width: 1400px;
        color: var(--shippers-ink);
    }

    .shippers-page .page-intro {
        padding: 28px;
        border: 1px solid var(--shippers-border);
        border-radius: 18px;
        background: linear-gradient(120deg, #f3f7ff 0%, #fff 76%);
    }

    .shippers-page .page-intro h1 {
        font-size: clamp(1.45rem, 2.5vw, 1.9rem);
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .shippers-page .page-intro p,
    .shippers-page .stat-label,
    .shippers-page .shipper-meta {
        color: var(--shippers-muted);
    }

    .shippers-page .stat-card,
    .shippers-page .shippers-card {
        border: 1px solid var(--shippers-border);
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(24, 39, 75, 0.035);
    }

    .shippers-page .stat-card {
        height: 100%;
    }

    .shippers-page .stat-card .card-body {
        padding: 20px;
    }

    .shippers-page .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .shippers-page .stat-mark {
        display: grid;
        width: 42px;
        height: 42px;
        place-items: center;
        border-radius: 13px;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .shippers-page .shippers-card {
        overflow: hidden;
    }

    .shippers-page .shippers-toolbar {
        padding: 20px;
        border-bottom: 1px solid var(--shippers-border);
    }

    .shippers-page .form-control {
        min-height: 44px;
        min-width: min(100%, 300px);
        border-color: #dfe5ee;
        border-radius: 10px;
    }

    .shippers-page .form-control:focus {
        border-color: #86a9f5;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }

    .shippers-page .table {
        margin-bottom: 0;
    }

    .shippers-page .table thead th {
        padding: 14px 18px;
        color: #647086;
        background: #f8fafd;
        border-bottom: 1px solid var(--shippers-border);
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.045em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .shippers-page .table tbody td {
        padding: 16px 18px;
        border-color: #eef1f6;
        vertical-align: middle;
    }

    .shippers-page .table tbody tr:last-child td {
        border-bottom: 0;
    }

    .shippers-page .shipper-id {
        display: inline-flex;
        min-width: 42px;
        height: 34px;
        align-items: center;
        justify-content: center;
        padding: 0 9px;
        border-radius: 10px;
        color: #2563eb;
        background: #edf3ff;
        font-weight: 700;
    }

    .shippers-page .shipper-name {
        font-weight: 600;
    }

    .shippers-page .phone-link {
        color: inherit;
        text-decoration: none;
    }

    .shippers-page .phone-link:hover {
        color: #0d6efd;
    }

    .shippers-page .action-button {
        border-radius: 9px;
        font-weight: 600;
    }

    .shippers-page .empty-state {
        padding: 48px 20px !important;
        color: var(--shippers-muted);
        text-align: center;
    }

    @media (max-width: 575.98px) {
        .shippers-page .page-intro,
        .shippers-page .shippers-toolbar {
            padding: 18px;
        }

        .shippers-page .table thead th,
        .shippers-page .table tbody td {
            padding-right: 14px;
            padding-left: 14px;
        }
    }
</style>

<main class="container-fluid shippers-page px-3 px-lg-4 py-4">
    <section class="page-intro mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="mb-2">Đơn vị vận chuyển</h1>
            </div>
            <a href="/admin/shippers/create.php" class="btn btn-primary px-3 py-2 fw-semibold">
                + Thêm đơn vị vận chuyển
            </a>
        </div>
    </section>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Đã thêm đơn vị vận chuyển thành công.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <section class="row g-3 mb-4" aria-label="Thống kê đơn vị vận chuyển">
        <div class="col-6 col-xl-4">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Tổng đơn vị vận chuyển</div>
                        <div class="stat-value"><?= count($shippers) ?></div>
                    </div>
                    <span class="stat-mark text-primary bg-primary-subtle" aria-hidden="true">▣</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-4">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Có số điện thoại</div>
                        <div class="stat-value"><?= $withPhoneCount ?></div>
                    </div>
                    <span class="stat-mark text-success bg-success-subtle" aria-hidden="true">☎</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-4">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Chưa có số điện thoại</div>
                        <div class="stat-value"><?= count($shippers) - $withPhoneCount ?></div>
                    </div>
                    <span class="stat-mark text-secondary bg-secondary-subtle" aria-hidden="true">—</span>
                </div>
            </div>
        </div>
    </section>

    <section class="card shippers-card">
        <div class="shippers-toolbar">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Danh sách đối tác</h2>
                    <p class="small text-secondary mb-0">
                        Hiển thị <span id="visible-shipper-count"><?= count($shippers) ?></span>
                        / <?= count($shippers) ?> đơn vị
                    </p>
                </div>
                <div>
                    <label class="visually-hidden" for="shipper-search">Tìm đơn vị vận chuyển</label>
                    <input
                        type="search"
                        class="form-control"
                        id="shipper-search"
                        placeholder="Tìm tên đơn vị hoặc số điện thoại..."
                        autocomplete="off"
                    >
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">Mã đơn vị</th>
                        <th scope="col">Đơn vị vận chuyển</th>
                        <th scope="col">Số điện thoại</th>
                        <th scope="col" class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="shippers-table-body">
                    <?php foreach ($shippers as $shipper): ?>
                        <?php
                        $shipperName = $shipper['ShipperName'] ?? '';
                        $phone = $shipper['Phone'] ?? '';
                        ?>
                        <tr
                            data-search="<?= htmlspecialchars(
                                '#' . $shipper['ShipperID'] . ' ' . $shipperName . ' ' . $phone,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <td>
                                <span class="shipper-id">#<?= (int) $shipper['ShipperID'] ?></span>
                            </td>
                            <td>
                                <div class="shipper-name">
                                    <?= htmlspecialchars($shipperName, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="shipper-meta small">Đối tác giao hàng</div>
                            </td>
                            <td>
                                <?php if ($phone !== ''): ?>
                                    <a class="phone-link" href="tel:<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary">Chưa cập nhật</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a
                                    href="/admin/shippers/edit.php?id=<?= (int) $shipper['ShipperID'] ?>"
                                    class="btn btn-sm btn-outline-primary action-button"
                                >
                                    Sửa
                                </a>
                                <form
                                    action="/admin/shippers/delete.php"
                                    method="post"
                                    class="d-inline"
                                    onsubmit="return confirm('Bạn có chắc muốn xóa đơn vị vận chuyển này?');"
                                >
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $shipper['ShipperID'] ?>"
                                    >
                                    <button type="submit" class="btn btn-sm btn-outline-danger action-button">
                                        Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="shippers-empty-state" <?= $shippers ? 'hidden' : '' ?>>
                        <td colspan="4" class="empty-state">
                            <?= $shippers
                                ? 'Không tìm thấy đơn vị vận chuyển phù hợp.'
                                : 'Chưa có đơn vị vận chuyển nào.' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    (() => {
        const searchInput = document.getElementById('shipper-search');
        const rows = Array.from(document.querySelectorAll('#shippers-table-body tr[data-search]'));
        const countLabel = document.getElementById('visible-shipper-count');
        const emptyState = document.getElementById('shippers-empty-state');

        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.trim().toLocaleLowerCase('vi');
            let visibleCount = 0;

            rows.forEach((row) => {
                const isVisible = row.dataset.search.toLocaleLowerCase('vi').includes(searchTerm);
                row.hidden = !isVisible;
                visibleCount += isVisible ? 1 : 0;
            });

            countLabel.textContent = visibleCount;
            emptyState.hidden = visibleCount > 0;
        });
    })();
</script>

<?php

require_once '/var/www/src/includes/admin/footer.php';

$conn->close();
