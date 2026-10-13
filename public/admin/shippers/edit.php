<?php

$pageTitle = 'Sửa đơn vị vận chuyển';

require_once '/var/www/src/config/database.php';

$error = '';
$shipperName = '';
$phone = '';

// Lấy ShipperID từ URL
$shipperID = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Khi gửi form POST, lấy ID từ form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipperID = filter_input(INPUT_POST, 'ShipperID', FILTER_VALIDATE_INT);
}

if (!$shipperID || $shipperID <= 0) {
    $conn->close();
    header('Location: /admin/shippers/');
    exit;
}

// Xử lý cập nhật
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
            UPDATE shippers
            SET ShipperName = ?, Phone = ?
            WHERE ShipperID = ?
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $phoneValue = $phone !== '' ? $phone : null;

            $stmt->bind_param(
                'ssi',
                $shipperName,
                $phoneValue,
                $shipperID
            );

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();

                header('Location: /admin/shippers/');
                exit;
            }

            $error = 'Không thể cập nhật đơn vị vận chuyển.';
            $stmt->close();
        } else {
            $error = 'Lỗi SQL: ' . $conn->error;
        }
    }

} else {

    // Lấy thông tin hiện tại để hiển thị lên form
    $sql = "
        SELECT ShipperName, Phone
        FROM shippers
        WHERE ShipperID = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $shipperID);
    $stmt->execute();

    $result = $stmt->get_result();
    $shipper = $result->fetch_assoc();

    $stmt->close();

    if (!$shipper) {
        $conn->close();
        header('Location: /admin/shippers/');
        exit;
    }

    $shipperName = $shipper['ShipperName'];
    $phone = $shipper['Phone'] ?? '';
}

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';

?>

<div class="container mt-4">

    <h2 class="mb-3">Sửa đơn vị vận chuyển</h2>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="/admin/shippers/edit.php" method="post">

                <input
                    type="hidden"
                    name="ShipperID"
                    value="<?= (int) $shipperID ?>"
                >

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
                        maxlength="20
"
                    >
                </div>

                <button type="submit" class="btn btn-primary">
                    Lưu thay đổi
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