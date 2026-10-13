<?php
require_once '/var/www/src/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/customers/');
    exit;
}

$id = filter_input(
    INPUT_POST,
    'CustomerID',
    FILTER_VALIDATE_INT
);

if (!$id || $id <= 0) {
    $conn->close();
    header('Location: /admin/customers/?error=invalid_id');
    exit;
}

// Kiểm tra khách hàng có tồn tại không
$stmt = $conn->prepare("
    SELECT CustomerID
    FROM customers
    WHERE CustomerID = ?
");

$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    $conn->close();

    header('Location: /admin/customers/?error=not_found');
    exit;
}

$stmt->close();

// Xóa khách hàng
$stmt = $conn->prepare("
    DELETE FROM customers
    WHERE CustomerID = ?
");

$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    header('Location: /admin/customers/?deleted=1');
    exit;
}

$stmt->close();
$conn->close();

header('Location: /admin/customers/?error=delete_failed');
exit;
