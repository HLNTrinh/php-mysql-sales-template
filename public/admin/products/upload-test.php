<?php
$pageTitle = 'Kiểm tra Upload File';
require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';
?>

<div class="container mt-4">

    <h2>Kiểm tra Upload File</h2>

    <form method="post" enctype="multipart/form-data">
                    <?php

            if (
                isset($_FILES['product_image'])
                && $_FILES['product_image']['error'] === UPLOAD_ERR_OK
            ) {

                $file = $_FILES['product_image'];
                $maxSize = 2 * 1024 * 1024;

                if ($file['size'] > $maxSize) {
                    die('File ảnh không được vượt quá 2 MB.');
                }
                $originalName = basename($file['name']);

                $destination =
                    '/var/www/html/uploads/products/' . $originalName;

                if (move_uploaded_file(
                    $file['tmp_name'],
                    $destination
                )) {
                    echo '<div class="alert alert-success mt-3">';
                    echo 'Upload file thành công.';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-danger mt-3">';
                    echo 'Không thể lưu file.';
                    echo '</div>';
                }
            }
            ?>

        <div class="mb-3">
            <label for="productImage" class="form-label">
                Chọn ảnh
            </label>

            <input
                type="file"
                class="form-control"
                id="productImage"
                name="product_image"
                accept="image/*"
            >
        </div>

        <button type="submit" class="btn btn-primary">
            Gửi file
        </button>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>

        <hr>
        <h4>Dữ liệu nhận được trong $_FILES</h4>
        <pre><?php print_r($_FILES); ?></pre>

    <?php endif; ?>

</div>

<?php
require_once '/var/www/src/includes/admin/footer.php';