
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = 'Xóa sản phẩm';

require_once '/var/www/src/config/database.php';

$productID = (int) ($_GET['id'] ?? 0);
$error = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /products/');
    exit;
}

if ($productID <= 0) {
    die('Mã sản phẩm không hợp lệ.');
}

if ($productID <= 0) {
    die('Mã sản phẩm không hợp lệ.');
}

// Lấy thông tin sản phẩm
$sql = "
    SELECT
        ProductID,
        ProductCode,
        ProductName,
        Description,
        Unit,
        Price,
        StockQuantity,
        IsActive,
        CategoryID,
        SupplierID
    FROM products
    WHERE ProductID = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $productID);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    die('Không tìm thấy sản phẩm.');
}

// Lấy hình ảnh sản phẩm
$sqlImages = "
    SELECT ImageFile, AltText, IsPrimary
    FROM product_images
    WHERE ProductID = ?
    ORDER BY SortOrder, ProductImageID
";

$stmtImages = $conn->prepare($sqlImages);
$stmtImages->bind_param('i', $productID);
$stmtImages->execute();
$images = $stmtImages->get_result();

// Xử lý xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filesToDelete = [];

    try {
        $conn->begin_transaction();

        // Lấy tên file ảnh trước khi xóa dữ liệu
        $sqlFiles = "
            SELECT ImageFile
            FROM product_images
            WHERE ProductID = ?
        ";

        $stmtFiles = $conn->prepare($sqlFiles);
        $stmtFiles->bind_param('i', $productID);
        $stmtFiles->execute();

        $resultFiles = $stmtFiles->get_result();

        while ($file = $resultFiles->fetch_assoc()) {
            $filesToDelete[] = $file['ImageFile'];
        }

        $stmtFiles->close();

        // Xóa dữ liệu hình ảnh
        $sqlDeleteImages = "
            DELETE FROM product_images
            WHERE ProductID = ?
        ";

        $stmtDeleteImages = $conn->prepare($sqlDeleteImages);
        $stmtDeleteImages->bind_param('i', $productID);
        $stmtDeleteImages->execute();
        $stmtDeleteImages->close();

        // Xóa sản phẩm
        $sqlDeleteProduct = "
            DELETE FROM products
            WHERE ProductID = ?
        ";

        $stmtDeleteProduct = $conn->prepare($sqlDeleteProduct);
        $stmtDeleteProduct->bind_param('i', $productID);
        $stmtDeleteProduct->execute();

        if ($stmtDeleteProduct->affected_rows !== 1) {
            throw new Exception('Không thể xóa sản phẩm.');
        }

        $stmtDeleteProduct->close();

        $conn->commit();

        // Xóa file ảnh trên máy chủ sau khi giao dịch thành công
        foreach ($filesToDelete as $fileName) {
            // Chỉ xử lý tên file hợp lệ
            if (
                basename($fileName) === $fileName &&
                preg_match('/\A[a-zA-Z0-9._-]+\z/', $fileName)
            ) {
                $filePath =
                    '/var/www/html/uploads/products/' . $fileName;

                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }
        }

        header('Location: /products/?deleted=1');
        exit;

    } catch (Throwable $e) {
        $conn->rollback();
        $error = 'Lỗi xóa sản phẩm: ' . $e->getMessage();
    }
}

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';
?>

<div class="container mt-4">

    <h2 class="mb-4">Xóa sản phẩm</h2>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-warning">
        Bạn có chắc chắn muốn xóa sản phẩm này không?
        Thao tác này không thể hoàn tác.
    </div>

    <div class="card mb-4">
        <div class="card-body">

            <p>
                <strong>Mã sản phẩm:</strong>
                <?= htmlspecialchars($product['ProductCode']) ?>
            </p>

            <p>
                <strong>Tên sản phẩm:</strong>
                <?= htmlspecialchars($product['ProductName']) ?>
            </p>

            <p>
                <strong>Mô tả:</strong>
                <?= nl2br(htmlspecialchars($product['Description'] ?? '')) ?>
            </p>

            <p>
                <strong>Đơn vị:</strong>
                <?= htmlspecialchars($product['Unit'] ?? '') ?>
            </p>

            <p>
                <strong>Giá:</strong>
                <?= number_format((float) $product['Price'], 0, ',', '.') ?>
                VNĐ
            </p>

            <p>
                <strong>Số lượng tồn kho:</strong>
                <?= (int) $product['StockQuantity'] ?>
            </p>

            <p>
                <strong>Trạng thái:</strong>
                <?= (int) $product['IsActive'] === 1
                    ? 'Đang bán'
                    : 'Ngừng bán' ?>
            </p>

            <hr>

            <h5 class="mb-3">Hình ảnh sản phẩm</h5>

            <div class="row">
                <?php if ($images->num_rows > 0): ?>

                    <?php while ($image = $images->fetch_assoc()): ?>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="card h-100">

                                <img
                                    src="/uploads/products/<?= htmlspecialchars($image['ImageFile']) ?>"
                                    class="card-img-top"
                                    alt="<?= htmlspecialchars($image['AltText'] ?? '') ?>"
                                    style="height: 180px; object-fit: contain;"
                                >

                                <div class="card-body">
                                    <?php if ((int) $image['IsPrimary'] === 1): ?>
                                        <span class="badge bg-success">
                                            Ảnh chính
                                        </span>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    <?php endwhile; ?>

                <?php else: ?>
                    <div class="col-12">
                        <p class="text-muted">
                            Sản phẩm chưa có hình ảnh.
                        </p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <form method="POST">
        <input
            type="hidden"
            name="confirm_delete"
            value="yes"
        >

        <button type="submit" class="btn btn-danger">
            Xác nhận xóa
        </button>

        <a href="/products/" class="btn btn-secondary">
            Hủy
        </a>
    </form>

</div>

<?php
require_once '/var/www/src/includes/admin/footer.php';
$conn->close();
?>
