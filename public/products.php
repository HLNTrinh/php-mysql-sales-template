<?php
require_once '/var/www/src/config/database.php';

// 1. Lấy danh sách danh mục
$sqlCategories = "
    SELECT CategoryID, CategoryName
    FROM categories
    ORDER BY CategoryName
";

$categoryResult = $conn->query($sqlCategories);
$categories = [];

while ($category = $categoryResult->fetch_assoc()) {
    $categories[] = $category;
}

$categoryResult->free();

// 2. Nhận dữ liệu tìm kiếm và lọc
$categoryID = isset($_GET['category'])
    ? max(0, (int) $_GET['category'])
    : 0;

$keyword = isset($_GET['keyword'])
    ? trim($_GET['keyword'])
    : '';

$itemsPerPage = 10;

$page = isset($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;

$searchKeyword = '%' . $keyword . '%';

// 3. Tạo điều kiện truy vấn
$where = "
    WHERE p.CategoryID = c.CategoryID
      AND p.IsActive = 1
";

$params = [];
$types = '';

if ($categoryID > 0) {
    $where .= " AND p.CategoryID = ?";
    $params[] = $categoryID;
    $types .= 'i';
}

if ($keyword !== '') {
    $where .= "
        AND (
            p.ProductName LIKE ?
            OR p.ProductCode LIKE ?
        )
    ";

    $params[] = $searchKeyword;
    $params[] = $searchKeyword;
    $types .= 'ss';
}

// 4. Đếm tổng số sản phẩm phù hợp
$sqlCount = "
    SELECT COUNT(*) AS TotalProducts
    FROM products p, categories c
    $where
";

$stmtCount = $conn->prepare($sqlCount);

if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}

$stmtCount->execute();

$countResult = $stmtCount->get_result();
$countRow = $countResult->fetch_assoc();

$totalProducts = (int) $countRow['TotalProducts'];
$totalPages = (int) ceil($totalProducts / $itemsPerPage);

$countResult->free();
$stmtCount->close();

// 5. Kiểm tra trang hiện tại
if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $itemsPerPage;

// 6. Truy vấn sản phẩm
$sql = "
    SELECT
        p.ProductID,
        p.ProductCode,
        p.ProductName,
        p.Price,
        c.CategoryName,
        (
            SELECT pi.ImageFile
            FROM product_images pi
            WHERE pi.ProductID = p.ProductID
              AND pi.IsPrimary = 1
            LIMIT 1
        ) AS ImageFile
    FROM products p, categories c
    $where
    ORDER BY p.ProductID DESC
    LIMIT ? OFFSET ?
";

$listParams = $params;
$listTypes = $types . 'ii';

