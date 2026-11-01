<?php

require_once '/var/www/src/config/session.php';
require_once '/var/www/src/config/database.php';

$pageTitle = 'Đăng ký tài khoản';

$customerName = '';
$phone = '';
$email = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customerName =
        trim($_POST['customer_name'] ?? '');

    $phone =
        trim($_POST['phone'] ?? '');

    $email =
        trim($_POST['email'] ?? '');

    $password =
        $_POST['password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';

    if ($customerName === ''
        || $email === ''
        || $password === ''
        || $confirmPassword === '') {

        $errorMessage =
            'Vui lòng nhập đầy đủ thông tin bắt buộc.';

    } elseif (!filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )) {

        $errorMessage =
            'Email không hợp lệ.';

    } elseif (strlen($password) < 6) {

        $errorMessage =
            'Mật khẩu phải có ít nhất 6 ký tự.';

    } elseif ($password !== $confirmPassword) {

        $errorMessage =
            'Mật khẩu xác nhận không khớp.';

    } else {

        $sqlCheck = "
            SELECT CustomerID
            FROM customers
            WHERE Email = ?
        ";

        $stmtCheck =
            $conn->prepare($sqlCheck);

        $stmtCheck->bind_param(
            's',
            $email
        );

        $stmtCheck->execute();

        $resultCheck =
            $stmtCheck->get_result();

        $existingCustomer =
            $resultCheck->fetch_assoc();

        $resultCheck->free();
        $stmtCheck->close();

        if ($existingCustomer) {

            $errorMessage =
                'Email này đã được sử dụng.';

        } else {

            $passwordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

            $sqlInsert = "
                INSERT INTO customers (
                    CustomerName,
                    Phone,
                    Email,
                    PasswordHash
                )
                VALUES (?, ?, ?, ?)
            ";

            $stmtInsert =
                $conn->prepare($sqlInsert);

            $stmtInsert->bind_param(
                'ssss',
                $customerName,
                $phone,
                $email,
                $passwordHash
            );

            $stmtInsert->execute();
            $stmtInsert->close();

            header(
                'Location: /login.php?registered=1'
            );
            exit;
        }
    }
}

require_once '/var/www/src/includes/frontend/header.php';
require_once '/var/www/src/includes/frontend/navbar.php';

?>

<style>
    .register-page {
        display: flex;
        min-height: 75vh;
        align-items: center;
        justify-content: center;
        padding: 48px 16px;
        background: #f3f6fc;
    }

    .register-card {
        width: 100%;
        max-width: 520px;
        padding: 36px 40px;
        border: 1px solid #e9ecef;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 10px 35px rgba(31, 41, 55, 0.07);
    }

    .register-title {
        margin-bottom: 28px;
        color: #212529;
        font-size: 36px;
        font-weight: 800;
        text-align: center;
        text-transform: uppercase;
    }

    .register-card form {
        margin-top: 24px;
    }

    .register-card .form-label {
        margin-bottom: 8px;
        color: #343a40;
        font-size: 18px;
        font-weight: 500;
    }

    .register-card .form-control {
        min-height: 48px;
        padding: 11px 14px;
        border: 1px solid #dfe3e8;
        border-radius: 9px;
        color: #212529;
        background: #fff;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .register-card .form-control:focus {
        border-color: #86a9ff;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
    }

    .register-submit {
        width: 100%;
        min-height: 48px;
        margin-top: 8px;
        border-radius: 9px;
        font-size: 17px;
        font-weight: 600;
        transition: background-color 0.2s, transform 0.2s;
    }

    .register-submit:hover {
        transform: translateY(-1px);
    }

    .register-footer {
        margin-top: 24px;
        color: #212529;
        font-size: 16px;
        text-align: center;
    }

    .register-footer a {
        color: #0d6efd;
        font-weight: 700;
        text-decoration: none;
    }

    .register-footer a:hover {
        text-decoration: underline;
    }

    @media (max-width: 575.98px) {
        .register-page {
            padding: 28px 14px;
        }

        .register-card {
            padding: 28px 22px;
        }

        .register-title {
            font-size: 30px;
        }
    }
</style>

<main class="register-page">
    <section class="register-card" aria-labelledby="register-title">
        <h1 class="register-title" id="register-title">
            Đăng ký
        </h1>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="customer_name" class="form-label">Họ và tên</label>
                <input
                    type="text"
                    class="form-control"
                    id="customer_name"
                    name="customer_name"
                    value="<?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input
                    type="text"
                    class="form-control"
                    id="phone"
                    name="phone"
                    value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"
                >
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mật khẩu</label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                <input
                    type="password"
                    class="form-control"
                    id="confirm_password"
                    name="confirm_password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary register-submit">
                Đăng ký
            </button>
        </form>

        <p class="register-footer mb-0">
            Đã có tài khoản?
            <a href="/login.php">Đăng nhập</a>
        </p>
    </section>
</main>

<?php

require_once '/var/www/src/includes/frontend/footer.php';

$conn->close();