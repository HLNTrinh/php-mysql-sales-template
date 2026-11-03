<?php

$pageTitle = 'Quản lý danh mục';

require_once '/var/www/src/config/database.php';

$sql = "
    SELECT
        CategoryID,
        CategoryName,
        Description
    FROM categories
    ORDER BY CategoryID
";

$result = $conn->query($sql);

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';

?>
<style>
    .category-page {
        padding-top: 32px;
        padding-bottom: 40px;
    }

    .category-layout {
        display: grid;
        grid-template-columns: minmax(270px, 0.8fr) minmax(0, 1.7fr);
        align-items: start;
        gap: 20px;
    }

    .category-heading {
        color: #212529;
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    .category-subtitle {
        color: #6c757d;
        font-size: 14px;
        margin-bottom: 0;
    }

    .category-card {
        min-width: 0;
        background: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
    }

    .category-form-body {
        padding: 20px 24px 24px;
    }

    .category-form-body .form-label {
        color: #495057;
        font-size: 13px;
        font-weight: 600;
    }

    .category-form-body .form-control {
        border-color: #e1e6ed;
        border-radius: 8px;
        padding: 10px 12px;
    }

    .category-form-body .form-control:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 3px rgb(13 110 253 / 12%);
    }

    .category-card-header {
        padding: 20px 24px;
        border-bottom: 1px solid #edf0f2;
        background: #ffffff;
    }

    .category-table {
        margin-bottom: 0;
        color: #343a40;
        vertical-align: middle;
    }

    .category-table thead th {
        background: #f8f9fc;
        color: #495057;
        font-size: 13px;
        font-weight: 600;
        padding: 16px 20px;
        border-bottom: 1px solid #e9ecef;
        white-space: nowrap;
    }

    .category-table tbody td {
        padding: 17px 20px;
        border-color: #f0f1f3;
        font-size: 14px;
    }

    .category-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .category-table tbody tr:hover {
        background-color: #f8faff;
    }

    .category-id {
        color: #6c757d;
        font-weight: 600;
    }

    .category-name {
        color: #212529;
        font-weight: 600;
    }

    .category-description {
        color: #6c757d;
        max-width: 420px;
    }

    .category-actions {
        white-space: nowrap;
    }

    .category-actions .btn {
        border-radius: 7px;
        padding: 6px 12px;
        font-size: 13px;
        font-weight: 500;
    }

    .btn-add-category {
        padding: 10px 16px;
        border-radius: 8px;
        font-weight: 500;
    }

    .category-empty {
        padding: 40px 20px !important;
        color: #6c757d;
        text-align: center;
    }

    @media (max-width: 767.98px) {
        .category-page {
            padding-top: 22px;
        }

        .category-layout {
            grid-template-columns: 1fr;
        }

        .category-card-header {
            padding: 16px;
        }

        .category-form-body {
            padding: 16px;
        }

        .category-table thead th,
        .category-table tbody td {
            padding: 13px 14px;
        }
    }
</style>

<div class="container category-page">

    <div class="mb-4">
        <h2 class="category-heading mb-1">Quản lý danh mục</h2>
        <p class="category-subtitle">Thêm mới và quản lý danh mục sản phẩm.</p>
    </div>

    <div class="category-layout">
        <section class="category-card">
            <div class="category-card-header">
                <h5 class="mb-0 fw-semibold text-dark">Thêm danh mục</h5>
            </div>
            <form
                action="/admin/categories/create.php"
                method="post"
                class="category-form-body"
            >
                <div class="mb-3">
                    <label for="categoryName" class="form-label">Tên danh mục</label>
                    <input
                        type="text"
                        class="form-control"
                        id="categoryName"
                        name="category_name"
                        maxlength="200"
                        placeholder="Nhập tên danh mục"
                        required
                    >
                </div>
                <div class="mb-3">
                    <label for="categoryDescription" class="form-label">Mô tả</label>
                    <textarea
                        class="form-control"
                        id="categoryDescription"
                        name="description"
                        rows="4"
                        placeholder="Thông tin mô tả danh mục"
                    ></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                    Thêm danh mục
                </button>
            </form>
        </section>

        <section class="category-card">
            <div class="category-card-header">
                <h5 class="mb-1 fw-semibold text-dark">Danh sách danh mục</h5>
                <span class="text-muted small"><?= $result->num_rows ?> danh mục</span>
            </div>
            <div class="table-responsive">
                <table class="table category-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th style="width: 190px;">Tên danh mục</th>
                            <th>Mô tả</th>
                            <th style="width: 150px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="4" class="category-empty">
                                Chưa có danh mục nào. Hãy thêm danh mục đầu tiên.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($category = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="category-id">
                                    <?= (int) $category['CategoryID'] ?>
                                </td>
                                <td class="category-name">
                                    <?= htmlspecialchars($category['CategoryName'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="category-description">
                                    <?= htmlspecialchars($category['Description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="category-actions">
                                    <a
                                        href="/admin/categories/edit.php?id=<?= (int) $category['CategoryID'] ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Sửa
                                    </a>
                                    <form
                                        action="/admin/categories/delete.php"
                                        method="post"
                                        class="d-inline"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $category['CategoryID'] ?>"
                                        >
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<?php

require_once '/var/www/src/includes/admin/footer.php';

$conn->close();