$listParams[] = $itemsPerPage;
$listParams[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($listTypes, ...$listParams);
$stmt->execute();

$result = $stmt->get_result();

$pageTitle = 'Sản phẩm';

require_once '/var/www/src/includes/frontend/header.php';
require_once '/var/www/src/includes/frontend/navbar.php';
?>

<main class="container py-5">

    <!-- Tiêu đề -->
    <div class="mb-4">
        <h1>Sản phẩm</h1>
        <p class="text-muted">
            Khám phá các sản phẩm hiện có tại cửa hàng.
        </p>
    </div>

    <!-- 7. Form tìm kiếm và lọc danh mục -->
    <form method="GET" action="/products.php" class="mb-4">
        <div class="row g-3">

            <div class="col-md-5">
                <input
                    type="text"
                    name="keyword"
                    class="form-control"
                    placeholder="Nhập tên hoặc mã sản phẩm..."
                    value="<?= htmlspecialchars(
                        $keyword,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <div class="col-md-4">
                <select name="category" class="form-select">
                    <option value="0">Tất cả danh mục</option>

                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= (int) $category['CategoryID'] ?>"
                            <?= $categoryID === (int) $category['CategoryID']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= htmlspecialchars(
                                $category['CategoryName'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    Tìm kiếm
                </button>

                <a
                    href="/products.php"
                    class="btn btn-outline-secondary"
                >
                    Làm mới
                </a>
            </div>

        </div>
    </form>

    <!-- Thông tin kết quả -->
    <p class="text-muted mb-4">
        Tổng cộng <?= $totalProducts ?> sản phẩm.
    </p>

    <!-- 8. Danh sách sản phẩm -->
    <?php if ($result->num_rows > 0): ?>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-3">

            <?php while ($product = $result->fetch_assoc()): ?>

                <div class="col">
                    <div class="card h-100">

                        <?php if (!empty($product['ImageFile'])): ?>

                            <div
                                class="bg-white" style="aspect-ratio: 1 / 1;"
                            >
                                <img
                                    src="/uploads/products/<?= htmlspecialchars(
                                        $product['ImageFile'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $product['ProductName'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    style="width: 100%; height: 100%; object-fit: contain;"
                                >
                            </div>

                        <?php else: ?>

                            <div
                                 class="bg-light d-flex align-items-center justify-content-center text-muted"
                                style="aspect-ratio: 1 / 1;"
                            >
                                Chưa có hình ảnh
                            </div>

                        <?php endif; ?>

                        <div class="card-body d-flex flex-column">

                            <p class="text-muted small mb-1">
                                <?= htmlspecialchars(
                                    $product['CategoryName'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>

                            <h5 class="card-title"
                            title="<?= htmlspecialchars($product['ProductName'], ENT_QUOTES, 'UTF-8') ?>"
                            style="
                                    display: -webkit-box;
                                    -webkit-line-clamp: 2;
                                    -webkit-box-orient: vertical;
                                    overflow: hidden;
                                    min-height: 4.5em;
                                "
                            >
                                <?= htmlspecialchars(
                                    $product['ProductName'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
        
                            </h5>

                            <p class="text-muted small">
                                Mã sản phẩm:
                                <?= htmlspecialchars(
                                    $product['ProductCode'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>

                            <p class="fw-bold fs-5 mb-3">
                                <?= number_format(
                                    (float) $product['Price'],
                                    0,
                                    ',',
                                    '.'
                                ) ?> đ
                            </p>

                            <a
                                href="/product-detail.php?id=<?= (int) $product['ProductID'] ?>"
                                class="btn btn-outline-primary mt-auto"
                            >
                                Xem chi tiết
                            </a>

                        </div>
                    </div>
                </div>

            <?php endwhile; ?>

        </div>

        <!-- 9. Phân trang -->
        <?php if ($totalPages > 1): ?>

            <nav class="mt-5" aria-label="Phân trang sản phẩm">
                <ul class="pagination justify-content-center flex-wrap">

                    <!-- Trang trước -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a
                            class="page-link"
                            href="?<?= http_build_query([
                                'category' => $categoryID,
                                'keyword' => $keyword,
                                'page' => max(1, $page - 1)
                            ]) ?>"
                        >
                            Trước
                        </a>
                    </li>

                    <!-- Số trang -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                        <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                            <a
                                class="page-link"
                                href="?<?= http_build_query([
                                    'category' => $categoryID,
                                    'keyword' => $keyword,
                                    'page' => $i
                                ]) ?>"
                            >
                                <?= $i ?>
                            </a>
                        </li>

                    <?php endfor; ?>

                    <!-- Trang sau -->
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a
                            class="page-link"
                            href="?<?= http_build_query([
                                'category' => $categoryID,
                                'keyword' => $keyword,
                                'page' => min($totalPages, $page + 1)
                            ]) ?>"
                        >
                            Sau
                        </a>
                    </li>

                </ul>
            </nav>

        <?php endif; ?>

    <?php else: ?>

        <div class="alert alert-info">
            Không tìm thấy sản phẩm phù hợp.
        </div>

    <?php endif; ?>

</main>

<?php
$result->free();
$stmt->close();

require_once '/var/www/src/includes/frontend/footer.php';
