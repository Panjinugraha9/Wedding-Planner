<?php
// Pastikan variabel $pdo dan $user_id sudah disediakan
if (!isset($pdo) || !isset($user_id)) {
    die("Akses tidak sah.");
}

// Inisialisasi pesan
$pesan_sukses_guest = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$pesan_error_guest = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// --- LOGIKA ---

// UPDATE STATUS KEHADIRAN (AJAX/Fetch request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $guest_id = filter_var($_POST['guest_id'], FILTER_VALIDATE_INT);
    $status_kehadiran = $_POST['status_kehadiran'];
    $allowed_statuses = ['Diundang', 'Hadir', 'Tidak Hadir'];

    if ($guest_id && in_array($status_kehadiran, $allowed_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE guests SET status_kehadiran = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$status_kehadiran, $guest_id, $user_id]);
            // Kirim respons sukses jika perlu, tapi untuk sekarang cukup hentikan eksekusi
            http_response_code(200); // Set status OK
            echo json_encode(['success' => true]); // Kirim respons JSON
        } catch (PDOException $e) { 
            http_response_code(500); // Internal Server Error
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Data tidak valid.']);
    }
    // Hentikan eksekusi script karena ini adalah request AJAX
    exit();
}


// TAMBAH TAMU BARU (Request dengan reload halaman)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_tamu'])) {
    $nama_tamu = trim($_POST['nama_tamu']);
    $nomor_telepon = trim($_POST['nomor_telepon']);

    if (empty($nama_tamu)) {
        $pesan_error_guest = "Nama tamu tidak boleh kosong.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO guests (user_id, nama_tamu, nomor_telepon) VALUES (?, ?, ?)");
            if ($stmt->execute([$user_id, $nama_tamu, $nomor_telepon])) {
                header("Location: user_dashboard.php?page=tamu&success=" . urlencode("Tamu '$nama_tamu' berhasil ditambahkan."));
                exit();
            }
        } catch (PDOException $e) {
            $pesan_error_guest = "Gagal menambahkan tamu. Error: " . $e->getMessage();
        }
    }
}

// HAPUS TAMU (Request dengan reload halaman)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_tamu'])) {
    $guest_id = filter_var($_POST['guest_id_hapus'], FILTER_VALIDATE_INT);
    if ($guest_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM guests WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$guest_id, $user_id]) && $stmt->rowCount() > 0) {
                header("Location: user_dashboard.php?page=tamu&success=" . urlencode("Tamu berhasil dihapus."));
                exit();
            }
        } catch (PDOException $e) { $pesan_error_guest = "Gagal menghapus tamu."; }
    }
}


// --- AMBIL DATA UNTUK DITAMPILKAN ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$guests = []; $total_tamu = 0; $tamu_hadir = 0; $kapasitas_maksimal = 150;

try {
    $sql_guests = "SELECT id, nama_tamu, nomor_telepon, status_kehadiran FROM guests WHERE user_id = ?";
    if (!empty($search_query)) {
        $sql_guests .= " AND nama_tamu LIKE ?";
        $params = [$user_id, "%$search_query%"];
    } else {
        $params = [$user_id];
    }
    $sql_guests .= " ORDER BY nama_tamu ASC";
    
    $stmt_guests = $pdo->prepare($sql_guests);
    $stmt_guests->execute($params);
    $guests = $stmt_guests->fetchAll(PDO::FETCH_ASSOC);

    $stmt_stats = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) as hadir FROM guests WHERE user_id = ?");
    $stmt_stats->execute([$user_id]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    $total_tamu = $stats['total'] ?? 0;
    $tamu_hadir = $stats['hadir'] ?? 0;

} catch (PDOException $e) {
    $pesan_error_guest = "Gagal memuat data tamu.";
}
?>

