<?php
// vendor_profile.php - Form untuk mengisi profil vendor
include_once 'auth.php';
include_once 'config.php';

$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Koneksi database gagal. Silakan periksa config.php dan konfigurasi database Anda.");
}
requireRole('vendor');

$user_id = $_SESSION['user_id'];

// Cek apakah vendor sudah memiliki profil
$stmt = $pdo->prepare("SELECT * FROM vendor_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$existing_profile = $stmt->fetch();

// Tentukan status yang akan disimpan
$new_status = 'pending'; // Default untuk profil baru atau jika status sebelumnya bukan approved
if ($existing_profile && $existing_profile['status'] === 'approved') {
    $new_status = 'approved'; // Pertahankan status approved jika sudah disetujui
}

// Handle form submission
if ($_POST) {
    $company_name = $_POST['company_name'];
    $address = $_POST['address'];
    $description = $_POST['description'];
    $social_media = $_POST['social_media'];
    $whatsapp = $_POST['whatsapp'];
    $contact_email = $_POST['contact_email']; // Ambil email kontak
    $services = json_encode($_POST['services']); // Multiple services as JSON
    
    try {
        if ($existing_profile) {
            // Update existing profile
            $stmt = $pdo->prepare("
                UPDATE vendor_profiles SET 
                company_name = ?, address = ?, description = ?, social_media = ?, 
                whatsapp = ?, contact_email = ?, services = ?, status = ? -- Gunakan $new_status di sini
                WHERE user_id = ?
            ");
            $stmt->execute([
                $company_name, $address, $description, $social_media,
                $whatsapp, $contact_email, $services, $new_status, $user_id
            ]);
            $message = "Profil berhasil diperbarui!";
            if ($new_status === 'pending') {
                 $message .= " Menunggu verifikasi ulang.";
                 // Kirim notifikasi ke admin jika status berubah jadi pending
                 $stmt_notif = $pdo->prepare("
                     INSERT INTO notifications (user_id, title, message, type) 
                     SELECT id, 'Profil Vendor Diperbarui', CONCAT('Profil vendor ', ?, ' telah diperbarui dan menunggu verifikasi ulang.'), 'vendor_submission'
                     FROM users WHERE role = 'admin'
                 ");
                 $stmt_notif->execute([$company_name]);
            }
        } else {
            // Insert new profile
            $stmt = $pdo->prepare("
                INSERT INTO vendor_profiles 
                (user_id, company_name, address, description, social_media, whatsapp, contact_email, services, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending') -- Selalu pending untuk profil baru
            ");
            $stmt->execute([
                $user_id, $company_name, $address, $description, $social_media,
                $whatsapp, $contact_email, $services
            ]);
            $message = "Profil berhasil dibuat dan dikirim untuk verifikasi!";
            
            // Kirim notifikasi ke admin untuk profil baru
            $stmt_notif = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type) 
                SELECT id, 'Profil Vendor Baru', CONCAT('Ada profil vendor baru yang perlu diverifikasi dari ', ?), 'vendor_submission'
                FROM users WHERE role = 'admin'
            ");
            $stmt_notif->execute([$company_name]);
        }
        
        header("Location: vendor_dashboard.php?success=" . urlencode($message));
        exit;
        
    } catch (Exception $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Get available services for checkboxes
$stmt = $pdo->prepare("SELECT * FROM vendor_categories ORDER BY name");
$stmt->execute();
$available_services = $stmt->fetchAll();

// Parse existing services if profile exists
$selected_services = [];
if ($existing_profile && $existing_profile['services']) {
    $selected_services = json_decode($existing_profile['services'], true) ?: [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Vendor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        input, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        textarea {
            height: 100px;
            resize: vertical;
        }
        
        .form-group small {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.25rem;
            display: block;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .service-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .service-item input[type="checkbox"] {
            width: auto;
            margin-right: 0.5rem;
        }
        
        .btn {
            background: #28a745;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: #1e7e34;
        }
        
        .back-btn {
            background: #6c757d;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
            font-weight: bold;
        }
        
        .status-pending {
            background: #ffeaa7;
            color: #2d3436;
        }
        
        .status-approved {
            background: #00b894;
            color: white;
        }
        
        .status-rejected {
            background: #e17055;
            color: white;
        }
        
        .required {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="vendor_dashboard.php" class="back-btn">← Kembali ke Dashboard</a>
        
        <h1>Profil Vendor</h1>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($existing_profile): ?>
            <div class="alert">
                Status Profil: 
                <span class="status-badge status-<?php echo $existing_profile['status']; ?>">
                    <?php 
                    switch($existing_profile['status']) {
                        case 'pending': echo 'Menunggu Verifikasi'; break;
                        case 'approved': echo 'Disetujui'; break;
                        case 'rejected': echo 'Ditolak'; break;
                    }
                    ?>
                </span>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="company_name">Nama Perusahaan <span class="required">*</span></label>
                <input type="text" id="company_name" name="company_name" 
                        value="<?php echo $existing_profile ? htmlspecialchars($existing_profile['company_name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="address">Alamat <span class="required">*</span></label>
                <textarea id="address" name="address" required><?php echo $existing_profile ? htmlspecialchars($existing_profile['address']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="description">Deskripsi Perusahaan <span class="required">*</span></label>
                <textarea id="description" name="description" style="height: 120px;" required placeholder="Ceritakan tentang perusahaan Anda, pengalaman, keunggulan, dan mengapa klien harus memilih layanan Anda..."><?php echo $existing_profile ? htmlspecialchars($existing_profile['description']) : ''; ?></textarea>
                <small>Jelaskan tentang perusahaan Anda, pengalaman, dan keunggulan layanan yang ditawarkan</small>
            </div>
            
            <div class="form-group">
                <label for="whatsapp">WhatsApp <span class="required">*</span></label>
                <input type="tel" id="whatsapp" name="whatsapp" 
                        value="<?php echo $existing_profile ? htmlspecialchars($existing_profile['whatsapp']) : ''; ?>" 
                        placeholder="Contoh: 081234567890" required>
            </div>

            <div class="form-group">
                <label for="contact_email">Email Kontak <span class="required">*</span></label>
                <input type="email" id="contact_email" name="contact_email" 
                        value="<?php echo $existing_profile ? htmlspecialchars($existing_profile['contact_email']) : ''; ?>" 
                        placeholder="Contoh: email@perusahaan.com" required>
                <small>Email ini akan digunakan untuk kontak publik.</small>
            </div>
            
            <div class="form-group">
                <label for="social_media">Media Sosial</label>
                <input type="text" id="social_media" name="social_media" 
                        value="<?php echo $existing_profile ? htmlspecialchars($existing_profile['social_media']) : ''; ?>" 
                        placeholder="Contoh: @instagram_anda atau facebook.com/nama_anda">
                <small>Isi dengan akun Instagram, Facebook, atau media sosial lainnya</small>
            </div>
            
            <div class="form-group">
                <label>Layanan yang Disediakan <span class="required">*</span></label>
                <div class="services-grid">
                    <?php foreach ($available_services as $service): ?>
                        <div class="service-item">
                            <input type="checkbox" 
                                        id="service_<?php echo $service['id']; ?>" 
                                        name="services[]" 
                                        value="<?php echo $service['name']; ?>"
                                        <?php echo in_array($service['name'], $selected_services) ? 'checked' : ''; ?>>
                            <label for="service_<?php echo $service['id']; ?>"><?php echo $service['name']; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small>Pilih minimal 1 layanan yang Anda sediakan</small>
            </div>
            
            <button type="submit" class="btn">
                <?php echo $existing_profile ? 'Perbarui Profil' : 'Simpan Profil'; ?>
            </button>
        </form>
    </div>
    
    <script>
        // Validasi minimal 1 service dipilih
        document.querySelector('form').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input[name="services[]"]:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Silakan pilih minimal 1 layanan yang Anda sediakan');
            }
        });
    </script>
</body>
</html>