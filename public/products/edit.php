<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$pageTitle = 'Sửa sản phẩm';

require_once '/var/www/src/config/database.php';

$error = '';

$productID = (int) ($_GET['id'] ?? 0);

if ($productID <= 0) {
    die('Mã sản phẩm không hợp lệ.');
}

$sqlProduct = "
    SELECT
        ProductID,
        ProductCode,
        ProductName,
        Description,
        Unit,
        Price,
        StockQuantity,
        IsActive,
        SupplierID,
        CategoryID
    FROM products
    WHERE ProductID = ?
";

$stmtProduct = $conn->prepare($sqlProduct);

if (!$stmtProduct) {
    die('Lỗi prepare SQL: ' . $conn->error);
}

$stmtProduct->bind_param(
    'i',
    $productID
);

$stmtProduct->execute();

$resultProduct = $stmtProduct->get_result();

if ($resultProduct->num_rows === 0) {
    die('Không tìm thấy sản phẩm.');
}
$product = $resultProduct->fetch_assoc();
$stmtProduct->close();
$sqlCategories = "
    SELECT
        CategoryID,
        CategoryName
    FROM categories
    ORDER BY CategoryName
";
$categories = $conn->query($sqlCategories);
if (!$categories) {
    die('Lỗi lấy danh mục: ' . $conn->error);
}

$sqlSuppliers = "
    SELECT
        SupplierID,
        SupplierName
    FROM suppliers
    ORDER BY SupplierName
";

$suppliers = $conn->query($sqlSuppliers);

if (!$suppliers) {
    die('Lỗi lấy nhà cung cấp: ' . $conn->error);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productCode = trim($_POST['product_code'] ?? '');

    $productName = trim($_POST['product_name'] ?? '');

    $description = trim($_POST['description'] ?? '');

    $unit = trim($_POST['unit'] ?? '');

    $price = (float) ($_POST['price'] ?? 0);

    $stockQuantity = (int) ($_POST['stock_quantity'] ?? 0);

    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $supplierID = (int) ($_POST['supplier_id'] ?? 0);

    $categoryID = (int) ($_POST['category_id'] ?? 0);

    if ($productCode === '') {

        $error = 'Mã sản phẩm không được để trống.';

    } elseif ($productName === '') {

        $error = 'Tên sản phẩm không được để trống.';

    } elseif ($price < 0) {

        $error = 'Giá sản phẩm không được nhỏ hơn 0.';

    } elseif ($stockQuantity < 0) {

        $error = 'Số lượng tồn kho không được nhỏ hơn 0.';

    } elseif ($categoryID <= 0) {

        $error = 'Vui lòng chọn danh mục.';

    } elseif ($supplierID <= 0) {

        $error = 'Vui lòng chọn nhà cung cấp.';

    } else {
        $sql = "
            UPDATE products
            SET
                ProductCode = ?,
                ProductName = ?,
                Description = ?,
                Unit = ?,
                Price = ?,
                StockQuantity = ?,
                IsActive = ?,
                SupplierID = ?,
                CategoryID = ?
            WHERE ProductID = ?
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die('Lỗi prepare SQL: ' . $conn->error);
        }

        $stmt->bind_param(
            'ssssdiiiii',
            $productCode,
            $productName,
            $description,
            $unit,
            $price,
            $stockQuantity,
            $isActive,
            $supplierID,
            $categoryID,
            $productID
        );

        if ($stmt->execute()) {

            header('Location: /products/');
            exit;

        } else {

            $error = 'Lỗi cập nhật sản phẩm: ' . $stmt->error;
        }

        $stmt->close();
    }
}


require_once '/var/www/src/includes/header.php';

require_once '/var/www/src/includes/navbar.php';

