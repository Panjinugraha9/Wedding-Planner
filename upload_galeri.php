<?php
// Ganti baris ini dengan koneksi database Anda
include_once 'config.php';
$database = new Database();
$pdo = $database->getConnection();

// --- PENTING: ID VENDOR ---
// Di aplikasi nyata, Anda akan mendapatkan ID ini dari sesi login vendor.
// Untuk sekarang, kita tulis manual untuk tujuan pengetesan.
// GANTI angka 1 dengan ID vendor yang ingin Anda tes di database.
$vendor_id_saat_ini = 1; 

$pesan = '';

// --- LOGIKA UTAMA: MENANGANI FORM SUBMIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // JIKA TOMBOL "UPLOAD FOTO BARU" DIKLIK
    if (isset($_FILES['foto_baru']) && $_FILES['foto_baru']['error'] == 0) {
        
        $upload_dir = 'uploads/'; // Pastikan Anda sudah membuat folder 'uploads/'
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $nama_file_asli = basename($_FILES['foto_baru']['name']);
        $nama_file_unik = uniqid() . '-' . $nama_file_asli;
        $path_file_tujuan = $upload_dir . $nama_file_unik;

        // Pindahkan file dari temporary ke folder uploads
        if (move_uploaded_file($_FILES['foto_baru']['tmp_name'], $path_file_tujuan)) {
            
            // 1. Ambil daftar foto lama dari database
            $stmt_get = $pdo->prepare("SELECT gallery_images FROM vendor_profiles WHERE id = ?");
            $stmt_get->execute([$vendor_id_saat_ini]);
            $vendor = $stmt_get->fetch();
            $gallery_lama = json_decode($vendor['gallery_images'] ?? '[]', true);

            // 2. Tambahkan nama file baru ke daftar
            $gallery_lama[] = $nama_file_unik;

            // 3. Simpan kembali daftar baru ke database
            $stmt_update = $pdo->prepare("UPDATE vendor_profiles SET gallery_images = ? WHERE id = ?");
            $stmt_update->execute([json_encode($gallery_lama), $vendor_id_saat_ini]);

            $pesan = "Foto berhasil diunggah!";
        } else {
            $pesan = "Gagal memindahkan file yang diunggah.";
        }
    }
}

// Ambil data galeri terbaru untuk ditampilkan di bawah
$stmt_get = $pdo->prepare("SELECT gallery_images FROM vendor_profiles WHERE id = ?");
$stmt_get->execute([$vendor_id_saat_ini]);
$vendor = $stmt_get->fetch();
$galeri_sekarang = json_decode($vendor['gallery_images'] ?? '[]', true);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Upload Galeri Vendor</title>
    <style>
        body { font-family: sans-serif; padding: 40px; }
        .gallery-container { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }
        .gallery-item { border: 1px solid #ccc; padding: 10px; border-radius: 8px; text-align: center; }
        .gallery-item img { max-width: 200px; max-height: 200px; display: block; margin-bottom: 10px; }
    </style>
</head>
<body>

    <h2>Kelola Galeri Foto Anda</h2>

    <?php if ($pesan): ?>
        <p style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px;">
            <?php echo $pesan; ?>
        </p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <h3>Unggah Foto Baru</h3>
        <p>Pilih file gambar (JPG, PNG, dll.) dari komputer Anda.</p>
        <input type="file" name="foto_baru" accept="image/*" required>
        <button type="submit">Upload Foto</button>
    </form>

    <hr style="margin: 40px 0;">

    <h3>Galeri Anda Saat Ini</h3>
    <div class="gallery-container">
        <?php if (empty($galeri_sekarang)): ?>
            <p>Anda belum memiliki foto di galeri.</p>
        <?php else: ?>
            <?php foreach ($galeri_sekarang as $gambar): ?>
                <div class="gallery-item">
                    <img src="uploads/<?php echo htmlspecialchars($gambar); ?>" alt="Foto Galeri">
                    <span><?php echo htmlspecialchars($gambar); ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>