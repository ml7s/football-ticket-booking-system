<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, full_name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        }
    } else {
        $error = 'يرجى ملء جميع الحقول';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    
    if (!empty($full_name) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            if (strlen($password) >= 6) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if (!$stmt->fetch()) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone) VALUES (?, ?, ?, ?)");
                    
                    if ($stmt->execute([$full_name, $email, $hashed_password, $phone])) {
                        $success = 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول';
                    } else {
                        $error = 'خطأ في إنشاء الحساب';
                    }
                } else {
                    $error = 'البريد الإلكتروني مستخدم بالفعل';
                }
            } else {
                $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
            }
        } else {
            $error = 'كلمات المرور غير متطابقة';
        }
    } else {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    }
}

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - حجز تذاكر المباريات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-ticket-alt me-2"></i>
                حجز تذاكر المباريات
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">الرئيسية</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                                    تسجيل الدخول
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                                    إنشاء حساب جديد
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="login" role="tabpanel">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">البريد الإلكتروني</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">كلمة المرور</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <button type="submit" name="login" class="btn btn-primary w-100">
                                        تسجيل الدخول
                                    </button>
                                </form>
                            </div>
                            
                            <div class="tab-pane fade" id="register" role="tabpanel">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="reg_email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="reg_email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">رقم الهاتف</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="05xxxxxxxx">
                                    </div>
                                    <div class="mb-3">
                                        <label for="reg_password" class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="reg_password" name="password" required>
                                        <div class="form-text">يجب أن تكون 6 أحرف على الأقل</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    <button type="submit" name="register" class="btn btn-success w-100">
                                        إنشاء حساب جديد
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/xxxxx.js" crossorigin="anonymous"></script>
</body>

</html>
