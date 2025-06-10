<?php
// vendor_dashboard.php - Enhanced Vendor Dashboard
include_once 'auth.php';
include_once 'config.php';

// Buat instance dari kelas Database
$database = new Database();
$pdo = $database->getConnection();

// Cek koneksi database
if (!$pdo) {
    die("Koneksi database gagal. Silakan periksa config.php dan konfigurasi database Anda.");
}

requireRole('vendor');

$user_id = $_SESSION['user_id'];

// Cek status profil vendor, termasuk kolom is_subscribed
// PASTIKAN KOLOM 'is_subscribed' ADA DI DATABASE ANDA!
$stmt = $pdo->prepare("SELECT * FROM vendor_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$vendor_profile = $stmt->fetch();

// Tentukan status langganan
$is_subscribed = ($vendor_profile && isset($vendor_profile['is_subscribed']) && $vendor_profile['is_subscribed'] == 1);

// Ambil statistik vendor jika profil sudah approved dan berlangganan
$stats = null;
if ($vendor_profile && $vendor_profile['status'] == 'approved' && $is_subscribed) { // Hanya tampilkan stats jika approved dan subscribed
    // Hitung rating rata-rata
    $stmt = $pdo->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
        FROM vendor_ratings 
        WHERE vendor_profile_id = ?
    ");
    $stmt->execute([$vendor_profile['id']]);
    $rating_stats = $stmt->fetch();
    
    $stats = [
        'avg_rating' => $rating_stats['avg_rating'] ? round($rating_stats['avg_rating'], 1) : 0,
        'total_reviews' => $rating_stats['total_reviews']
    ];
}

// Ambil notifikasi terbaru
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Hitung notifikasi yang belum dibaca
$stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetchColumn();

// Tandai notifikasi sebagai dibaca
if (isset($_GET['read_notification'])) {
    $notif_id = $_GET['read_notification'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);
    header("Location: vendor_dashboard.php");
    exit;
}

// Tandai semua notifikasi sebagai dibaca
if (isset($_GET['read_all'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: vendor_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - Wedding Planner</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .dashboard {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .notification-bell {
            position: relative;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .notification-bell:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 25px;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        
        .content {
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card.green {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card.orange {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .stat-card.blue {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .profile-status {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid #007bff;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-none {
            background: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }
        
        .payment-info {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .payment-amount {
            font-size: 2rem;
            font-weight: bold;
            color: #d63031;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-contact {
            background: rgba(255,255,255,0.9);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .phone-number {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: bold;
            background: #fff;
            padding: 12px 16px;
            border: 2px solid #007bff;
            border-radius: 8px;
            display: inline-block;
            margin-right: 15px;
            user-select: all;
            color: #007bff;
        }
        
        .copy-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .copy-btn:hover {
            background: #0056b3;
        }
        
        .main-action {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            text-align: center;
            border-radius: 15px;
            text-decoration: none;
            display: block;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .main-action:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        
        .notifications-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .read-all-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .read-all-btn:hover {
            background: #545b62;
            color: white;
        }
        
        .notification-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
            transition: transform 0.2s;
        }
        
        .notification-item:hover {
            transform: translateX(5px);
        }
        
        .notification-item.unread {
            background: #e3f2fd;
            border-left-color: #2196f3;
            box-shadow: 0 2px 15px rgba(33,150,243,0.2);
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification-time {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .mark-read-btn {
            color: #007bff;
            text-decoration: none;
            font-size: 0.8rem;
            padding: 4px 12px;
            border-radius: 15px;
            background: rgba(0,123,255,0.1);
            transition: background 0.3s;
        }
        
        .mark-read-btn:hover {
            background: rgba(0,123,255,0.2);
        }
        
        .alert {
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .rating-stars {
            color: #ffc107;
            margin-left: 10px;
        }
        
        @media (max-width: 768px) {
            .dashboard {
                margin: 10px;
                border-radius: 10px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header-actions {
                flex-direction: row;
            }
            
            .content {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .phone-number {
                display: block;
                margin-bottom: 10px;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1><i class="fas fa-tachometer-alt"></i> Vendor Dashboard</h1>
            <div class="header-actions">
                <button class="notification-bell" onclick="scrollToNotifications()">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </button>
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="content">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($vendor_profile && $vendor_profile['status'] == 'approved' && $is_subscribed && $stats): // Tampilkan stats hanya jika approved dan subscribed ?>
            <div class="stats-grid">
                <div class="stat-card green">
                    <div class="stat-number">
                        <?php echo $stats['avg_rating'] ?: '0'; ?>
                        <span class="rating-stars">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= round($stats['avg_rating']) ? '★' : '☆';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="stat-label">Rating Rata-rata</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-number"><?php echo $stats['total_reviews']; ?></div>
                    <div class="stat-label">Total Ulasan</div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-number"><?php echo $unread_count; ?></div>
                    <div class="stat-label">Notifikasi Baru</div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="profile-status">
                <h3><i class="fas fa-user-check"></i> Status Profil Vendor</h3>
                <br>
                <?php if ($vendor_profile): ?>
                    <p style="margin-bottom: 15px;">
                        Status: 
                        <span class="status-badge status-<?php echo $vendor_profile['status']; ?>">
                            <?php 
                            switch($vendor_profile['status']) {
                                case 'pending': 
                                    echo '<i class="fas fa-clock"></i> Menunggu Verifikasi'; 
                                    break;
                                case 'approved': 
                                    echo '<i class="fas fa-check-circle"></i> Disetujui'; 
                                    break;
                                case 'rejected': 
                                    echo '<i class="fas fa-times-circle"></i> Ditolak'; 
                                    break;
                            }
                            ?>
                        </span>
                        <?php if ($vendor_profile['status'] == 'approved' && $is_subscribed): ?>
                            <span class="status-badge status-approved">
                                <i class="fas fa-dollar-sign"></i> Berlangganan Aktif
                            </span>
                        <?php elseif ($vendor_profile['status'] == 'approved' && !$is_subscribed): ?>
                             <span class="status-badge status-pending">
                                <i class="fas fa-exclamation-circle"></i> Langganan Nonaktif
                            </span>
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($vendor_profile['status'] == 'approved' && $is_subscribed): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-thumbs-up"></i> Profil Anda sudah **disetujui** dan **langganan aktif**. Profil Anda kini tampil di halaman vendor untuk user!
                        </div>
                    <?php elseif ($vendor_profile['status'] == 'approved' && !$is_subscribed): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> Profil Anda sudah **disetujui**, tetapi langganan Anda **belum aktif**. Silakan aktifkan langganan untuk tampil di halaman vendor.
                        </div>
                    <?php elseif ($vendor_profile['status'] == 'pending'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-hourglass-half"></i> Profil Anda sedang dalam proses verifikasi oleh admin.
                        </div>
                    <?php elseif ($vendor_profile['status'] == 'rejected'): ?>
                        <div class="alert alert-danger">
                            <p style="font-weight: 500; margin-bottom: 10px;">
                                <i class="fas fa-exclamation-triangle"></i> Profil Anda ditolak. Silakan perbarui profil sesuai catatan admin.
                            </p>
                            <?php if ($vendor_profile['admin_notes']): ?>
                                <div style="background: rgba(220,53,69,0.1); padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;">
                                    <strong>Catatan Admin:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($vendor_profile['admin_notes'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="margin-bottom: 15px;">
                        Status: 
                        <span class="status-badge status-none">
                            <i class="fas fa-user-plus"></i> Belum Mengisi Profil
                        </span>
                    </p>
                    <p>Silakan isi profil vendor Anda untuk dapat bergabung dengan platform wedding planner kami.</p>
                <?php endif; ?>
            </div>
            
            <?php 
            // Tampilkan informasi pembayaran langganan HANYA JIKA vendor BELUM berlangganan
            if ($vendor_profile && !$is_subscribed): 
            ?>
            <div class="payment-info">
                <h3><i class="fas fa-credit-card"></i> Informasi Pembayaran Langganan</h3>
                <div class="payment-amount">
                    <i class="fas fa-money-bill-wave"></i>
                    Rp. 100.000/bulan
                </div>
                <p style="margin-bottom: 0;">Untuk mengaktifkan profil vendor dan mendapatkan akses penuh ke platform, silakan lakukan pembayaran langganan bulanan.</p>
                
                <div class="admin-contact">
                    <h4><i class="fab fa-whatsapp" style="color: #25d366;"></i> Hubungi Admin untuk Pembayaran</h4>
                    <p>Silakan hubungi admin melalui WhatsApp untuk konfirmasi pembayaran:</p>
                    <div style="display: flex; align-items: center; gap: 15px; margin: 15px 0; flex-wrap: wrap;">
                        <span class="phone-number" id="adminPhone">081353791815</span>
                        <button class="copy-btn" onclick="copyPhoneNumber()">
                            <i class="fas fa-copy"></i> Salin Nomor
                        </button>
                    </div>
                    <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                        <p style="margin: 0; font-style: italic; color: #1976d2;">
                            <i class="fas fa-comment-dots"></i> 
                            Template pesan: "Halo, saya ingin mengaktifkan langganan vendor untuk [<?php echo htmlspecialchars($vendor_profile['company_name'] ?? 'Nama Perusahaan Anda'); ?>]"
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; // Akhir dari kondisi !$is_subscribed ?>
            
            <a href="vendor_profile.php" class="main-action">
                <?php if ($vendor_profile): ?>
                    <i class="fas fa-edit"></i> Edit Profil Vendor
                <?php else: ?>
                    <i class="fas fa-plus-circle"></i> Isi Profil Vendor
                <?php endif; ?>
            </a>
            
            <?php if (!empty($notifications)): ?>
            <div class="notifications-section" id="notifications">
                <div class="notifications-header">
                    <h3><i class="fas fa-bell"></i> Notifikasi Terbaru</h3>
                    <?php if ($unread_count > 0): ?>
                        <a href="?read_all=1" class="read-all-btn">
                            <i class="fas fa-check-double"></i> Tandai Semua Dibaca
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-title">
                            <?php
                            $icon = 'fas fa-info-circle';
                            switch($notif['type']) {
                                case 'vendor_approved': $icon = 'fas fa-check-circle'; break;
                                case 'vendor_rejected': $icon = 'fas fa-times-circle'; break;
                                case 'vendor_submission': $icon = 'fas fa-paper-plane'; break;
                            }
                            ?>
                            <i class="<?php echo $icon; ?>"></i>
                            <?php echo htmlspecialchars($notif['title']); ?>
                            <?php if (!$notif['is_read']): ?>
                                <span style="background: #ff4757; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem;">BARU</span>
                            <?php endif; ?>
                        </div>
                        <p style="margin-bottom: 0;"><?php echo nl2br(htmlspecialchars($notif['message'])); ?></p>
                        <div class="notification-time">
                            <span>
                                <i class="fas fa-clock"></i>
                                <?php echo date('d M Y H:i', strtotime($notif['created_at'])); ?>
                            </span>
                            <?php if (!$notif['is_read']): ?>
                                <a href="?read_notification=<?php echo $notif['id']; ?>" class="mark-read-btn">
                                    <i class="fas fa-check"></i> Tandai Dibaca
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="notifications-section">
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h4>Belum Ada Notifikasi</h4>
                    <p>Notifikasi akan muncul di sini ketika ada update terkait profil vendor Anda.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function copyPhoneNumber() {
            const phoneNumber = document.getElementById('adminPhone').textContent;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(phoneNumber).then(function() {
                    showNotification('Nomor berhasil disalin: ' + phoneNumber, 'success');
                }).catch(function(err) {
                    console.error('Gagal menyalin: ', err);
                    fallbackCopyTextToClipboard(phoneNumber);
                });
            } else {
                fallbackCopyTextToClipboard(phoneNumber);
            }
        }
        
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showNotification('Nomor berhasil disalin: ' + text, 'success');
                } else {
                    showNotification('Gagal menyalin nomor. Silakan salin manual: ' + text, 'error');
                }
            } catch (err) {
                showNotification('Gagal menyalin nomor. Silakan salin manual: ' + text, 'error');
            }
            
            document.body.removeChild(textArea);
        }
        
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : '#dc3545'};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                z-index: 1000;
                max-width: 300px;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        
        function scrollToNotifications() {
            const notificationsSection = document.getElementById('notifications');
            if (notificationsSection) {
                notificationsSection.scrollIntoView({ behavior: 'smooth' });
            }
        }
        
        // Auto refresh untuk notifikasi baru
        setInterval(() => {
            const currentUnreadCount = <?php echo $unread_count; ?>;
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newBadge = doc.querySelector('.notification-badge');
                    const currentBadge = document.querySelector('.notification-badge');
                    
                    if (newBadge && !currentBadge) {
                        location.reload();
                    } else if (newBadge && currentBadge && newBadge.textContent !== currentBadge.textContent) {
                        location.reload();
                    }
                })
                .catch(err => console.error('Error checking notifications:', err));
        }, 30000); // Check every 30 seconds
    </script>
</body>
</html>