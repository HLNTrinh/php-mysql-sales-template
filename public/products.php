<?php
require_once '/var/www/src/config/session.php';
require_once '/var/www/src/config/database.php';

$itemsPerPage = 10;

$keyword = $_GET['keyword'] ?? '';
$keyword = is_string($keyword) ? trim($keyword) : '';
$searchKeyword = '%' . $keyword . '%';

$page = isset($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;

// 1. Lấy sản phẩm đang kinh doanh
$where = "
    WHERE p.CategoryID = c.CategoryID
      AND p.IsActive = 1
";

if ($keyword !== '') {
    $where .= "
      AND (
          p.ProductName LIKE ?
          OR p.ProductCode LIKE ?
      )
    ";
}

// 2. Đếm tổng số sản phẩm
$sqlCount = "
    SELECT COUNT(*) AS TotalProducts
    FROM products p, categories c
    $where
";

$stmtCount = $conn->prepare($sqlCount);

if ($keyword !== '') {
    $stmtCount->bind_param('ss', $searchKeyword, $searchKeyword);
}

$stmtCount->execute();

$countResult = $stmtCount->get_result();
$countRow = $countResult->fetch_assoc();

$totalProducts = (int) $countRow['TotalProducts'];
$totalPages = (int) ceil($totalProducts / $itemsPerPage);

$countResult->free();
$stmtCount->close();

// 3. Kiểm tra trang hiện tại
if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $itemsPerPage;

// 4. Truy vấn sản phẩm
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

$stmt = $conn->prepare($sql);

if ($keyword !== '') {
    $stmt->bind_param(
        'ssii',
        $searchKeyword,
        $searchKeyword,
        $itemsPerPage,
        $offset
    );
} else {
    $stmt->bind_param('ii', $itemsPerPage, $offset);
}

$stmt->execute();

$result = $stmt->get_result();

$pageTitle = $keyword !== ''
    ? 'Kết quả tìm kiếm: ' . $keyword
    : 'Tất cả sản phẩm';

require_once '/var/www/src/includes/frontend/header.php';
require_once '/var/www/src/includes/frontend/navbar.php';
?>

<main class="catalog-page">
    <div class="container catalog-container">
        <nav class="catalog-breadcrumb" aria-label="Điều hướng">
            <a href="/">Trang chủ</a>
            <span aria-hidden="true">/</span>
            <?php if ($keyword !== ''): ?>
                <span>Kết quả tìm kiếm</span>
            <?php else: ?>
                <span>Sản phẩm</span>
            <?php endif; ?>
        </nav>

        <section class="catalog-heading">
            <div>
                <span class="catalog-eyebrow">
                    <?= $keyword !== '' ? 'TÌM KIẾM SẢN PHẨM' : 'CỬA HÀNG' ?>
                </span>
                <h1>
                    <?= $keyword !== '' ? 'Kết quả tìm kiếm' : 'Khám phá sản phẩm' ?>
                </h1>
                <?php if ($keyword !== ''): ?>
                    <p class="catalog-description">
                        Kết quả cho từ khóa
                        <strong>“<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>”</strong>
                    </p>
                <?php else: ?>
                    <p class="catalog-description">
                        Tìm sản phẩm phù hợp trong danh mục hàng đang kinh doanh.
                    </p>
                <?php endif; ?>
            </div>
            <span class="catalog-result-count">
                <?= $totalProducts ?> sản phẩm
            </span>
        </section>

        <?php if ($result->num_rows > 0): ?>
            <div class="catalog-grid">
                <?php while ($product = $result->fetch_assoc()): ?>
                    <article class="product-card">
                        <a
                            class="product-card-image"
                            href="/product-detail.php?id=<?= (int) $product['ProductID'] ?>"
                            aria-label="Xem <?= htmlspecialchars($product['ProductName'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <?php if (!empty($product['ImageFile'])): ?>
                                <img
                                    src="/uploads/products/<?= htmlspecialchars($product['ImageFile'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($product['ProductName'], ENT_QUOTES, 'UTF-8') ?>"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <span class="product-image-placeholder">
                                    Chưa có hình ảnh
                                </span>
                            <?php endif; ?>
                        </a>

                        <div class="product-card-body">
                            <span class="product-category">
                                <?= htmlspecialchars($product['CategoryName'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <h2 title="<?= htmlspecialchars($product['ProductName'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($product['ProductName'], ENT_QUOTES, 'UTF-8') ?>
                            </h2>
                            <span class="product-code">
                                Mã sản phẩm:
                                <?= htmlspecialchars($product['ProductCode'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <strong class="product-price">
                                <?= number_format((float) $product['Price'], 0, ',', '.') ?> đ
                            </strong>
                            <a
                                href="/product-detail.php?id=<?= (int) $product['ProductID'] ?>"
                                class="product-detail-button"
                            >
                                Xem chi tiết
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-5" aria-label="Phân trang sản phẩm">
                    <ul class="pagination justify-content-center flex-wrap">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a
                                class="page-link"
                                href="?<?= http_build_query([
                                    'keyword' => $keyword,
                                    'page' => max(1, $page - 1)
                                ]) ?>"
                            >
                                Trước
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                <a
                                    class="page-link"
                                    href="?<?= http_build_query([
                                        'keyword' => $keyword,
                                        'page' => $i
                                    ]) ?>"
                                    <?= $page === $i ? 'aria-current="page"' : '' ?>
                                >
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a
                                class="page-link"
                                href="?<?= http_build_query([
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
            <section class="catalog-empty">
                <div class="catalog-empty-mark" aria-hidden="true">?</div>
                <h2>
                    <?= $keyword !== '' ? 'Chưa tìm thấy sản phẩm phù hợp' : 'Chưa có sản phẩm đang kinh doanh' ?>
                </h2>
                <p>
                    <?php if ($keyword !== ''): ?>
                        Hãy thử từ khóa khác hoặc kiểm tra lại chính tả.
                    <?php else: ?>
                        Sản phẩm mới sẽ được hiển thị tại đây.
                    <?php endif; ?>
                </p>
                <?php if ($keyword !== ''): ?>
                    <a href="/products.php" class="product-detail-button">
                        Xem tất cả sản phẩm
                    </a>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</main>

<style>
    .catalog-page {
        min-height: 70vh;
        padding: 22px 0 64px;
        background: #f7f8fc;
    }

    .catalog-container {
        max-width: 1160px;
    }

    .catalog-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 9px;
        margin-bottom: 30px;
        color: #78869d;
        font-size: 13px;
    }

    .catalog-breadcrumb a {
        color: #52627b;
        text-decoration: none;
    }

    .catalog-breadcrumb a:hover {
        color: #2874d8;
    }

    .catalog-heading {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 26px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e4e9f1;
    }

    .catalog-eyebrow {
        color: #2874d8;
        font-size: 11px;
        font-weight: 750;
        letter-spacing: 0.1em;
    }

    .catalog-heading h1 {
        margin: 6px 0;
        color: #1d2b45;
        font-size: clamp(26px, 4vw, 34px);
        font-weight: 760;
        letter-spacing: -0.6px;
    }

    .catalog-description {
        margin: 0;
        color: #748198;
        font-size: 14px;
    }

    .catalog-description strong {
        color: #34425a;
        font-weight: 700;
    }

    .catalog-result-count {
        flex: 0 0 auto;
        padding: 8px 13px;
        border: 1px solid #e1e7f0;
        border-radius: 999px;
        background: #fff;
        color: #53647d;
        font-size: 13px;
        font-weight: 650;
    }

    .catalog-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 20px;
    }

    .product-card {
        display: flex;
        min-width: 0;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid #e8eaf0;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 5px 18px rgb(34 54 88 / 4%);
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .product-card:hover {
        transform: translateY(-4px);
        border-color: #d3e1f4;
        box-shadow: 0 12px 26px rgb(34 72 130 / 10%);
    }

    .product-card-image {
        display: grid;
        aspect-ratio: 1 / 1;
        padding: 14px;
        place-items: center;
        background: #fff;
    }

    .product-card-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: transform 0.25s ease;
    }

    .product-card:hover .product-card-image img {
        transform: scale(1.035);
    }

    .product-image-placeholder {
        color: #8995a8;
        font-size: 13px;
    }

    .product-card-body {
        display: flex;
        flex: 1;
        flex-direction: column;
        align-items: flex-start;
        padding: 4px 17px 17px;
    }

    .product-category {
        overflow: hidden;
        max-width: 100%;
        color: #8792a3;
        font-size: 12px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .product-card h2 {
        display: -webkit-box;
        min-height: 44px;
        margin: 4px 0 5px;
        overflow: hidden;
        color: #27364d;
        font-size: 16px;
        font-weight: 700;
        line-height: 1.4;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .product-code {
        color: #8994a5;
        font-size: 11px;
    }

    .product-price {
        margin: 9px 0 14px;
        color: #1d2b45;
        font-size: 19px;
        font-weight: 760;
    }

    .product-detail-button {
        display: inline-flex;
        width: 100%;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        margin-top: auto;
        padding: 9px 12px;
        border: 0;
        border-radius: 8px;
        background: #287ee5;
        color: #fff;
        font-size: 13px;
        font-weight: 650;
        text-align: center;
        text-decoration: none;
        transition: background-color 0.2s ease;
    }

    .product-detail-button:hover {
        background: #176bd0;
        color: #fff;
    }

    .catalog-empty {
        display: grid;
        justify-items: center;
        padding: 56px 20px;
        border: 1px solid #e6eaf1;
        border-radius: 16px;
        background: #fff;
        text-align: center;
    }

    .catalog-empty-mark {
        display: grid;
        width: 48px;
        height: 48px;
        margin-bottom: 12px;
        place-items: center;
        border-radius: 50%;
        background: #eef4fc;
        color: #2874d8;
        font-size: 24px;
        font-weight: 700;
    }

    .catalog-empty h2 {
        margin: 0 0 7px;
        color: #27364d;
        font-size: 19px;
        font-weight: 700;
    }

    .catalog-empty p {
        margin: 0 0 18px;
        color: #7d899b;
        font-size: 14px;
    }

    .catalog-empty .product-detail-button {
        width: auto;
    }

    @media (max-width: 991.98px) {
        .catalog-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }
    }

    @media (max-width: 767.98px) {
        .catalog-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .catalog-breadcrumb {
            margin-bottom: 22px;
        }
    }

    @media (max-width: 479.98px) {
        .catalog-page {
            padding-top: 16px;
        }

        .catalog-heading {
            align-items: flex-start;
            flex-direction: column;
            gap: 12px;
        }

        .catalog-result-count {
            padding: 6px 10px;
            font-size: 12px;
        }

        .catalog-grid {
            gap: 12px;
        }

        .product-card {
            border-radius: 12px;
        }

        .product-card-image {
            padding: 8px;
        }

        .product-card-body {
            padding: 4px 11px 12px;
        }

        .product-card h2 {
            font-size: 14px;
        }

        .product-price {
            font-size: 16px;
        }

        .product-detail-button {
            min-height: 36px;
            padding: 7px 8px;
            font-size: 12px;
        }
    }
</style>

<?php
$result->free();
$stmt->close();

require_once '/var/www/src/includes/frontend/footer.php';
