<?php
$pageTitle = 'Sửa khách hàng';

require_once '/var/www/src/config/database.php';

$error = '';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: /admin/customers/');
    exit;
}

// Lấy thông tin khách hàng
$stmt = $conn->prepare("
    SELECT CustomerID, CustomerName, ContactName,
           Address, City, PostalCode, Country
    FROM customers
    WHERE CustomerID = ?
");

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    $conn->close();
    header('Location: /admin/customers/');
    exit;
}

$customerName = $customer['CustomerName'];
$contactName = $customer['ContactName'] ?? '';
$address = $customer['Address'] ?? '';
$city = $customer['City'] ?? '';
$postalCode = $customer['PostalCode'] ?? '';
$country = $customer['Country'] ?? '';

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
        $stmt = $conn->prepare("
            UPDATE customers
            SET CustomerName = ?,
                ContactName = ?,
                Address = ?,
                City = ?,
                PostalCode = ?,
                Country = ?
            WHERE CustomerID = ?
        ");

        $stmt->bind_param(
            'ssssssi',
            $customerName,
            $contactName,
            $address,
            $city,
            $postalCode,
            $country,
            $id
        );

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();

            header('Location: /admin/customers/?success=1');
            exit;
        }

        $error = 'Lỗi cập nhật khách hàng: ' . $stmt->error;
        $stmt->close();
    }
}

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';
?>

<div class="container-fluid mt-4">
    <h2 class="mb-3">Sửa thông tin khách hàng</h2>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="/admin/customers/edit.php?id=<?= (int) $id ?>"
                  method="post">

                <div class="mb-3">
                    <label class="form-label" for="CustomerName">
                        Tên khách hàng *
                    </label>
                    <input type="text" class="form-control"
                           id="CustomerName" name="CustomerName"
                           maxlength="100" required
                           value="<?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="ContactName">
                        Người liên hệ
                    </label>
                    <input type="text" class="form-control"
                           id="ContactName" name="ContactName"
                           maxlength="100"
                           value="<?= htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="Address">Địa chỉ</label>
                    <input type="text" class="form-control"
                           id="Address" name="Address"
                           maxlength="200"
                           value="<?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="City">Thành phố</label>
                        <input type="text" class="form-control"
                               id="City" name="City" maxlength="100"
                               value="<?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="PostalCode">
                            Mã bưu điện
                        </label>
                        <input type="text" class="form-control"
                               id="PostalCode" name="PostalCode"
                               maxlength="20"
                               value="<?= htmlspecialchars($postalCode, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="Country">Quốc gia</label>
                        <input type="text" class="form-control"
                               id="Country" name="Country"
                               maxlength="100"
                               value="<?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    Lưu thay đổi
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
