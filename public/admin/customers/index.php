<?php

$pageTitle = 'Quản lý khách hàng';

require_once '/var/www/src/config/database.php';

$sql = "
    SELECT
        CustomerID,
        CustomerName,
        ContactName,
        Address,
        City,
        PostalCode,
        Country
    FROM customers
    ORDER BY CustomerID DESC
";

$result = $conn->query($sql);

if (!$result) {
    die('Lỗi truy vấn khách hàng: ' . $conn->error);
}

$customers = [];
$countries = [];
$contactCount = 0;

while ($customer = $result->fetch_assoc()) {
    $customers[] = $customer;

    if (!empty($customer['Country'])) {
        $countries[$customer['Country']] = $customer['Country'];
    }

    if (!empty($customer['ContactName'])) {
        $contactCount++;
    }
}

$result->free();
asort($countries, SORT_NATURAL | SORT_FLAG_CASE);

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';

?>

<style>
    .customers-page {
        --customers-ink: #172033;
        --customers-muted: #707b8e;
        --customers-border: #e8edf4;
        max-width: 1600px;
        color: var(--customers-ink);
    }

    .customers-page .page-intro {
        padding: 28px;
        border: 1px solid var(--customers-border);
        border-radius: 18px;
        background: linear-gradient(120deg, #f3f7ff 0%, #fff 76%);
    }

    .customers-page .page-intro h1 {
        font-size: clamp(1.45rem, 2.5vw, 1.9rem);
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .customers-page .page-intro p,
    .customers-page .stat-label,
    .customers-page .customer-meta {
        color: var(--customers-muted);
    }

    .customers-page .stat-card,
    .customers-page .customers-card {
        border: 1px solid var(--customers-border);
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(24, 39, 75, 0.035);
    }

    .customers-page .stat-card {
        height: 100%;
    }

    .customers-page .stat-card .card-body {
        padding: 20px;
    }

    .customers-page .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .customers-page .stat-mark {
        display: grid;
        width: 42px;
        height: 42px;
        place-items: center;
        border-radius: 13px;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .customers-page .customers-card {
        overflow: hidden;
    }

    .customers-page .customers-toolbar {
        padding: 20px;
        border-bottom: 1px solid var(--customers-border);
    }

    .customers-page .form-control,
    .customers-page .form-select {
        min-height: 44px;
        width: 100%;
        border-color: #dfe5ee;
        border-radius: 10px;
    }

    .customers-page .form-control:focus,
    .customers-page .form-select:focus {
        border-color: #86a9f5;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }

    .customers-page .filter-controls {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) minmax(170px, 220px);
        gap: 8px;
        width: min(100%, 520px);
    }

    .customers-page .table {
        margin-bottom: 0;
    }

    .customers-page .table thead th {
        padding: 14px 18px;
        color: #647086;
        background: #f8fafd;
        border-bottom: 1px solid var(--customers-border);
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.045em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .customers-page .table tbody td {
        padding: 16px 18px;
        border-color: #eef1f6;
        vertical-align: middle;
    }

    .customers-page .table tbody tr:last-child td {
        border-bottom: 0;
    }

    .customers-page .customer-id {
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

    .customers-page .customer-name {
        font-weight: 600;
    }

    .customers-page .customer-location {
        min-width: 160px;
    }

    .customers-page .action-button {
        border-radius: 9px;
        font-weight: 600;
    }

    .customers-page .empty-state {
        padding: 48px 20px !important;
        color: var(--customers-muted);
        text-align: center;
    }

    @media (max-width: 767.98px) {
        .customers-page .filter-controls {
            grid-template-columns: minmax(0, 1fr);
            width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .customers-page .page-intro,
        .customers-page .customers-toolbar {
            padding: 18px;
        }

        .customers-page .table thead th,
        .customers-page .table tbody td {
            padding-right: 14px;
            padding-left: 14px;
        }
    }
</style>

<main class="container-fluid customers-page px-3 px-lg-4 py-4">
    <section class="page-intro mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="mb-2">Quản lý khách hàng</h1>
            </div>
            <a href="/admin/customers/create.php" class="btn btn-primary px-3 py-2 fw-semibold">
                + Thêm khách hàng
            </a>
        </div>
    </section>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Thao tác khách hàng thành công!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <section class="row g-3 mb-4" aria-label="Thống kê khách hàng">
        <div class="col-6 col-xl-4">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Tổng khách hàng</div>
                        <div class="stat-value"><?= count($customers) ?></div>
                    </div>
                    <span class="stat-mark text-primary bg-primary-subtle" aria-hidden="true">♙</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-4">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Có người liên hệ</div>
                        <div class="stat-value"><?= $contactCount ?></div>
                    </div>
                    <span class="stat-mark text-success bg-success-subtle" aria-hidden="true">✓</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-4">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Khu vực/quốc gia</div>
                        <div class="stat-value"><?= count($countries) ?></div>
                    </div>
                    <span class="stat-mark text-info-emphasis bg-info-subtle" aria-hidden="true">◎</span>
                </div>
            </div>
        </div>
    </section>

    <section class="card customers-card">
        <div class="customers-toolbar">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Danh sách khách hàng</h2>
                    <p class="small text-secondary mb-0">
                        Hiển thị <span id="visible-customer-count"><?= count($customers) ?></span>
                        / <?= count($customers) ?> khách hàng
                    </p>
                </div>
                <div class="filter-controls">
                    <label class="visually-hidden" for="customer-search">Tìm khách hàng</label>
                    <input
                        type="search"
                        class="form-control"
                        id="customer-search"
                        placeholder="Tìm tên, người liên hệ, địa chỉ..."
                        autocomplete="off"
                    >
                    <label class="visually-hidden" for="customer-country-filter">Lọc theo quốc gia</label>
                    <select class="form-select" id="customer-country-filter">
                        <option value="">Tất cả quốc gia</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>
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
                        <th scope="col">Mã KH</th>
                        <th scope="col">Khách hàng</th>
                        <th scope="col">Người liên hệ</th>
                        <th scope="col">Địa chỉ</th>
                        <th scope="col">Khu vực</th>
                        <th scope="col" class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="customers-table-body">
                    <?php foreach ($customers as $customer): ?>
                        <?php
                        $customerName = $customer['CustomerName'] ?? '';
                        $contactName = $customer['ContactName'] ?? '';
                        $address = $customer['Address'] ?? '';
                        $city = $customer['City'] ?? '';
                        $postalCode = $customer['PostalCode'] ?? '';
                        $country = $customer['Country'] ?? '';
                        ?>
                        <tr
                            data-country="<?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>"
                            data-search="<?= htmlspecialchars(
                                '#' . $customer['CustomerID'] . ' ' . $customerName . ' ' . $contactName . ' '
                                    . $address . ' ' . $city . ' ' . $postalCode . ' ' . $country,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <td>
                                <span class="customer-id">#<?= (int) $customer['CustomerID'] ?></span>
                            </td>
                            <td>
                                <div class="customer-name">
                                    <?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </td>
                            <td>
                                <?= $contactName !== ''
                                    ? htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8')
                                    : '<span class="text-secondary">Chưa cập nhật</span>' ?>
                            </td>
                            <td>
                                <?php if ($address !== ''): ?>
                                    <div><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php else: ?>
                                    <span class="text-secondary">Chưa cập nhật</span>
                                <?php endif; ?>
                            </td>
                            <td class="customer-location">
                                <?php if ($city !== '' || $country !== ''): ?>
                                    <div class="fw-medium">
                                        <?= htmlspecialchars(implode(', ', array_filter([$city, $country])), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if ($postalCode !== ''): ?>
                                        <div class="customer-meta small">
                                            Mã bưu điện: <?= htmlspecialchars($postalCode, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-secondary">Chưa cập nhật</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a
                                    href="/admin/customers/edit.php?id=<?= (int) $customer['CustomerID'] ?>"
                                    class="btn btn-sm btn-outline-primary action-button"
                                    title="Sửa khách hàng"
                                >
                                    Sửa
                                </a>
                                <form
                                    action="/admin/customers/delete.php"
                                    method="post"
                                    class="d-inline"
                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng này không?');"
                                >
                                    <input
                                        type="hidden"
                                        name="CustomerID"
                                        value="<?= (int) $customer['CustomerID'] ?>"
                                    >
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger action-button"
                                        title="Xóa khách hàng"
                                    >
                                        Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="customers-empty-state" <?= $customers ? 'hidden' : '' ?>>
                        <td colspan="6" class="empty-state">
                            <?= $customers
                                ? 'Không tìm thấy khách hàng phù hợp với bộ lọc.'
                                : 'Chưa có khách hàng nào.' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    (() => {
        const searchInput = document.getElementById('customer-search');
        const countryFilter = document.getElementById('customer-country-filter');
        const rows = Array.from(document.querySelectorAll('#customers-table-body tr[data-search]'));
        const countLabel = document.getElementById('visible-customer-count');
        const emptyState = document.getElementById('customers-empty-state');

        const filterCustomers = () => {
            const searchTerm = searchInput.value.trim().toLocaleLowerCase('vi');
            let visibleCount = 0;

            rows.forEach((row) => {
                const matchesSearch = row.dataset.search.toLocaleLowerCase('vi').includes(searchTerm);
                const matchesCountry = !countryFilter.value || row.dataset.country === countryFilter.value;
                const isVisible = matchesSearch && matchesCountry;

                row.hidden = !isVisible;
                visibleCount += isVisible ? 1 : 0;
            });

            countLabel.textContent = visibleCount;
            emptyState.hidden = visibleCount > 0;
        };

        searchInput.addEventListener('input', filterCustomers);
        countryFilter.addEventListener('change', filterCustomers);
    })();
</script>

<?php

require_once '/var/www/src/includes/admin/footer.php';

$conn->close();
