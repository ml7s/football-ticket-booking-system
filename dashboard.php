<?php
require_once 'config.php';
requireLogin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? AND booking_status = 'confirmed'");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch();
    
    if ($booking) {
        $stmt = $pdo->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?");
        if ($stmt->execute([$booking_id])) {
            $success = "تم إلغاء الحجز بنجاح";
        } else {
            $error = "خطأ في إلغاء الحجز";
        }
    } else {
        $error = "الحجز غير موجود أو تم إلغاؤه مسبقاً";
    }
}

$bookings_per_page = isset($_GET['per_page']) ? max(5, min(50, (int)$_GET['per_page'])) : 10;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $bookings_per_page;

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_bookings_count = $stmt->fetch()['total'];
$total_pages = ceil($total_bookings_count / $bookings_per_page);

$stmt = $pdo->prepare("
    SELECT b.*, m.team_home, m.team_away, m.match_date, m.stadium 
    FROM bookings b 
    JOIN matches m ON b.match_id = m.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
    LIMIT " . (int)$bookings_per_page . " OFFSET " . (int)$offset
);
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) as total_bookings, SUM(total_price) as total_spent FROM bookings WHERE user_id = ? AND booking_status = 'confirmed'");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

function buildPaginationUrl($page, $per_page) {
    $params = [];
    if ($page > 1) $params['page'] = $page;
    if ($per_page != 10) $params['per_page'] = $per_page;
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - حجز تذاكر المباريات</title>
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
                <a class="nav-link active" href="dashboard.php">لوحة التحكم</a>
                <a class="nav-link" href="?logout=1">تسجيل الخروج</a>
            </div>
        </div>
    </nav>

    <?php
    if (isset($_GET['logout'])) {
        logout();
    }
    ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">مرحباً <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
            </div>
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

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>إجمالي الحجوزات</h4>
                                <h2><?php echo $stats['total_bookings'] ?: 0; ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-ticket-alt fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>إجمالي المبلغ المدفوع</h4>
                                <h2><?php echo formatPrice($stats['total_spent'] ?: 0); ?></h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-money-bill fa-3x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">حجوزاتي</h3>
                        <?php if ($total_bookings_count > 5): ?>
                            <div class="d-flex align-items-center">
                                <label for="per_page" class="form-label mb-0 me-2">عرض:</label>
                                <select class="form-select form-select-sm" id="per_page" onchange="changePerPage(this.value)" style="width: auto;">
                                    <option value="5" <?php echo $bookings_per_page == 5 ? 'selected' : ''; ?>>5</option>
                                    <option value="10" <?php echo $bookings_per_page == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="20" <?php echo $bookings_per_page == 20 ? 'selected' : ''; ?>>20</option>
                                    <option value="50" <?php echo $bookings_per_page == 50 ? 'selected' : ''; ?>>50</option>
                                </select>
                                <span class="ms-2 small text-muted">حجز</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bookings)): ?>
                            <?php if ($total_bookings_count > 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-search fa-4x text-muted mb-3"></i>
                                    <h4>لا توجد حجوزات في هذه الصفحة</h4>
                                    <p class="text-muted">
                                        لديك <?php echo $total_bookings_count; ?> حجز إجمالي، جرب تغيير عدد العناصر في الصفحة أو العودة للصفحة الأولى
                                    </p>
                                    <a href="<?php echo buildPaginationUrl(1, $bookings_per_page); ?>" class="btn btn-primary">العودة للصفحة الأولى</a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                                    <h4>لا توجد حجوزات</h4>
                                    <p class="text-muted">لم تقم بأي حجوزات بعد</p>
                                    <a href="index.php" class="btn btn-primary">تصفح المباريات</a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>رقم الحجز</th>
                                            <th>المباراة</th>
                                            <th>التاريخ</th>
                                            <th>الملعب</th>
                                            <th>عدد التذاكر</th>
                                            <th>المبلغ</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الحجز</th>
                                            <th>العمليات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td class="fw-bold text-primary"><?php echo $booking['booking_reference']; ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($booking['team_home']); ?>
                                                    <small class="text-muted">VS</small>
                                                    <?php echo htmlspecialchars($booking['team_away']); ?>
                                                </td>
                                                <td><?php echo formatDate($booking['match_date']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['stadium']); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $booking['ticket_quantity']; ?></span>
                                                </td>
                                                <td class="fw-bold"><?php echo formatPrice($booking['total_price']); ?></td>
                                                <td>
                                                    <?php if ($booking['booking_status'] == 'confirmed'): ?>
                                                        <span class="badge bg-success">مؤكد</span>
                                                    <?php elseif ($booking['booking_status'] == 'cancelled'): ?>
                                                        <span class="badge bg-danger">ملغي</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">في الانتظار</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($booking['booking_status'] == 'confirmed' && strtotime($booking['match_date']) > time()): ?>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الحجز؟');">
                                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                            <button type="submit" name="cancel_booking" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-times"></i> إلغاء
                                                            </button>
                                                        </form>
                                                    <?php elseif (strtotime($booking['match_date']) <= time()): ?>
                                                        <small class="text-muted">انتهت المباراة</small>
                                                    <?php else: ?>
                                                        <small class="text-muted">تم الإلغاء</small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div>
                                    <p class="mb-0 text-muted">
                                        عرض <?php echo $offset + 1; ?> إلى <?php echo min($offset + $bookings_per_page, $total_bookings_count); ?> 
                                        من أصل <?php echo $total_bookings_count; ?> حجز
                                    </p>
                                </div>
                                <nav aria-label="pagination">
                                    <ul class="pagination mb-0">
                                        <?php if ($current_page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo buildPaginationUrl($current_page - 1, $bookings_per_page); ?>">
                                                    <i class="fas fa-chevron-right"></i> السابق
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $start_page = max(1, $current_page - 2);
                                        $end_page = min($total_pages, $current_page + 2);
                                        
                                        if ($start_page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo buildPaginationUrl(1, $bookings_per_page); ?>">1</a>
                                            </li>
                                            <?php if ($start_page > 2): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                                <a class="page-link" href="<?php echo buildPaginationUrl($i, $bookings_per_page); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($end_page < $total_pages): ?>
                                            <?php if ($end_page < $total_pages - 1): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo buildPaginationUrl($total_pages, $bookings_per_page); ?>"><?php echo $total_pages; ?></a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php if ($current_page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo buildPaginationUrl($current_page + 1, $bookings_per_page); ?>">
                                                    التالي <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/406ccee5fc.js" crossorigin="anonymous"></script>
    <script>
        function changePerPage(perPage) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', perPage);
            url.searchParams.set('page', 1);
            window.location = url;
        }
    </script>
</body>
</html>