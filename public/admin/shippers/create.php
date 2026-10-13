
<?php

$pageTitle = 'Thêm đơn vị vận chuyển';

require_once '/var/www/src/config/database.php';

$error = '';
$shipperName = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $shipperName = trim($_POST['ShipperName'] ?? '');
    $phone = trim($_POST['Phone'] ?? '');

    if ($shipperName === '') {
        $error = 'Vui lòng nhập tên đơn vị vận chuyển.';
    } elseif (
        $phone !== '' &&
        !preg_match('/^[0-9+\s().-]{8,20}$/', $phone)
    ) {
        $error = 'Số điện thoại không hợp lệ.';
    } else {

        $sql = "
            INSERT INTO shippers (ShipperName, Phone)
            VALUES (?, ?)
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = 'Lỗi SQL: ' . $conn->error;
        } else {
            $phoneValue = $phone !== '' ? $phone : null;

            $stmt->bind_param(
                'ss',
                $shipperName,
                $phoneValue
            );

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                header('Location: /admin/shippers/?success=1');
                exit;
            }

            $error = 'Không thể thêm đơn vị vận chuyển.';
            $stmt->close();
        }
    }
}

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';

?>

<div class="container mt-4">

    <h2 class="mb-3">Thêm đơn vị vận chuyển</h2>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="/admin/shippers/create.php" method="post">

                <div class="mb-3">
                    <label for="ShipperName" class="form-label">
                        Tên đơn vị vận chuyển
                    </label>

                    <input
                        type="text"
                        id="ShipperName"
                        name="ShipperName"
                        class="form-control"
                        value="<?= htmlspecialchars($shipperName, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Nhập tên đơn vị vận chuyển"
                        maxlength="255"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="Phone" class="form-label">
                        Số điện thoại
                    </label>

                    <input
                        type="tel"
                        id="Phone"
                        name="Phone"
                        class="form-control"
                        value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Nhập số điện thoại"
                        maxlength="20"
                    >
                </div>

                <button type="submit" class="btn btn-primary">
                    Thêm đơn vị vận chuyển
                </button>

                <a href="/admin/shippers/" class="btn btn-secondary">
                    Quay lại
                </a>

            </form>

        </div>
    </div>

</div>

<?php

require_once '/var/www/src/includes/admin/footer.php';

$conn->close();

?>