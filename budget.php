<?php
// Pastikan variabel $pdo dan $user_id sudah disediakan
if (!isset($pdo) || !isset($user_id)) { die("Akses tidak sah."); }

// Inisialisasi variabel pesan
$pesan_sukses_budget = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$pesan_error_budget = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// --- LOGIKA UPDATE DANA AWAL (TARGET ANGGARAN MANUAL) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_dana_awal'])) {
    $dana_awal = (float)str_replace(['.', ','], '', $_POST['dana_awal']);
    if ($dana_awal >= 0) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET target_anggaran_manual = ? WHERE id = ?");
            if ($stmt->execute([$dana_awal, $user_id])) {
                header("Location: user_dashboard.php?page=budget&success=" . urlencode("Dana awal berhasil diperbarui."));
                exit();
            }
        } catch (PDOException $e) { $pesan_error_budget = "Error database: " . $e->getMessage(); }
    }
}

// --- LOGIKA TAMBAH/HAPUS KATEGORI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = trim($_POST['nama_kategori']);
    $biaya_kategori = (float)str_replace(['.', ','], '', $_POST['biaya_kategori']); 
    if (empty($nama_kategori)) { $pesan_error_budget = "Nama kategori tidak boleh kosong."; }
    else {
        try {
            $stmt_insert = $pdo->prepare("INSERT INTO budget_categories (user_id, nama_kategori, anggaran) VALUES (?, ?, ?)");
            if ($stmt_insert->execute([$user_id, $nama_kategori, $biaya_kategori])) {
                header("Location: user_dashboard.php?page=budget&success=" . urlencode("Kategori '$nama_kategori' berhasil ditambahkan!"));
                exit();
            }
        } catch (PDOException $e) { $pesan_error_budget = "Error database: " . $e->getMessage(); }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_kategori'])) {
    $kategori_id = filter_var($_POST['kategori_id_hapus'], FILTER_VALIDATE_INT);
    if ($kategori_id) {
        try {
            $stmt_delete = $pdo->prepare("DELETE FROM budget_categories WHERE id = ? AND user_id = ?");
            if ($stmt_delete->execute([$kategori_id, $user_id]) && $stmt_delete->rowCount() > 0) {
                header("Location: user_dashboard.php?page=budget&success=" . urlencode("Kategori berhasil dihapus."));
                exit();
            }
        } catch (PDOException $e) { $pesan_error_budget = "Error database: " . $e->getMessage(); }
    }
}


// --- LOGIKA MENGAMBIL SEMUA DATA ---
$budget_categories = [];
$dana_awal = 0;

try {
    $stmt_budget = $pdo->prepare("SELECT id, nama_kategori, anggaran FROM budget_categories WHERE user_id = ? ORDER BY id ASC");
    $stmt_budget->execute([$user_id]);
    $budget_categories = $stmt_budget->fetchAll(PDO::FETCH_ASSOC);

    $stmt_user = $pdo->prepare("SELECT target_anggaran_manual FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $result = $stmt_user->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $dana_awal = $result['target_anggaran_manual'];
    }

} catch (PDOException $e) { $pesan_error_budget = "Gagal mengambil data budget."; }

?>

<?php if (!empty($pesan_sukses_budget)): ?>
    <div class="message-success"><?php echo $pesan_sukses_budget; ?></div>
<?php endif; ?>
<?php if (!empty($pesan_error_budget)): ?>
    <div class="message-error"><?php echo $pesan_error_budget; ?></div>
<?php endif; ?>