?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h2>Sửa sản phẩm</h2>
    </div>

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <form method="POST">
        <div class="mb-3">
            <label
                for="product_code"
                class="form-label"
            >
                Mã sản phẩm
            </label>

            <input
                type="text"
                class="form-control"
                id="product_code"
                name="product_code"
                value="<?= htmlspecialchars(
                    $_POST['product_code']
                    ?? $product['ProductCode']
                ) ?>"
                required
            >
        </div>
        <div class="mb-3">

            <label
                for="product_name"
                class="form-label"
            >
                Tên sản phẩm
            </label>

            <input
                type="text"
                class="form-control"
                id="product_name"
                name="product_name"
                value="<?= htmlspecialchars(
                    $_POST['product_name']
                    ?? $product['ProductName']
                ) ?>"
                required
            >
        </div>
        <div class="mb-3">

            <label
                for="description"
                class="form-label"
            >
                Mô tả
            </label>

            <textarea
                class="form-control"
                id="description"
                name="description"
                rows="4"
                placeholder="Nhập mô tả sản phẩm"
            ><?= htmlspecialchars(
                $_POST['description']
                ?? $product['Description']
                ?? ''
            ) ?></textarea>

        </div>
        <div class="mb-3">

            <label
                for="unit"
                class="form-label"
            >
                Đơn vị
            </label>

            <input
                type="text"
                class="form-control"
                id="unit"
                name="unit"
                value="<?= htmlspecialchars(
                    $_POST['unit']
                    ?? $product['Unit']
                    ?? ''
                ) ?>"
                placeholder="Ví dụ: Cái, Chiếc, Hộp"
            >

        </div>
        <div class="mb-3">

            <label
                for="price"
                class="form-label"
            >
                Giá sản phẩm
            </label>

            <input
                type="number"
                class="form-control"
                id="price"
                name="price"
                min="0"
                step="0.01"
                value="<?= htmlspecialchars(
                    $_POST['price']
                    ?? $product['Price']
                ) ?>"
                required
            >

        </div>
        <div class="mb-3">

            <label
                for="stock_quantity"
                class="form-label"
            >
                Số lượng tồn kho
            </label>

            <input
                type="number"
                class="form-control"
                id="stock_quantity"
                name="stock_quantity"
                min="0"
                value="<?= htmlspecialchars(
                    $_POST['stock_quantity']
                    ?? $product['StockQuantity']
                ) ?>"
                required
            >
        </div>
        <div class="mb-3">

            <label
                for="category_id"
                class="form-label"
            >
                Danh mục
            </label>

            <select
                class="form-select"
                id="category_id"
                name="category_id"
                required
            >

                <option value="">
                    -- Chọn danh mục --
                </option>

                <?php while ($category = $categories->fetch_assoc()): ?>

                    <?php
                    $selectedCategory =
                        (int) (
                            $_POST['category_id']
                            ?? $product['CategoryID']
                        )
                        ===
                        (int) $category['CategoryID'];
                    ?>

                    <option
                        value="<?= $category['CategoryID'] ?>"
                        <?= $selectedCategory ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars(
                            $category['CategoryName']
                        ) ?>
                    </option>

                <?php endwhile; ?>

            </select>

        </div>
        <div class="mb-3">

            <label
                for="supplier_id"
                class="form-label"
            >
                Nhà cung cấp
            </label>

            <select
                class="form-select"
                id="supplier_id"
                name="supplier_id"
                required
            >

                <option value="">
                    -- Chọn nhà cung cấp --
                </option>

                <?php while ($supplier = $suppliers->fetch_assoc()): ?>

                    <?php
                    $selectedSupplier =
                        (int) (
                            $_POST['supplier_id']
                            ?? $product['SupplierID']
                        )
                        ===
                        (int) $supplier['SupplierID'];
                    ?>

                    <option
                        value="<?= $supplier['SupplierID'] ?>"
                        <?= $selectedSupplier ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars(
                            $supplier['SupplierName']
                        ) ?>
                    </option>

                <?php endwhile; ?>

            </select>

        </div>
        <div class="mb-3 form-check">

            <?php
            $activeValue =
                $_POST['is_active']
                ?? $product['IsActive'];
            ?>

            <input
                type="checkbox"
                class="form-check-input"
                id="is_active"
                name="is_active"
                value="1"
                <?= (int)$activeValue === 1 ? 'checked' : '' ?>
            >

            <label
                for="is_active"
                class="form-check-label"
            >
                Đang bán
            </label>

        </div>
        <div class="d-flex gap-2">

            <button
                type="submit"
                class="btn btn-primary"
            >
                Cập nhật
            </button>

            <a
                href="/products/"
                class="btn btn-secondary"
            >
                Hủy
            </a>

        </div>

    </form>

</div>

<?php

require_once '/var/www/src/includes/footer.php';

$conn->close();

?>