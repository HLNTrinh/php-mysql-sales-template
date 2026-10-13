<?php

require_once '/var/www/src/config/database.php';

// Chỉ cho phép xóa bằng phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $conn->close();
    header('Location: /admin/shippers/');
    exit;
}

// Nhận ShipperID từ form
$shipperID = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$shipperID || $shipperID <= 0) {
    $conn->close();
    header('Location: /admin/shippers/?error=invalid_id');
    exit;
}

// Kiểm tra đơn vị vận chuyển có tồn tại không
$sql = "
    SELECT ShipperID
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
    header('Location: /admin/shippers/?error=not_found');
    exit;
}

// Thực hiện xóa
$sql = "
    DELETE FROM shippers
    WHERE ShipperID = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $shipperID);

if ($stmt->execute()) {

    $stmt->close();
    $conn->close();

    header('Location: /admin/shippers/?deleted=1');
    exit;

} else {

    // Có thể không xóa được nếu đơn vị đang được sử dụng
    // trong các đơn hàng và bị ràng buộc khóa ngoại.
    $stmt->close();
    $conn->close();

    header('Location: /admin/shippers/?error=delete_failed');
    exit;
}

?>