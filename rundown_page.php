<?php
// Pastikan variabel $pdo dan $user_id sudah disediakan
if (!isset($pdo) || !isset($user_id)) { die("Akses tidak sah."); }

// Menentukan aksi: 'list' (default), 'add', atau 'edit'
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$item_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

$pesan_sukses_rundown = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$pesan_error_rundown = '';

// --- LOGIKA ---

// PROSES TAMBAH ATAU EDIT AGENDA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['tambah_agenda']) || isset($_POST['edit_agenda']))) {
    $waktu = $_POST['waktu'];
    $agenda = trim($_POST['agenda']);
    $lokasi = trim($_POST['lokasi']);
    $deskripsi = trim($_POST['deskripsi']);

    if (empty($waktu) || empty($agenda)) {
        $pesan_error_rundown = "Waktu dan Agenda tidak boleh kosong.";
    } else {
        try {
            if (isset($_POST['tambah_agenda'])) {
                // Proses Tambah
                $stmt = $pdo->prepare("INSERT INTO rundown_items (user_id, waktu, agenda, lokasi, deskripsi) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $waktu, $agenda, $lokasi, $deskripsi]);
                // PERBAIKAN: Menggunakan page=tugas
                header("Location: user_dashboard.php?page=tugas&success=" . urlencode("Agenda berhasil ditambahkan."));
                exit();
            } elseif (isset($_POST['edit_agenda']) && $item_id) {
                // Proses Edit
                $stmt = $pdo->prepare("UPDATE rundown_items SET waktu = ?, agenda = ?, lokasi = ?, deskripsi = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$waktu, $agenda, $lokasi, $deskripsi, $item_id, $user_id]);
                // PERBAIKAN: Menggunakan page=tugas
                header("Location: user_dashboard.php?page=tugas&success=" . urlencode("Agenda berhasil diperbarui."));
                exit();
            }
        } catch (PDOException $e) {
            $pesan_error_rundown = "Error database: " . $e->getMessage();
        }
    }
}

// PROSES HAPUS AGENDA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_agenda'])) {
    $item_id_hapus = filter_var($_POST['item_id_hapus'], FILTER_VALIDATE_INT);
    if ($item_id_hapus) {
        try {
            $stmt = $pdo->prepare("DELETE FROM rundown_items WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$item_id_hapus, $user_id]) && $stmt->rowCount() > 0) {
                // PERBAIKAN: Menggunakan page=tugas
                header("Location: user_dashboard.php?page=tugas&success=" . urlencode("Agenda berhasil dihapus."));
                exit();
            }
        } catch (PDOException $e) {
            $pesan_error_rundown = "Gagal menghapus agenda.";
        }
    }
}

// Ambil data untuk ditampilkan (hanya jika di halaman list atau edit)
$rundown_items = [];
$item_to_edit = null;

if ($action === 'list') {
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    try {
        $sql = "SELECT id, waktu, agenda, lokasi, deskripsi FROM rundown_items WHERE user_id = ?";
        if (!empty($search_query)) {
            $sql .= " AND (agenda LIKE ? OR lokasi LIKE ? OR deskripsi LIKE ?)";
            $params = [$user_id, "%$search_query%", "%$search_query%", "%$search_query%"];
        } else {
            $params = [$user_id];
        }
        $sql .= " ORDER BY waktu ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rundown_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $pesan_error_rundown = "Gagal memuat data rundown.";
    }
} elseif ($action === 'edit' && $item_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM rundown_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);
        $item_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item_to_edit) {
            // PERBAIKAN: Menggunakan page=tugas
            header("Location: user_dashboard.php?page=tugas");
            exit();
        }
    } catch (PDOException $e) {
        $pesan_error_rundown = "Gagal mengambil data untuk diedit.";
    }
}

// Tampilkan halaman berdasarkan aksi
if ($action === 'add' || $action === 'edit'):
?>
    <div class="form-container">
        <div class="form-header">
            <a href="user_dashboard.php?page=tugas" class="back-button">&larr;</a>
            <h3><?php echo $action === 'add' ? 'Tambah Agenda' : 'Edit Agenda'; ?></h3>
        </div>

        <?php if (!empty($pesan_error_rundown)): ?>
            <div class="message-error"><?php echo $pesan_error_rundown; ?></div>
        <?php endif; ?>
        
        <form class="agenda-form" method="POST" action="user_dashboard.php?page=tugas&action=<?php echo $action; ?><?php echo $item_id ? '&id='.$item_id : ''; ?>">
            <div class="form-group">
                <label for="waktu">Waktu</label>
                <input type="time" id="waktu" name="waktu" value="<?php echo htmlspecialchars($item_to_edit['waktu'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="agenda">Agenda</label>
                <input type="text" id="agenda" name="agenda" placeholder="Contoh: Rias Pengantin" value="<?php echo htmlspecialchars($item_to_edit['agenda'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="lokasi">Lokasi</label>
                <input type="text" id="lokasi" name="lokasi" placeholder="Contoh: Ruang Rias" value="<?php echo htmlspecialchars($item_to_edit['lokasi'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" placeholder="Informasi tambahan mengenai agenda..."><?php echo htmlspecialchars($item_to_edit['deskripsi'] ?? ''); ?></textarea>
            </div>
            <button type="submit" name="<?php echo $action === 'add' ? 'tambah_agenda' : 'edit_agenda'; ?>" class="btn-simpan">+ Simpan</button>
        </form>
    </div>
<?php
else: // Tampilan default: list agenda
?>
    <div class="rundown-page-container">
        <?php if (!empty($pesan_sukses_rundown)): ?>
            <div class="message-success"><?php echo $pesan_sukses_rundown; ?></div>
        <?php endif; ?>
        <?php if (!empty($pesan_error_rundown)): ?>
            <div class="message-error"><?php echo $pesan_error_rundown; ?></div>
        <?php endif; ?>

        <section class="rundown-controls">
            <a href="user_dashboard.php?page=tugas&action=add" class="btn-add-agenda">+ Tambah</a>
            <form method="GET">
                <input type="hidden" name="page" value="tugas">
                <input type="text" name="search" placeholder="Cari agenda..." value="<?php echo htmlspecialchars($search_query ?? ''); ?>">
            </form>
        </section>

        <section class="rundown-table-container">
            <table class="rundown-table">
                <thead>
                    <tr><th>Waktu</th><th>Agenda</th><th>Lokasi</th><th>Deskripsi</th><th class="action-cell"></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($rundown_items)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-light-grey);">Belum ada agenda. Klik "+ Tambah" untuk memulai.</td></tr>
                    <?php else: ?>
                        <?php foreach($rundown_items as $item): ?>
                            <tr>
                                <td><?php echo date('H:i', strtotime($item['waktu'])); ?></td>
                                <td><?php echo htmlspecialchars($item['agenda']); ?></td>
                                <td><?php echo htmlspecialchars($item['lokasi']); ?></td>
                                <td><?php echo htmlspecialchars($item['deskripsi']); ?></td>
                                <td class="action-cell">
                                    <div class="action-buttons">
                                        <a href="user_dashboard.php?page=tugas&action=edit&id=<?php echo $item['id']; ?>" class="btn-edit" title="Edit">
                                            <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"></path></svg>
                                        </a>
                                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus agenda ini?')">
                                            <input type="hidden" name="item_id_hapus" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="hapus_agenda" class="btn-delete-rundown" title="Hapus">
                                                <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
<?php
endif;
?>