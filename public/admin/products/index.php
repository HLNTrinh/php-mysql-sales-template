<?php

$pageTitle = 'Quản lý sản phẩm';

require_once '/var/www/src/config/database.php';

$sql = "
    SELECT
        p.ProductID,
        p.ProductCode,
        p.ProductName,
        p.Unit,
        p.Price,
        p.StockQuantity,
        p.IsActive,
        c.CategoryID,
        c.CategoryName,
        s.SupplierName,
        pi.ImageFile,
        pi.AltText
    FROM products AS p
    INNER JOIN categories AS c
        ON p.CategoryID = c.CategoryID
    INNER JOIN suppliers AS s
        ON p.SupplierID = s.SupplierID
    LEFT JOIN product_images AS pi
        ON p.ProductID = pi.ProductID
        AND pi.IsPrimary = TRUE
    ORDER BY
        p.ProductID DESC
";

$result = $conn->query($sql);

if (!$result) {
    die('Lỗi truy vấn sản phẩm: ' . $conn->error);
}

$products = [];
$categories = [];
$activeCount = 0;
$lowStockCount = 0;
$outOfStockCount = 0;

while ($product = $result->fetch_assoc()) {
    $products[] = $product;
    $categories[$product['CategoryID']] = $product['CategoryName'];

    if ((int) $product['IsActive'] === 1) {
        $activeCount++;
    }

    if ((int) $product['StockQuantity'] < 5) {
        $lowStockCount++;
    }

    if ((int) $product['StockQuantity'] === 0) {
        $outOfStockCount++;
    }
}

$result->free();
asort($categories, SORT_NATURAL | SORT_FLAG_CASE);

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';

?>

