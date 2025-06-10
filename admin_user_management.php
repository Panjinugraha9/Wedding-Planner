<?php

// admin_user_management.php - User Management

include_once 'auth.php';
include_once 'config.php';

// Buat instance dari kelas Database
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Koneksi database gagal. Silakan periksa config.php dan konfigurasi database Anda.");
}

requireRole('admin');

// Handle actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    
    if ($action && $user_id) {
        switch ($action) {
            case 'activate':
                $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = "User berhasil diaktifkan!";
                $message_type = "success";
                break;
                
            case 'deactivate':
                $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = "User berhasil dinonaktifkan!";
                $message_type = "warning";
                break;
                
            case 'delete':
                // Delete user and related data
                $pdo->beginTransaction();
                try {
                    // Delete vendor profile if exists
                    $stmt = $pdo->prepare("DELETE FROM vendor_profiles WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete user ratings
                    $stmt = $pdo->prepare("DELETE FROM vendor_ratings WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete notifications
                    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    $pdo->commit();
                    $message = "User berhasil dihapus!";
                    $message_type = "success";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $message = "Gagal menghapus user: " . $e->getMessage();
                    $message_type = "error";
                }
                break;
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_role = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($filter_status !== 'all') {
    $whereConditions[] = "u.status = ?";
    $params[] = $filter_status;
}

if ($filter_role !== 'all') {
    $whereConditions[] = "u.role = ?";
    $params[] = $filter_role;
}

if (!empty($search)) {
    $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get users - SIMPLIFIED VERSION WITHOUT ACTIVITY STATUS
$query = "
    SELECT 
        u.id,
        u.username,
        u.email,
        u.role,
        COALESCE(u.status, 'active') as status,
        u.created_at,
        u.last_login
    FROM users u
    WHERE u.role = 'user'
    " . ($whereClause ? " AND " . str_replace("WHERE ", "", $whereClause) : "") . "
    ORDER BY u.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get statistics (only for regular users)
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN COALESCE(status, 'active') = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
    FROM users 
    WHERE role = 'user'
";
$stmt = $pdo->prepare($stats_query);
$stmt->execute();
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User Biasa - Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        
        .container {
            max-width: 1400px;
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
        
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stats-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .stat-card {
            flex: 1;
            min-width: 200px;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            color: white;
        }
        
        .stat-card.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.active { background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%); }
        .stat-card.inactive { background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%); }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .filters {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .filter-group label {
            font-size: 0.9rem;
            font-weight: bold;
            color: #495057;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            min-width: 150px;
        }
        
        .filter-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 1.5rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .users-table th,
        .users-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .users-table th {
            background: #f8f9fa;
            font-weight: bold;
            position: sticky;
            top: 0;
        }
        
        .users-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn.activate {
            background: #28a745;
            color: white;
        }
        
        .btn.deactivate {
            background: #ffc107;
            color: #212529;
        }
        
        .btn.delete {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 1rem;
        }
        
        .role-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .role-badge.user {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .role-badge.vendor {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .role-badge.admin {
            background: #ffebee;
            color: #c62828;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Kelola User Biasa</h1>
            <a href="admin_dashboard.php" class="back-btn">
                ← Kembali ke Dashboard
            </a>
        </div>
        
        <div class="stats-row">
            <div class="stat-card total">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div>Total User Biasa</div>
            </div>
            <div class="stat-card active">
                <div class="stat-number"><?php echo $stats['active']; ?></div>
                <div>Aktif</div>
            </div>
            <div class="stat-card inactive">
                <div class="stat-number"><?php echo $stats['inactive']; ?></div>
                <div>Non-aktif</div>
            </div>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="filters">
            <form method="GET">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Status:</label>
                        <select name="status">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Semua</option>
                            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Non-aktif</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Role:</label>
                        <select name="role" disabled>
                            <option value="user" selected>User Biasa</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Cari:</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Username atau Email">
                    </div>
                    
                    <button type="submit" class="filter-btn">Filter</button>
                </div>
            </form>
        </div>
        
        <div class="table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Terakhir Login</th>
                        <th>Terdaftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="no-data">
                                Tidak ada user biasa yang ditemukan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge user">
                                        User
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($user['last_login']): 
                                        echo date('d/m/Y H:i', strtotime($user['last_login']));
                                    else:
                                        echo '<span style="color: #999;">Belum pernah</span>';
                                    endif;
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <?php if ($user['status'] === 'active'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menonaktifkan user ini?')">
                                                <input type="hidden" name="action" value="deactivate">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn deactivate">Non-aktifkan</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin mengaktifkan user ini?')">
                                                <input type="hidden" name="action" value="activate">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn activate">Aktifkan</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('PERINGATAN: Tindakan ini akan menghapus user dan semua data terkait secara permanen. Yakin ingin melanjutkan?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn delete">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Auto-submit form on filter change (optional enhancement)
        document.addEventListener('DOMContentLoaded', function() {
            const filterSelects = document.querySelectorAll('.filter-group select');
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    // Optionally auto-submit form when filters change
                    // this.form.submit();
                });
            });
        });
    </script>
</body>
</html>