<div class="guest-page-container">

    <?php if (!empty($pesan_sukses_guest)): ?>
        <div class="message-success"><?php echo $pesan_sukses_guest; ?></div>
    <?php endif; ?>
    <?php if (!empty($pesan_error_guest)): ?>
        <div class="message-error"><?php echo $pesan_error_guest; ?></div>
    <?php endif; ?>
    
    <section class="guest-controls">
        <form class="add-guest-form" method="POST" action="user_dashboard.php?page=tamu">
            <input type="text" name="nama_tamu" placeholder="Nama Tamu" required>
            <input type="tel" name="nomor_telepon" placeholder="No Telp (Opsional)">
            <button type="submit" name="tambah_tamu" class="btn-add-guest">+ Tambah</button>
        </form>
        <div class="search-counter-wrapper">
            <form method="GET" action="user_dashboard.php">
                <input type="hidden" name="page" value="tamu">
                <input type="text" name="search" placeholder="Cari nama tamu..." value="<?php echo htmlspecialchars($search_query); ?>">
            </form>
            <div class="guest-counter"><?php echo $tamu_hadir; ?>/<?php echo $total_tamu; ?></div>
        </div>
    </section>

    <section class="guest-table-container">
        <table class="guest-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Nomor Telepon</th>
                    <th>Kehadiran</th>
                    <th class="action-cell"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($guests)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-light-grey);">
                        <?php echo empty($search_query) ? "Belum ada tamu yang ditambahkan." : "Tidak ada tamu yang cocok dengan pencarian '$search_query'."; ?>
                    </td></tr>
                <?php else: ?>
                    <?php foreach($guests as $guest): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($guest['nama_tamu']); ?></td>
                            <td><?php echo htmlspecialchars($guest['nomor_telepon']); ?></td>
                            <td>
                                <form class="status-form">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="guest_id" value="<?php echo $guest['id']; ?>">
                                    <?php
                                        $status_class = 'status-diundang';
                                        if ($guest['status_kehadiran'] === 'Hadir') $status_class = 'status-hadir';
                                        if ($guest['status_kehadiran'] === 'Tidak Hadir') $status_class = 'status-tidak-hadir';
                                    ?>
                                    <select name="status_kehadiran" class="<?php echo $status_class; ?>">
                                        <option value="Diundang" <?php if($guest['status_kehadiran'] == 'Diundang') echo 'selected'; ?>>Diundang</option>
                                        <option value="Hadir" <?php if($guest['status_kehadiran'] == 'Hadir') echo 'selected'; ?>>Hadir</option>
                                        <option value="Tidak Hadir" <?php if($guest['status_kehadiran'] == 'Tidak Hadir') echo 'selected'; ?>>Tidak Hadir</option>
                                    </select>
                                </form>
                            </td>
                            <td class="action-cell">
                               <form method="POST" action="user_dashboard.php?page=tamu" onsubmit="return confirm('Yakin ingin menghapus tamu ini?')">
                                   <input type="hidden" name="guest_id_hapus" value="<?php echo $guest['id']; ?>">
                                   <button type="submit" name="hapus_tamu" class="btn-delete" title="Hapus Tamu">
                                       <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path></svg>
                                   </button>
                               </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>

<script>
document.querySelectorAll('.status-form').forEach(form => {
    // Gunakan 'change' pada elemen <select> di dalam form
    const selectElement = form.querySelector('select');

    selectElement.addEventListener('change', function(event) {
        event.preventDefault(); // Mencegah perilaku default

        const formData = new FormData(form);
        const selectedStatus = this.value;

        // Kirim data di background tanpa reload halaman
        fetch('user_dashboard.php?page=tamu', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                // Jika ada error dari server, tampilkan di console
                console.error('Gagal mengupdate status di server.');
            }
            // Update warna dropdown secara langsung setelah berhasil
            this.className = ''; // Hapus semua class warna lama
            if (selectedStatus === 'Hadir') {
                this.classList.add('status-hadir');
            } else if (selectedStatus === 'Tidak Hadir') {
                this.classList.add('status-tidak-hadir');
            } else {
                this.classList.add('status-diundang');
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
</script>