<style>
    .products-page {
        --products-ink: #172033;
        --products-muted: #707b8e;
        --products-border: #e8edf4;
        max-width: 1600px;
        color: var(--products-ink);
    }

    .products-page .page-intro {
        padding: 28px;
        border: 1px solid var(--products-border);
        border-radius: 18px;
        background: linear-gradient(120deg, #f3f7ff 0%, #fff 76%);
    }

    .products-page .page-intro h1 {
        font-size: clamp(1.45rem, 2.5vw, 1.9rem);
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .products-page .page-intro p,
    .products-page .stat-label,
    .products-page .product-code,
    .products-page .product-meta {
        color: var(--products-muted);
    }

    .products-page .stat-card,
    .products-page .products-card {
        border: 1px solid var(--products-border);
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(24, 39, 75, 0.035);
    }

    .products-page .stat-card {
        height: 100%;
    }

    .products-page .stat-card .card-body {
        padding: 20px;
    }

    .products-page .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        letter-spacing: -0.04em;
    }

    .products-page .stat-mark {
        display: grid;
        width: 42px;
        height: 42px;
        place-items: center;
        border-radius: 13px;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .products-page .products-card {
        overflow: hidden;
    }

    .products-page .products-toolbar {
        padding: 20px;
        border-bottom: 1px solid var(--products-border);
    }

    .products-page .form-control,
    .products-page .form-select {
        min-height: 44px;
        border-color: #dfe5ee;
        border-radius: 10px;
    }

    .products-page .form-control:focus,
    .products-page .form-select:focus {
        border-color: #86a9f5;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }

    .products-page .table {
        margin-bottom: 0;
    }

    .products-page .table thead th {
        padding: 14px 16px;
        color: #647086;
        background: #f8fafd;
        border-bottom: 1px solid var(--products-border);
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.045em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .products-page .table tbody td {
        padding: 14px 16px;
        border-color: #eef1f6;
        vertical-align: middle;
    }

    .products-page .table tbody tr:last-child td {
        border-bottom: 0;
    }

    .products-page .product-image,
    .products-page .product-image-placeholder {
        width: 56px;
        height: 56px;
        border-radius: 12px;
    }

    .products-page .product-image {
        object-fit: cover;
        background: #f4f6fa;
    }

    .products-page .product-image-placeholder {
        display: grid;
        place-items: center;
        color: #8a94a6;
        background: #f2f4f8;
        font-size: 1.25rem;
    }

    .products-page .product-name {
        min-width: 180px;
        font-weight: 600;
    }

    .products-page .stock-pill,
    .products-page .product-state {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .products-page .stock-ok,
    .products-page .state-active {
        color: #198754;
        background: #e5f6ec;
    }

    .products-page .stock-low {
        color: #996300;
        background: #fff4d6;
    }

    .products-page .stock-empty,
    .products-page .state-inactive {
        color: #667085;
        background: #eef0f3;
    }

    .products-page .action-button {
        border-radius: 9px;
        font-weight: 600;
    }

    .products-page .empty-state {
        padding: 48px 20px !important;
        color: var(--products-muted);
        text-align: center;
    }

    @media (max-width: 575.98px) {
        .products-page .page-intro,
        .products-page .products-toolbar {
            padding: 18px;
        }

        .products-page .table thead th,
        .products-page .table tbody td {
            padding-right: 13px;
            padding-left: 13px;
        }
    }
</style>

<main class="container-fluid products-page px-3 px-lg-4 py-4">
    <section class="page-intro mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="mb-2">Quản lý sản phẩm</h1>
            </div>
            <a href="/products/create.php" class="btn btn-primary px-3 py-2 fw-semibold">
                + Thêm sản phẩm
            </a>
        </div>
    </section>

    <section class="row g-3 mb-4" aria-label="Thống kê sản phẩm">
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Tổng sản phẩm</div>
                        <div class="stat-value"><?= count($products) ?></div>
                    </div>
                    <span class="stat-mark text-primary bg-primary-subtle" aria-hidden="true">▦</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Đang kinh doanh</div>
                        <div class="stat-value"><?= $activeCount ?></div>
                    </div>
                    <span class="stat-mark text-success bg-success-subtle" aria-hidden="true">✓</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Sắp hết hàng</div>
                        <div class="stat-value"><?= $lowStockCount ?></div>
                    </div>
                    <span class="stat-mark text-warning-emphasis bg-warning-subtle" aria-hidden="true">!</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="stat-label small mb-1">Hết hàng</div>
                        <div class="stat-value"><?= $outOfStockCount ?></div>
                    </div>
                    <span class="stat-mark text-secondary bg-secondary-subtle" aria-hidden="true">−</span>
                </div>
            </div>
        </div>
    </section>

    <section class="card products-card">
        <div class="products-toolbar">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Danh sách sản phẩm</h2>
                    <p class="small text-secondary mb-0">
                        Hiển thị <span id="visible-product-count"><?= count($products) ?></span>
                        / <?= count($products) ?> sản phẩm
                    </p>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <label class="visually-hidden" for="product-search">Tìm sản phẩm</label>
                    <input
                        type="search"
                        class="form-control"
                        id="product-search"
                        placeholder="Tìm tên, mã, nhà cung cấp..."
                        autocomplete="off"
                    >
                    <label class="visually-hidden" for="product-category-filter">Lọc danh mục</label>
                    <select class="form-select" id="product-category-filter">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($categories as $categoryID => $categoryName): ?>
                            <option value="<?= (int) $categoryID ?>">
                                <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="visually-hidden" for="product-state-filter">Lọc trạng thái</label>
                    <select class="form-select" id="product-state-filter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1">Đang bán</option>
                        <option value="0">Ngừng bán</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">Sản phẩm</th>
                        <th scope="col">Danh mục</th>
                        <th scope="col">Nhà cung cấp</th>
                        <th scope="col" class="text-end">Giá bán</th>
                        <th scope="col" class="text-center">Tồn kho</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col" class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="products-table-body">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $imageFile = $product['ImageFile'] ?? '';
                        $altText = $product['AltText'] ?: $product['ProductName'];
                        $stockQuantity = (int) $product['StockQuantity'];
                        $isActive = (int) $product['IsActive'];
                        $stockClass = $stockQuantity === 0
                            ? 'stock-empty'
                            : ($stockQuantity < 5 ? 'stock-low' : 'stock-ok');
                        ?>
                        <tr
                            data-category="<?= (int) $product['CategoryID'] ?>"
                            data-state="<?= $isActive ?>"
                            data-search="<?= htmlspecialchars(
                                $product['ProductCode'] . ' ' . $product['ProductName'] . ' '
                                    . $product['CategoryName'] . ' ' . $product['SupplierName'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($imageFile !== ''): ?>
                                        <img
                                            src="/uploads/products/<?= htmlspecialchars($imageFile, ENT_QUOTES, 'UTF-8') ?>"
                                            alt="<?= htmlspecialchars($altText, ENT_QUOTES, 'UTF-8') ?>"
                                            class="product-image"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <span class="product-image-placeholder" aria-hidden="true">▧</span>
                                    <?php endif; ?>
                                    <div>
                                        <div class="product-name">
                                            <?= htmlspecialchars($product['ProductName'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="product-code small">
                                            <?= htmlspecialchars($product['ProductCode'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php if (!empty($product['Unit'])): ?>
                                                <span class="product-meta">· <?= htmlspecialchars($product['Unit'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($product['CategoryName'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($product['SupplierName'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end text-nowrap fw-semibold">
                                <?= number_format((float) $product['Price'], 0, ',', '.') ?> đ
                            </td>
                            <td class="text-center">
                                <span class="stock-pill <?= $stockClass ?>">
                                    <?= $stockQuantity ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isActive === 1): ?>
                                    <span class="product-state state-active">Đang bán</span>
                                <?php else: ?>
                                    <span class="product-state state-inactive">Ngừng bán</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a
                                    href="/products/edit.php?id=<?= (int) $product['ProductID'] ?>"
                                    class="btn btn-sm btn-outline-primary action-button"
                                >
                                    Sửa
                                </a>
                                <form
                                    action="/products/delete.php"
                                    method="post"
                                    class="d-inline"
                                    onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');"
                                >
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $product['ProductID'] ?>"
                                    >
                                    <button type="submit" class="btn btn-sm btn-outline-danger action-button">
                                        Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="products-empty-state" <?= $products ? 'hidden' : '' ?>>
                        <td colspan="7" class="empty-state">
                            <?= $products
                                ? 'Không tìm thấy sản phẩm phù hợp với bộ lọc.'
                                : 'Chưa có sản phẩm nào.' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    (() => {
        const searchInput = document.getElementById('product-search');
        const categoryFilter = document.getElementById('product-category-filter');
        const stateFilter = document.getElementById('product-state-filter');
        const rows = Array.from(document.querySelectorAll('#products-table-body tr[data-state]'));
        const countLabel = document.getElementById('visible-product-count');
        const emptyState = document.getElementById('products-empty-state');

        const filterProducts = () => {
            const searchTerm = searchInput.value.trim().toLocaleLowerCase('vi');
            let visibleCount = 0;

            rows.forEach((row) => {
                const matchesSearch = row.dataset.search.toLocaleLowerCase('vi').includes(searchTerm);
                const matchesCategory = !categoryFilter.value
                    || row.dataset.category === categoryFilter.value;
                const matchesState = !stateFilter.value || row.dataset.state === stateFilter.value;
                const isVisible = matchesSearch && matchesCategory && matchesState;

                row.hidden = !isVisible;
                visibleCount += isVisible ? 1 : 0;
            });

            countLabel.textContent = visibleCount;
            emptyState.hidden = visibleCount > 0;
        };

        searchInput.addEventListener('input', filterProducts);
        categoryFilter.addEventListener('change', filterProducts);
        stateFilter.addEventListener('change', filterProducts);
    })();
</script>

<?php

require_once '/var/www/src/includes/admin/footer.php';

$conn->close();
