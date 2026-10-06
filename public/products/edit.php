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
    $sqlImages = "
        SELECT
            ProductImageID,
            ImageFile,
            AltText,
            IsPrimary,
            SortOrder
        FROM product_images
        WHERE ProductID = ?
        ORDER BY SortOrder, ProductImageID
    ";

    $stmtImages = $conn->prepare($sqlImages);
    $stmtImages->bind_param('i', $productID);
    $stmtImages->execute();
    $productImages = $stmtImages->get_result();
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
    if (isset($_POST['set_primary_image'])) {

    $imageID = (int) $_POST['set_primary_image'];

    try {
        $conn->begin_transaction();

        $sqlResetPrimary = "
            UPDATE product_images
            SET IsPrimary = 0
            WHERE ProductID = ?
        ";

        $stmtResetPrimary = $conn->prepare($sqlResetPrimary);
        $stmtResetPrimary->bind_param('i', $productID);
        $stmtResetPrimary->execute();
        $stmtResetPrimary->close();

        $sqlSetPrimary = "
            UPDATE product_images
            SET IsPrimary = 1
            WHERE ProductImageID = ?
              AND ProductID = ?
        ";

        $stmtSetPrimary = $conn->prepare($sqlSetPrimary);
        $stmtSetPrimary->bind_param('ii', $imageID, $productID);
        $stmtSetPrimary->execute();

        if ($stmtSetPrimary->affected_rows !== 1) {
            throw new Exception('Không thể đặt ảnh chính.');
        }

        $stmtSetPrimary->close();
        $conn->commit();

        header(
            'Location: /products/edit.php?id='
            . $productID
            . '&primary_updated=1'
        );
        exit;

    } catch (Throwable $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
    $suppliers = $conn->query($sqlSuppliers);

    if (!$suppliers) {
        die('Lỗi lấy nhà cung cấp: ' . $conn->error);
    }
    $validImages = [];
    $imageCount = 0;
    $nextSortOrder = 0;

    if (isset($_POST['delete_image'])) {
        $imageID = (int) $_POST['delete_image'];

        try {
            $conn->begin_transaction();

            $sqlImage = "
                SELECT
                    ProductImageID,
                    ImageFile,
                    IsPrimary,
                    SortOrder
                FROM product_images
                WHERE ProductImageID = ?
                  AND ProductID = ?
            ";

            $stmtImage = $conn->prepare($sqlImage);
            $stmtImage->bind_param(
                'ii',
                $imageID,
                $productID
            );
            $stmtImage->execute();

            $imageToDelete =
                $stmtImage->get_result()->fetch_assoc();

            $stmtImage->close();

            if (!$imageToDelete) {
                throw new Exception(
                    'Không tìm thấy ảnh cần xóa.'
                );
            }

            $sqlDelete = "
                DELETE FROM product_images
                WHERE ProductImageID = ?
                  AND ProductID = ?
            ";

            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->bind_param(
                'ii',
                $imageID,
                $productID
            );
            $stmtDelete->execute();

            if ($stmtDelete->affected_rows !== 1) {
                throw new Exception(
                    'Không thể xóa ảnh.'
                );
            }

            $stmtDelete->close();

            if ((int) $imageToDelete['IsPrimary'] === 1) {
                $sqlNewPrimary = "
                    UPDATE product_images
                    SET IsPrimary = 1
                    WHERE ProductImageID = (
                        SELECT ProductImageID
                        FROM (
                            SELECT ProductImageID
                            FROM product_images
                            WHERE ProductID = ?
                            ORDER BY
                                SortOrder,
                                ProductImageID
                            LIMIT 1
                        ) AS remaining_images
                    )
                ";

                $stmtNewPrimary =
                    $conn->prepare($sqlNewPrimary);

                $stmtNewPrimary->bind_param(
                    'i',
                    $productID
                );

                $stmtNewPrimary->execute();
                $stmtNewPrimary->close();
            }

            $deletedSortOrder =
                (int) $imageToDelete['SortOrder'];

            $sqlReorder = "
                UPDATE product_images
                SET SortOrder = SortOrder - 1
                WHERE ProductID = ?
                  AND SortOrder > ?
            ";

            $stmtReorder = $conn->prepare($sqlReorder);
            $stmtReorder->bind_param(
                'ii',
                $productID,
                $deletedSortOrder
            );
            $stmtReorder->execute();
            $stmtReorder->close();

            $conn->commit();

            $filePath =
                '/var/www/html/uploads/products/'
                . $imageToDelete['ImageFile'];

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            header(
                'Location: /products/edit.php?id='
                . $productID
                . '&image_deleted=1'
            );
            exit;

        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }

    if (isset($_POST['add_images'])) {

    $files = $_FILES['product_images'] ?? null;

    if (
        !$files ||
        !isset($files['name']) ||
        !is_array($files['name'])
    ) {
        $error = 'Vui lòng chọn ít nhất một ảnh.';
    } else {

        $maxSize = 2 * 1024 * 1024;

        $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        $validImages = [];
        $fileCount = count($files['name']);

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < $fileCount; $i++) {

            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $error = 'Có lỗi xảy ra khi upload ảnh.';
                break;
            }

            if ($files['size'][$i] > $maxSize) {
                $error = 'Mỗi ảnh chỉ được có kích thước tối đa 2 MB.';
                break;
            }

            $mimeType = $finfo->file(
                $files['tmp_name'][$i]
            );

            if (!isset($extensionMap[$mimeType])) {
                $error = 'Chỉ chấp nhận ảnh JPG, PNG hoặc WebP.';
                break;
            }

            $extension = $extensionMap[$mimeType];

            $fileName =
                'product-' .
                bin2hex(random_bytes(8)) .
                '.' .
                $extension;

            $validImages[] = [
                'tmp_name'  => $files['tmp_name'][$i],
                'file_name' => $fileName
            ];
        }

        if (!$error && count($validImages) === 0) {
            $error = 'Vui lòng chọn ít nhất một ảnh.';
        }
    }


    /* ================================
       LẤY TRẠNG THÁI ẢNH HIỆN TẠI
       ================================ */

    if (!$error) {

        $sqlImageState = "
            SELECT
                COUNT(*) AS ImageCount,
                COALESCE(MAX(SortOrder), 0) AS MaxSortOrder
            FROM product_images
            WHERE ProductID = ?
        ";

        $stmtImageState = $conn->prepare($sqlImageState);

        if (!$stmtImageState) {
            $error = 'Lỗi SQL: ' . $conn->error;
        } else {

            $stmtImageState->bind_param(
                'i',
                $productID
            );

            $stmtImageState->execute();

            $imageState =
                $stmtImageState
                    ->get_result()
                    ->fetch_assoc();

            $stmtImageState->close();

            $imageCount =
                (int) $imageState['ImageCount'];

            $nextSortOrder =
                (int) $imageState['MaxSortOrder'] + 1;
        }
    }
    if (!$error) {

        $movedFiles = [];

        try {

            $conn->begin_transaction();

            $sqlInsertImage = "
                INSERT INTO product_images
                (
                    ProductID,
                    ImageFile,
                    AltText,
                    IsPrimary,
                    SortOrder
                )
                VALUES (?, ?, ?, ?, ?)
            ";

            $stmtInsertImage =
                $conn->prepare($sqlInsertImage);

            if (!$stmtInsertImage) {
                throw new Exception(
                    'Lỗi prepare SQL: ' . $conn->error
                );
            }

            foreach (
                $validImages
                as $index => $image
            ) {

                $destination =
                    '/var/www/html/uploads/products/' .
                    $image['file_name'];

                if (!move_uploaded_file(
                    $image['tmp_name'],
                    $destination
                )) {
                    throw new Exception(
                        'Không thể lưu một trong các ảnh.'
                    );
                }

                $movedFiles[] = $destination;
                $isPrimary =
                    (
                        $imageCount === 0 &&
                        $index === 0
                    )
                    ? 1
                    : 0;


                // SortOrder tiếp tục từ ảnh cuối.
                $sortOrder =
                    $nextSortOrder + $index;


                $altText =
                    $product['ProductName'] .
                    (
                        $isPrimary === 1
                        ? ' - ảnh chính'
                        : ' - ảnh ' . $sortOrder
                    );


                $stmtInsertImage->bind_param(
                    'issii',
                    $productID,
                    $image['file_name'],
                    $altText,
                    $isPrimary,
                    $sortOrder
                );


                if (!$stmtInsertImage->execute()) {
                    throw new Exception(
                        'Không thể lưu thông tin ảnh: ' .
                        $stmtInsertImage->error
                    );
                }
            }


            $stmtInsertImage->close();

            $conn->commit();

            header(
                'Location: /products/edit.php?id=' .
                $productID .
                '&images_added=1'
            );

            exit;

        } catch (Throwable $e) {

            $conn->rollback();

            foreach ($movedFiles as $movedFile) {

                if (file_exists($movedFile)) {
                    unlink($movedFile);
                }
            }

            $error = $e->getMessage();
        }
    }
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


    require_once '/var/www/src/includes/admin/header.php';

    require_once '/var/www/src/includes/admin/navbar.php';

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


        <form method="POST" enctype="multipart/form-data">>
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
                        <hr class="my-4">
        <h4 class="mb-3">Hình ảnh sản phẩm</h4>
         <?php if (
            isset($_GET['images_added'])
                && $_GET['images_added'] === '1'
            ): ?>
             <div class="alert alert-success">
             Đã thêm hình ảnh sản phẩm.
            </div>
            <?php endif; ?>
            <div class="mb-3">
            <label for="productImages" class="form-label">
            Thêm hình ảnh
            </label>
                <input
                    type="file"
                    class="form-control"
                    id="productImages"
                    name="product_images[]"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                >

                <div class="form-text">
                    Chấp nhận JPG, PNG hoặc WebP.
                    Mỗi ảnh tối đa 2 MB.
                </div>
            </div>
        <button
            type="submit"
            class="btn btn-outline-success"
            name="add_images"
             value="1"
            >
            Thêm ảnh
        </button>
        <div class="row">
             <?php while ($image = $productImages->fetch_assoc()): ?>
                <div class="col-md-3 mb-3">
                 <div class="card h-100">
                <img
                    src="/uploads/products/<?= htmlspecialchars($image['ImageFile']) ?>"
                    class="card-img-top"
                    alt="<?= htmlspecialchars($image['AltText'] ?? '') ?>"
                >

                <div class="card-body">
                    <small class="text-muted">
                        Thứ tự: <?= $image['SortOrder'] ?>
                    </small>

                    <?php if ((int) $image['IsPrimary'] === 1): ?>
                        <div class="mt-2">
                            <span class="badge bg-success">Ảnh chính</span>
                        </div>
                        <?php else: ?>
                            <?php if (
                            isset($_GET['image_deleted'])
                            && $_GET['image_deleted'] === '1'
                            ): ?>
                             <div class="alert alert-success">
                            Đã xóa hình ảnh sản phẩm.
                            </div>
                    <?php endif; ?>
                    <div class="mt-2">
                    <button
                        type="submit"
                        class="btn btn-outline-primary btn-sm"
                        name="set_primary_image"
                        value="<?= $image['ProductImageID'] ?>"
                        formaction="/products/edit.php?id=<?= $productID ?>"
                        formmethod="post"
                    >
                        Đặt làm ảnh chính
                    </button>
                       <button
                        type="submit"
                        class="btn btn-outline-danger btn-sm ms-2"
                        name="delete_image"
                        value="<?= $image['ProductImageID'] ?>"
                        formaction="/products/edit.php?id=<?= $productID ?>"
                        formmethod="post"
                        onclick="return confirm('Bạn có chắc muốn xóa ảnh này?');"
                    >
                        Xóa ảnh
                    </button>
                    </div>
                    <?php endif; ?>
                    <?php if (
                        isset($_GET['primary_updated'])
                        && $_GET['primary_updated'] === '1'
                    ): ?>
                        <div class="alert alert-success">
                            Đã cập nhật ảnh chính.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
         <?php endwhile; ?>
        </div>
        </form>

    </div>

    <?php

    require_once '/var/www/src/includes/admin/footer.php';

    $conn->close();

    ?>