<?php

$pageTitle = 'Thêm sản phẩm';

require_once '/var/www/src/config/database.php';

$error = '';

$sqlCategories = "
    SELECT CategoryID, CategoryName
    FROM categories
    ORDER BY CategoryName
";

$categories = $conn->query($sqlCategories);

$sqlSuppliers = "
    SELECT SupplierID, SupplierName
    FROM suppliers
    ORDER BY SupplierName
";

$suppliers = $conn->query($sqlSuppliers);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productCode = trim($_POST['product_code'] ?? '');
    $productName = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $unit = trim($_POST['unit'] ?? '');

    $price = (float) ($_POST['price'] ?? 0);
    $stockQuantity = (int) ($_POST['stock_quantity'] ?? 0);

    $categoryID = (int) ($_POST['category_id'] ?? 0);
    $supplierID = (int) ($_POST['supplier_id'] ?? 0);

    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($productCode === '') {
        $error = 'Mã sản phẩm không được để trống.';

    } elseif ($productName === '') {
        $error = 'Tên sản phẩm không được để trống.';

    } elseif ($price < 0) {
        $error = 'Giá sản phẩm không hợp lệ.';

    } elseif ($stockQuantity < 0) {
        $error = 'Số lượng tồn kho không hợp lệ.';

    } elseif ($categoryID <= 0) {
        $error = 'Vui lòng chọn danh mục.';

    } elseif ($supplierID <= 0) {
        $error = 'Vui lòng chọn nhà cung cấp.';

    } else {

        $sql = "
            INSERT INTO products
            (
                ProductCode,
                ProductName,
                Description,
                Unit,
                Price,
                StockQuantity,
                IsActive,
                SupplierID,
                CategoryID
            )
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            'ssssdiiii',
            $productCode,
            $productName,
            $description,
            $unit,
            $price,
            $stockQuantity,
            $isActive,
            $supplierID,
            $categoryID
        );

        if ($stmt->execute()) {
            header('Location: /products/');
            exit;
        }

        $error = 'Không thể thêm sản phẩm.';
        $stmt->close();
    }
}

require_once '/var/www/src/includes/header.php';
require_once '/var/www/src/includes/navbar.php';
?>

 <div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h2>Thêm sản phẩm</h2>
    </div>
    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">

        <div class="mb-3">

            <label for="product_code" class="form-label">
                Mã sản phẩm
            </label>

            <input
                type="text"
                class="form-control"
                id="product_code"
                name="product_code"
                value="<?= htmlspecialchars($_POST['product_code'] ?? '') ?>"
                required
            >

        </div>
        <div class="mb-3">

            <label for="product_name" class="form-label">
                Tên sản phẩm
            </label>

            <input
                type="text"
                class="form-control"
                id="product_name"
                name="product_name"
                value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>"
                required
            >

        </div>
        <div class="mb-3">

            <label for="description" class="form-label">
                Mô tả
            </label>

            <textarea
                class="form-control"
                id="description"
                name="description"
                rows="4"
                placeholder="Nhập mô tả sản phẩm"
            ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

        </div>
        <div class="mb-3">

            <label for="unit" class="form-label">
                Đơn vị
            </label>

            <input
                type="text"
                class="form-control"
                id="unit"
                name="unit"
                value="<?= htmlspecialchars($_POST['unit'] ?? '') ?>"
            >
        </div>

        <div class="mb-3">

            <label for="price" class="form-label">
                Giá sản phẩm
            </label>
            <input
                type="number"
                class="form-control"
                id="price"
                name="price"
                min="0"
                step="0.01"
                value="<?= htmlspecialchars($_POST['price'] ?? '0') ?>"
                required
            >
        </div>
        <div class="mb-3">

            <label for="stock_quantity" class="form-label">
                Số lượng tồn kho
            </label>

            <input
                type="number"
                class="form-control"
                id="stock_quantity"
                name="stock_quantity"
                min="0"
                value="<?= htmlspecialchars($_POST['stock_quantity'] ?? '0') ?>"
                required
            >

        </div>
        <div class="mb-3">

            <label for="category_id" class="form-label">
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

                    <option
                        value="<?= $category['CategoryID'] ?>"
                        <?= (
                            (int)($_POST['category_id'] ?? 0)
                            ===
                            (int)$category['CategoryID']
                        ) ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars($category['CategoryName']) ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </div>
        <div class="mb-3">

            <label for="supplier_id" class="form-label">
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

                    <option
                        value="<?= $supplier['SupplierID'] ?>"
                        <?= (
                            (int)($_POST['supplier_id'] ?? 0)
                            ===
                            (int)$supplier['SupplierID']
                        ) ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars($supplier['SupplierName']) ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </div>

        <div class="mb-3 form-check">

            <input
                type="checkbox"
                class="form-check-input"
                id="is_active"
                name="is_active"
                value="1"
                <?= isset($_POST['is_active']) ? 'checked' : 'checked' ?>
            >

            <label
                for="is_active"
                class="form-check-label"
            >
                Đang bán
            </label>

        </div>


        <!-- Nút -->

        <div class="d-flex gap-2">

            <button
                type="submit"
                class="btn btn-primary"
            >
                Thêm sản phẩm
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