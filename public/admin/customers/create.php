<?php
$pageTitle = 'Thêm khách hàng';

require_once '/var/www/src/config/database.php';

$error = '';
$customerName = '';
$contactName = '';
$address = '';
$city = '';
$postalCode = '';
$country = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['CustomerName'] ?? '');
    $contactName = trim($_POST['ContactName'] ?? '');
    $address = trim($_POST['Address'] ?? '');
    $city = trim($_POST['City'] ?? '');
    $postalCode = trim($_POST['PostalCode'] ?? '');
    $country = trim($_POST['Country'] ?? '');

    if ($customerName === '') {
        $error = 'Vui lòng nhập tên khách hàng.';
    } else {
        $sql = "
            INSERT INTO customers
                (CustomerName, ContactName, Address, City, PostalCode, Country)
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = 'Không thể chuẩn bị truy vấn: ' . $conn->error;
        } else {
            $stmt->bind_param(
                'ssssss',
                $customerName,
                $contactName,
                $address,
                $city,
                $postalCode,
                $country
            );

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();

                header('Location: /admin/customers/?success=1');
                exit;
            } else {
                $error = 'Thêm khách hàng thất bại: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Thêm khách hàng</h2>

        <a href="/admin/customers/" class="btn btn-secondary">
            Quay lại
        </a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="/admin/customers/create.php" method="post">
                <div class="mb-3">
                    <label for="CustomerName" class="form-label">
                        Tên khách hàng <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        class="form-control"
                        id="CustomerName"
                        name="CustomerName"
                        maxlength="100"
                        value="<?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label for="ContactName" class="form-label">
                        Người liên hệ
                    </label>
                    <input
                        type="text"
                        class="form-control"
                        id="ContactName"
                        name="ContactName"
                        maxlength="100"
                        value="<?= htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label for="Address" class="form-label">Địa chỉ</label>
                    <input
                        type="text"
                        class="form-control"
                        id="Address"
                        name="Address"
                        maxlength="200"
                        value="<?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="City" class="form-label">Thành phố</label>
                        <input
                            type="text"
                            class="form-control"
                            id="City"
                            name="City"
                            maxlength="100"
                            value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="PostalCode" class="form-label">
                            Mã bưu điện
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="PostalCode"
                            name="PostalCode"
                            maxlength="20"
                            value="<?= htmlspecialchars($postalCode, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="Country" class="form-label">Quốc gia</label>
                        <input
                            type="text"
                            class="form-control"
                            id="Country"
                            name="Country"
                            maxlength="100"
                            value="<?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i>
                    Lưu khách hàng
                </button>

                <a href="/admin/customers/" class="btn btn-secondary">
                    Hủy
                </a>
            </form>
        </div>
    </div>
</div>

<?php
require_once '/var/www/src/includes/admin/footer.php';
$conn->close();
