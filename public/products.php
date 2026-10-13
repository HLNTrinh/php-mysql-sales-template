<?php

require_once '/var/www/src/config/database.php';

$sql = "
    SELECT
        p.ProductID,
        p.ProductCode,
        p.ProductName,
        p.Price,
        c.CategoryName,

        (
            SELECT pi.ImageFile
            FROM product_images pi
            WHERE pi.ProductID = p.ProductID
              AND pi.IsPrimary = 1
            LIMIT 1
        ) AS ImageFile

    FROM
        products p,
        categories c

    WHERE
        p.CategoryID = c.CategoryID
        AND p.IsActive = 1

    ORDER BY
        p.ProductID DESC
";

$result = $conn->query($sql);

$pageTitle = 'Sản phẩm';

require_once '/var/www/src/includes/frontend/header.php';
require_once '/var/www/src/includes/frontend/navbar.php';
?>