<div>
    <section class="budget-summary-fullscreen">
        <div class="summary-item">
            <div class="icon-wrapper"><span class="icon">$</span></div>
            <div class="details">
                <span class="label">Dana Awal (Anggaran) <button class="btn-edit-target" onclick="showEditDanaAwalModal()">Edit</button></span>
                <span class="amount" style="color: var(--primary-purple);"><?php echo formatCurrency($dana_awal); ?></span>
            </div>
        </div>
        
        <?php
        $total_biaya = 0;
        foreach ($budget_categories as $category) {
            $total_biaya += $category['anggaran'];
        }
        $sisa_terakhir = $dana_awal - $total_biaya;
        ?>
        <div class="summary-item">
            <div class="icon-wrapper" style="background-color: #D8F2E2;"><span class="icon" style="color: #37A24A;">=</span></div>
            <div class="details">
                <span class="label">Sisa Anggaran Terakhir</span>
                <span class="amount" style="color: <?php echo ($sisa_terakhir < 0) ? '#EE5B5B' : '#37A24A'; ?>;">
                    <?php echo formatCurrency($sisa_terakhir); ?>
                </span>
            </div>
        </div>
    </section>

    <section class="budget-table-container">
        <div class="budget-table-header">
            <h3>Rincian Biaya</h3>
            <button class="btn-add" onclick="showAddKategoriModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5V19M5 12H19" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Tambah Biaya
            </button>
        </div>
        <table class="budget-table">
            <thead>
                <tr><th>Kategori</th><th>Biaya Kategori</th><th>Sisa Anggaran (Berjalan)</th><th class="action-cell"></th></tr>
            </thead>
            <tbody>
                <?php if (empty($budget_categories)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-light-grey);">Belum ada biaya. Klik "+ Tambah Biaya".</td></tr>
                <?php else: ?>
                    <?php 
                    $sisa_berjalan = $dana_awal;
                    foreach ($budget_categories as $category):
                        $biaya_kategori = $category['anggaran'];
                        $sisa_berjalan -= $biaya_kategori;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['nama_kategori']); ?></td>
                            <td><?php echo formatCurrency($biaya_kategori); ?></td>
                            <td><?php echo formatCurrency($sisa_berjalan); ?></td>
                            <td class="action-cell">
                                <form action="user_dashboard.php?page=budget" method="POST" onsubmit="return confirm('Anda yakin ingin menghapus kategori <?php echo htmlspecialchars(addslashes($category['nama_kategori'])); ?>?')">
                                    <input type="hidden" name="kategori_id_hapus" value="<?php echo $category['id']; ?>">
                                    <button type="submit" name="hapus_kategori" class="btn-delete" title="Hapus"><svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"></path></svg></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>

<div id="addKategoriModal" class="modal">
    <div class="modal-content"><div class="modal-header"><h4>Tambah Biaya Baru</h4><button class="modal-close-button" onclick="closeModal('addKategoriModal')">&times;</button></div><form action="user_dashboard.php?page=budget" method="POST"><div class="form-group"><label for="add_nama_kategori">Nama Kategori/Biaya</label><input type="text" id="add_nama_kategori" name="nama_kategori" required></div><div class="form-group"><label for="add_biaya_kategori">Jumlah Biaya (Rp)</label><input type="text" id="add_biaya_kategori" name="biaya_kategori" inputmode="numeric" required onkeyup="formatInputCurrency(this)"></div><button type="submit" name="tambah_kategori" class="btn-modal-primary">Simpan Biaya</button></form></div>
</div>

<div id="editDanaAwalModal" class="modal">
    <div class="modal-content"><div class="modal-header"><h4>Edit Dana Awal</h4><button class="modal-close-button" onclick="closeModal('editDanaAwalModal')">&times;</button></div><form action="user_dashboard.php?page=budget" method="POST"><div class="form-group"><label for="input_dana_awal">Total Dana Awal (Rp)</label><input type="text" id="input_dana_awal" name="dana_awal" inputmode="numeric" required onkeyup="formatInputCurrency(this)"></div><button type="submit" name="update_dana_awal" class="btn-modal-primary">Simpan Dana Awal</button></form></div>
</div>

<style>
.btn-link { background: none; border: none; color: var(--primary-purple); text-decoration: underline; cursor: pointer; padding: 0; font-size: inherit; font-family: inherit; text-align: left; font-weight: bold; }
.btn-link:hover { color: #5a4cd1; }
</style>

<script>
    function formatInputCurrency(input) {
        let value = input.value.replace(/\D/g, '');
        if (value === '') { input.value = ''; return; }
        input.value = Number(value).toLocaleString('id-ID');
    }
    function showModal(modalId) { document.getElementById(modalId).style.display = 'flex'; }
    function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
    function showAddKategoriModal() { showModal('addKategoriModal'); }
    
    function showEditDanaAwalModal() {
        const inputTarget = document.getElementById('input_dana_awal');
        const currentDana = parseFloat(<?php echo json_encode($dana_awal); ?>) || 0;
        inputTarget.value = currentDana;
        formatInputCurrency(inputTarget);
        showModal('editDanaAwalModal');
    }

    window.onclick = function(event) {
        const modals = document.getElementsByClassName('modal');
        for (let i = 0; i < modals.length; i++) {
            if (event.target == modals[i]) {
                modals[i].style.display = "none";
            }
        }
    }
    
    // PERBAIKAN BUG ADA DI SINI
    document.addEventListener('DOMContentLoaded', function() {
        closeModal('addKategoriModal');
        closeModal('editDanaAwalModal');
    });
</script>