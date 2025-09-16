<?php
require_once 'config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_ticket'])) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    $match_id = (int)$_POST['match_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($match_id > 0 && $quantity > 0) {
        $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ? AND available_tickets >= ? AND match_status = 'upcoming'");
        $stmt->execute([$match_id, $quantity]);
        $match = $stmt->fetch();
        
        if ($match) {
            $total_price = $match['ticket_price'] * $quantity;
            $booking_ref = generateBookingReference();
            
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, match_id, ticket_quantity, total_price, booking_reference) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$_SESSION['user_id'], $match_id, $quantity, $total_price, $booking_ref])) {
                $success = "تم حجز التذكرة بنجاح! رقم الحجز: " . $booking_ref;
            } else {
                $error = "خطأ في عملية الحجز";
            }
        } else {
            $error = "عذراً، لا توجد تذاكر متاحة بالكمية المطلوبة";
        }
    } else {
        $error = "بيانات غير صحيحة";
    }
}

$stmt = $pdo->prepare("SELECT * FROM matches WHERE match_status = 'upcoming' AND match_date > NOW() ORDER BY match_date ASC");
$stmt->execute();
$matches = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجز تذاكر المباريات</title>
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
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="dashboard.php">لوحة التحكم</a>
                    <a class="nav-link" href="?logout=1">تسجيل الخروج</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">تسجيل الدخول</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php
    if (isset($_GET['logout'])) {
        logout();
    }
    ?>

    <div class="container mt-4">
        <div class="hero-section text-center mb-5">
            <h1 class="display-4 mb-3">أهلاً بك في موقع حجز تذاكر المباريات</h1>
            <p class="lead">احجز تذكرتك لأهم المباريات واستمتع بتجربة لا تُنسى</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">المباريات المتاحة</h2>
            </div>
        </div>

        <div class="row">
            <?php if (empty($matches)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <h4>لا توجد مباريات متاحة حالياً</h4>
                        <p>يرجى المراجعة لاحقاً للاطلاع على المباريات الجديدة</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($matches as $match): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card match-card h-100">
                            <div class="card-header bg-primary text-white text-center">
                                <h5 class="card-title mb-0">
                                    <?php echo htmlspecialchars($match['team_home']); ?> 
                                    <span class="mx-2">VS</span>
                                    <?php echo htmlspecialchars($match['team_away']); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="match-info mb-3">
                                    <p class="mb-2">
                                        <i class="fas fa-calendar me-2"></i>
                                        <strong>التاريخ:</strong> <?php echo formatDate($match['match_date']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        <strong>الملعب:</strong> <?php echo htmlspecialchars($match['stadium']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="fas fa-money-bill me-2"></i>
                                        <strong>سعر التذكرة:</strong> 
                                        <span class="text-primary fw-bold"><?php echo formatPrice($match['ticket_price']); ?></span>
                                    </p>
                                    <p class="mb-2">
                                        <i class="fas fa-users me-2"></i>
                                        <strong>التذاكر المتاحة:</strong> 
                                        <span class="badge bg-success"><?php echo number_format($match['available_tickets']); ?></span>
                                    </p>
                                </div>
                                
                                <?php if (!empty($match['description'])): ?>
                                    <p class="text-muted small"><?php echo htmlspecialchars($match['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <?php if ($match['available_tickets'] > 0): ?>
                                    <?php if (isLoggedIn()): ?>
                                        <form method="POST" class="booking-form">
                                            <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <select name="quantity" class="form-select" required>
                                                        <option value="">عدد التذاكر</option>
                                                        <?php for ($i = 1; $i <= min(10, $match['available_tickets']); $i++): ?>
                                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <button type="submit" name="book_ticket" class="btn btn-success w-100">
                                                        احجز الآن
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-primary w-100">
                                            سجل دخولك للحجز
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        نفدت التذاكر
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/xxxxx.js" crossorigin="anonymous"></script>
</body>
</html>

