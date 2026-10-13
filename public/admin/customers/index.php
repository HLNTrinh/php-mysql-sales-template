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

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Quản lý khách hàng</h2>

        <a href="/admin/customers/create.php"
           class="btn btn-primary">
            <i class="bi bi-plus-circle"></i>
            Thêm khách hàng
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            Thao tác khách hàng thành công!
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Mã KH</th>
                            <th>Tên khách hàng</th>
                            <th>Người liên hệ</th>
                            <th>Địa chỉ</th>
                            <th>Thành phố</th>
                            <th>Mã bưu điện</th>
                            <th>Quốc gia</th>
                            <th style="width: 150px;">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($customer = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?= (int) $customer['CustomerID'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($customer['CustomerName'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($customer['ContactName'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($customer['Address'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($customer['City'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($customer['PostalCode'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($customer['Country'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td class="text-center">
                                        <a
                                            href="/admin/customers/edit.php?id=<?= (int) $customer['CustomerID'] ?>"
                                            class="btn btn-sm btn-warning"
                                            title="Sửa khách hàng">
                                            <i class="bi bi-pencil-square"></i>
                                            Sửa
                                        </a>

                                        <form
                                            action="/admin/customers/delete.php"
                                            method="post"
                                            class="d-inline"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng này không?');">

                                            <input
                                                type="hidden"
                                                name="CustomerID"
                                                value="<?= (int) $customer['CustomerID'] ?>">

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                                title="Xóa khách hàng">
                                                <i class="bi bi-trash"></i>
                                                Xóa
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    Chưa có khách hàng nào.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$result->free();

require_once '/var/www/src/includes/admin/footer.php';

$conn->close();