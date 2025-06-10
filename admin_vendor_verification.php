<?php
// admin_vendor_verification.php
include_once 'auth.php';
include_once 'config.php';

requireRole('admin');

$database = new Database();
$pdo = $database->getConnection();

// Handle Subscription status update (if a request comes in)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['vendor_id'])) {
    $vendor_id = $_POST['vendor_id'];
    $action = $_POST['action']; // 'activate_subscription' or 'deactivate_subscription'

    if ($action === 'activate_subscription' || $action === 'deactivate_subscription') {
        $new_status = ($action === 'activate_subscription') ? 1 : 0;
        try {
            $stmt = $pdo->prepare("UPDATE vendor_profiles SET is_subscribed = :is_subscribed WHERE id = :id");
            $stmt->bindParam(':is_subscribed', $new_status, PDO::PARAM_INT);
            $stmt->bindParam(':id', $vendor_id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['success_message'] = "Status langganan vendor berhasil diperbarui.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Gagal memperbarui status langganan: " . $e->getMessage();
        }
    } else {
        // Handle verification actions if needed (approve/reject) - existing logic
        // ... (your existing verification logic here) ...
    }
    header('Location: admin_vendor_verification.php'); // Redirect to prevent re-submission
    exit();
}


// Handle Vendor Verification actions (existing logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vendor_id_verification'], $_POST['action_verification'])) {
    $vendor_id = $_POST['vendor_id_verification'];
    $action = $_POST['action_verification'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE vendor_profiles SET status = 'approved' WHERE id = :id");
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE vendor_profiles SET status = 'rejected' WHERE id = :id");
    }

    if (isset($stmt)) {
        $stmt->bindParam(':id', $vendor_id, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['success_message'] = "Vendor berhasil " . $action . ".";
    } else {
        $_SESSION['error_message'] = "Aksi verifikasi tidak valid.";
    }
    header('Location: admin_vendor_verification.php');
    exit();
}


// Fetch vendor profiles for verification AND subscription status
$stmt = $pdo->prepare("
    SELECT vp.*, u.username 
    FROM vendor_profiles vp 
    JOIN users u ON vp.user_id = u.id 
    ORDER BY vp.created_at DESC
");
$stmt->execute();
$vendor_profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']); // Clear messages after display
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Vendor & Langganan</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 1200px; margin: 20px auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .actions button, .actions a {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            font-size: 0.9em;
            margin-right: 5px;
        }
        .actions .approve { background-color: #28a745; }
        .actions .reject { background-color: #dc3545; }
        .actions .activate { background-color: #007bff; }
        .actions .deactivate { background-color: #ffc107; color: #333; }
        .status-badge {
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-badge.pending { background-color: #ffc107; color: #333; }
        .status-badge.approved { background-color: #28a745; color: white; }
        .status-badge.rejected { background-color: #dc3545; color: white; }
        .status-badge.subscribed { background-color: #20c997; color: white; }
        .status-badge.not-subscribed { background-color: #6c757d; color: white; }
        .back-link { display: block; margin-top: 20px; text-align: center; text-decoration: none; color: #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Kelola Vendor & Langganan</h1>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Nama Perusahaan</th>
                    <th>Nama Pengguna</th>
                    <th>Email Kontak</th>
                    <th>Status Verifikasi</th>
                    <th>Status Langganan</th>
                    <th>Tanggal Daftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($vendor_profiles) > 0): ?>
                    <?php foreach ($vendor_profiles as $vendor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vendor['company_name']); ?></td>
                            <td><?php echo htmlspecialchars($vendor['username']); ?></td>
                            <td><?php echo htmlspecialchars($vendor['contact_email']); ?></td>
                            <td>
                                <span class="status-badge <?php echo htmlspecialchars($vendor['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($vendor['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $vendor['is_subscribed'] ? 'subscribed' : 'not-subscribed'; ?>">
                                    <?php echo $vendor['is_subscribed'] ? 'Aktif' : 'Tidak Aktif'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y H:i', strtotime($vendor['created_at'])); ?></td>
                            <td class="actions">
                                <?php if ($vendor['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="vendor_id_verification" value="<?php echo $vendor['id']; ?>">
                                        <input type="hidden" name="action_verification" value="approve">
                                        <button type="submit" class="approve">Setujui</button>
                                    </form>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="vendor_id_verification" value="<?php echo $vendor['id']; ?>">
                                        <input type="hidden" name="action_verification" value="reject">
                                        <button type="submit" class="reject">Tolak</button>
                                    </form>
                                <?php else: ?>
                                    <?php endif; ?>

                                <?php if ($vendor['is_subscribed']): ?>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                        <input type="hidden" name="action" value="deactivate_subscription">
                                        <button type="submit" class="deactivate">Nonaktifkan Langganan</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                        <input type="hidden" name="action" value="activate_subscription">
                                        <button type="submit" class="activate">Aktifkan Langganan</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Tidak ada profil vendor yang ditemukan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="admin_dashboard.php" class="back-link">Kembali ke Dashboard Admin</a>
    </div>
</body>
</html>