<?php
// admin_dashboard.php - Admin Dashboard
include_once 'auth.php';
include_once 'config.php'; // Ini meng-include definisi kelas Database

// ------ PERUBAHAN DIMULAI DI SINI ------

// Buat instance dari kelas Database
$database = new Database();
// Dapatkan koneksi PDO dari instance tersebut
$pdo = $database->getConnection();

// Cek apakah koneksi berhasil didapatkan
if (!$pdo) {
    // Jika koneksi gagal, hentikan eksekusi dan tampilkan pesan error
    die("Koneksi database gagal. Silakan periksa config.php dan konfigurasi database Anda.");
}

// ------ PERUBAHAN BERAKHIR DI SINI ------

requireRole('admin');

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND status = 'active'");
$stmt->execute();
$total_users = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'vendor' AND status = 'active'");
$stmt->execute();
$total_vendors = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM vendor_profiles WHERE status = 'pending'");
$stmt->execute();
$pending_verifications = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM vendor_profiles WHERE status = 'approved'");
$stmt->execute();
$approved_vendors = $stmt->fetch()['total'];

// Get inactive users count (haven't logged in for 3+ months)
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE last_login < DATE_SUB(NOW(), INTERVAL 3 MONTH) OR last_login IS NULL");
$stmt->execute();
$inactive_users = $stmt->fetch()['total'];

// Get recent notifications/activities
$stmt = $pdo->prepare("
    SELECT vp.company_name, vp.created_at, u.username 
    FROM vendor_profiles vp 
    JOIN users u ON vp.user_id = u.id 
    WHERE vp.status = 'pending' 
    ORDER BY vp.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_submissions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        
        .dashboard {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-card.users {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card.vendors {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card.pending {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #333;
        }
        
        .stat-card.approved {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }
        
        .stat-card.inactive {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #333;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .admin-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .menu-item {
            background: #fff;
            border: 1px solid #e9ecef;
            padding: 2rem;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .menu-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            text-decoration: none;
            color: #333;
        }
        
        .menu-item .icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .menu-item h3 {
            margin: 0.5rem 0;
            font-size: 1.2rem;
        }
        
        .menu-item p {
            margin: 0.5rem 0 0 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .menu-item.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .menu-item.primary:hover {
            color: white;
        }
        
        .menu-item.primary p {
            color: rgba(255,255,255,0.8);
        }
        
        .menu-item.info {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
        }
        
        .menu-item.info:hover {
            color: white;
        }
        
        .menu-item.info p {
            color: rgba(255,255,255,0.8);
        }
        
        .recent-activity {
            margin-top: 2rem;
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
        }
        
        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-text {
            font-size: 0.9rem;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .notification-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            position: absolute;
            top: 10px;
            right: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1>Admin Dashboard</h1>
            <div>
                <span>Welcome, <?php echo $_SESSION['username']; ?>!</span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card users">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users Aktif</div>
            </div>
            <div class="stat-card vendors">
                <div class="stat-number"><?php echo $total_vendors; ?></div>
                <div class="stat-label">Total Vendors Aktif</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number"><?php echo $pending_verifications; ?></div>
                <div class="stat-label">Pending Verifications</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-number"><?php echo $approved_vendors; ?></div>
                <div class="stat-label">Approved Vendors</div>
            </div>
            <div class="stat-card inactive">
                <div class="stat-number"><?php echo $inactive_users; ?></div>
                <div class="stat-label">Users Tidak Aktif (3+ Bulan)</div>
            </div>
        </div>
        
        <div class="content">
            <h2>Menu Administrasi</h2>
            
            <div class="admin-menu">
                <a href="admin_vendor_verification.php" class="menu-item primary">
                    <span class="icon">🔍</span>
                    <h3>Verifikasi & Langganan Vendor</h3>
                    <p>Kelola verifikasi dan status langganan vendor</p>
                    <?php if ($pending_verifications > 0): ?>
                        <span class="notification-badge"><?php echo $pending_verifications; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="admin_user_management.php" class="menu-item info">
                    <span class="icon">👥</span>
                    <h3>Kelola Users</h3>
                    <p>Manajemen pengguna, aktifasi/nonaktifkan akun, dan hapus akun tidak aktif</p>
                    <?php if ($inactive_users > 0): ?>
                        <span class="notification-badge"><?php echo $inactive_users; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        
        <?php if (!empty($recent_submissions)): ?>
        <div class="recent-activity">
            <h3>Aktivitas Terbaru</h3>
            <p style="margin-bottom: 1rem; color: #6c757d;">Vendor yang baru mendaftar dan perlu verifikasi:</p>
            
            <?php foreach ($recent_submissions as $submission): ?>
                <div class="activity-item">
                    <div class="activity-text">
                        <strong><?php echo htmlspecialchars($submission['company_name']); ?></strong> 
                        oleh <?php echo htmlspecialchars($submission['username']); ?>
                    </div>
                    <div class="activity-time">
                        <?php echo date('d M Y H:i', strtotime($submission['created_at'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div style="margin-top: 1rem; text-align: center;">
                <a href="admin_vendor_verification.php" style="color: #007bff; text-decoration: none;">
                    Lihat Semua Verifikasi →
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>