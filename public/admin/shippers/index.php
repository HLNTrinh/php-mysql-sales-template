
<?php

$pageTitle = 'Quản lý đơn vị vận chuyển';

require_once '/var/www/src/config/database.php';

$sql = "
    SELECT
        ShipperID,
        ShipperName,
        Phone
    FROM shippers
    ORDER BY ShipperID;
";

$result = $conn->query($sql);

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';

?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h2>Quản lý đơn vị vận chuyển</h2>

        <a href="/admin/shippers/create.php" class="btn btn-primary">
            Thêm đơn vị vận chuyển
        </a>

    </div>

    <div class="table-responsive">

        <table class="table table-bordered table-striped align-middle">

            <thead class="table-dark">
                <tr>
                    <th>Mã đơn vị</th>
                    <th>Tên đơn vị vận chuyển</th>
                    <th>Số điện thoại</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($result && $result->num_rows > 0): ?>

                <?php while ($shipper = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?= (int) $shipper['ShipperID'] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($shipper['ShipperName']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($shipper['Phone'] ?? '') ?>
                        </td>

                        <td>

                            <a
                                href="/admin/shippers/edit.php?id=<?= (int) $shipper['ShipperID'] ?>"
                                class="btn btn-sm btn-warning"
                            >
                                Sửa
                            </a>

                            <form
                                action="/admin/shippers/delete.php"
                                method="post"
                                class="d-inline"
                                onsubmit="return confirm('Bạn có chắc muốn xóa đơn vị vận chuyển này?');"
                            >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $shipper['ShipperID'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-danger"
                                >
                                    Xóa
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="4" class="text-center">
                        Chưa có đơn vị vận chuyển nào.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php

require_once '/var/www/src/includes/admin/footer.php';

$conn->close();

?>
