<?php

require_once '/var/www/src/config/session.php';
require_once '/var/www/src/config/database.php';

$pageTitle = 'Đăng nhập';

if (isset($_SESSION['customer_id'])) {
    header('Location: /');
    exit;
}

$email = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email =
        trim($_POST['email'] ?? '');

    $password =
        $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $errorMessage =
            'Vui lòng nhập email và mật khẩu.';

    } elseif (!filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )) {

        $errorMessage =
            'Email không hợp lệ.';

    } else {

        $sql = "
            SELECT
                CustomerID,
                CustomerName,
                Email,
                PasswordHash
            FROM customers
            WHERE Email = ?
        ";

        $stmt =
            $conn->prepare($sql);

        $stmt->bind_param(
            's',
            $email
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        $customer =
            $result->fetch_assoc();

        $result->free();
        $stmt->close();

        if ($customer
            && !empty($customer['PasswordHash'])
            && password_verify(
                $password,
                $customer['PasswordHash']
            )) {

            session_regenerate_id(true);

            $_SESSION['customer_id'] =
                (int) $customer['CustomerID'];

            $_SESSION['customer_name'] =
                $customer['CustomerName'];

            header('Location: /');
            exit;

        } else {

            $errorMessage =
                'Email hoặc mật khẩu không đúng.';
        }
    }
}

require_once '/var/www/src/includes/frontend/header.php';
require_once '/var/www/src/includes/frontend/navbar.php';

?>
<style>
    .login-page {
        min-height: 65vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 48px 16px;
        background: #f3f6fc;
    }

    .login-card form {
        margin-top: 30px;
    }

    .login-card {
        width: 100%;
        max-width: 520px;
        background: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 14px;
        padding: 36px 40px;
        box-shadow: 0 10px 35px rgba(31, 41, 55, 0.07);
    }

    .login-title {
        color: #212529;
        font-size: 36px;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .login-description {
        color: #6c757d;
        font-size: 14px;
        line-height: 1.6;
    }

    .login-card .form-label {
        color: #343a40;
        font-size: 18px;
        font-weight: 500;
        margin-bottom: 8px;
    }

    .login-card .form-control {
        min-height: 48px;
        padding: 11px 14px;
        border: 1px solid #dfe3e8;
        border-radius: 9px;
        color: #212529;
        background: #ffffff;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .login-card .form-control:focus {
        border-color: #86a9ff;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
    }

    .login-submit {
        width: 100%;
        min-height: 48px;
        margin-top: 8px;
        border-radius: 9px;
        font-size: 17px;
        font-weight: 600;
        transition: background-color 0.2s, transform 0.2s;
    }

    .login-submit:hover {
        transform: translateY(-1px);
    }

    .login-footer {
        margin-top: 24px;
        color: #212529;
        font-size: 16px;
        text-align: center;
    }

    .login-footer a {
        color: #0d6efd;
        font-weight: 700;
        text-decoration: none;
    }

    .login-footer a:hover {
        text-decoration: underline;
    }

    @media (max-width: 575.98px) {
        .login-page {
            padding: 28px 14px;
        }

        .login-card {
            padding: 28px 22px;
            border-radius: 14px;
        }

        .login-title {
            font-size: 30px;
        }
    }
</style>


    <div class="login-page">

        <div class="login-card">

            <h1 class="login-title text-center mb-4">
                Đăng nhập
            </h1>

            <?php if (isset($_GET['registered'])): ?>

                <div class="alert alert-success">
                    Đăng ký thành công.
                    Bạn có thể đăng nhập.
                </div>

            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>

            <?php endif; ?>

            <form method="post">

                <div class="mb-3">

                    <label
                        for="email"
                        class="form-label"
                    >
                        Email
                    </label>

                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                    >

                </div>

                <div class="mb-3">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Mật khẩu
                    </label>

                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary login-submit"
                >
                    Đăng nhập
                </button>

            </form>

            <p class="login-footer mb-0">
                Bạn chưa có tài khoản?
                <a href="/register.php">Đăng ký ngay</a>
            </p>

        </div>

    </div>


<?php

require_once '/var/www/src/includes/frontend/footer.php';

$conn->close();