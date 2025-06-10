<?php
// pages/vendor.php
if (!isset($pdo)) {
    // Inisialisasi jika file ini di-include dari user_dashboard.php
    include_once 'config.php';
    $database_check = new Database();
    $pdo = $database_check->getConnection();
}
if (!isset($user_id)) { 
    $user_id = $_SESSION['user_id'] ?? null; 
}

// Logika untuk menangani pengiriman rating (dengan redirect)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    if (!$user_id) {
        header("Location: user_dashboard.php?page=vendor&error=" . urlencode("Anda harus login untuk memberikan rating."));
        exit();
    }
    
    $vendor_profile_id = $_POST['vendor_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $review_text = trim($_POST['review_text'] ?? '');

    if (!$vendor_profile_id || !is_numeric($vendor_profile_id) || !$rating || !is_numeric($rating) || $rating < 1 || $rating > 5) {
        header("Location: user_dashboard.php?page=vendor&error=" . urlencode("Input rating tidak valid."));
        exit();
    } else {
        try {
            $stmt_check = $pdo->prepare("SELECT id FROM vendor_ratings WHERE vendor_profile_id = ? AND user_id = ?");
            $stmt_check->execute([$vendor_profile_id, $user_id]);
            $existing_rating = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($existing_rating) {
                $stmt_update = $pdo->prepare("UPDATE vendor_ratings SET rating = ?, review_text = ?, created_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt_update->execute([$rating, $review_text, $existing_rating['id']]);
                header("Location: user_dashboard.php?page=vendor&success=" . urlencode("Rating Anda berhasil diperbarui!"));
                exit();
            } else {
                $stmt_insert = $pdo->prepare("INSERT INTO vendor_ratings (vendor_profile_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
                $stmt_insert->execute([$vendor_profile_id, $user_id, $rating, $review_text]);
                header("Location: user_dashboard.php?page=vendor&success=" . urlencode("Terima kasih! Rating Anda berhasil disimpan."));
                exit();
            }
        } catch (PDOException $e) { 
            header("Location: user_dashboard.php?page=vendor&error=" . urlencode("Terjadi kesalahan saat menyimpan rating."));
            exit();
        }
    }
}

// --- LOGIKA MENGAMBIL DATA ---
$vendor_categories = [];
$vendors = [];
$error_message = '';
try {
    $stmt_categories = $pdo->prepare("SELECT id, name FROM vendor_categories ORDER BY name ASC");
    $stmt_categories->execute();
    $vendor_categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

    $stmt_vendors = $pdo->prepare("SELECT vp.*, u.username as vendor_username, AVG(vr.rating) AS average_rating, COUNT(DISTINCT vr.id) AS total_reviews, MAX(CASE WHEN vr.user_id = ? THEN vr.rating ELSE NULL END) AS user_given_rating, MAX(CASE WHEN vr.user_id = ? THEN vr.review_text ELSE NULL END) AS user_given_review_text FROM vendor_profiles vp LEFT JOIN users u ON vp.user_id = u.id LEFT JOIN vendor_ratings vr ON vp.id = vr.vendor_profile_id WHERE vp.status = 'approved' GROUP BY vp.id ORDER BY vp.company_name ASC");
    $stmt_vendors->execute([$user_id, $user_id]);
    $vendors = $stmt_vendors->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $error_message = "Gagal mengambil data dari database."; }

?>

<div class="vendor-page-container">
    <p>Temukan vendor terpercaya yang telah diverifikasi untuk kebutuhan pernikahan Anda.</p>
    
    <?php if (!empty($pesan_sukses)): ?><div class="message-success"><?php echo $pesan_sukses; ?></div><?php endif; ?>
    <?php if (!empty($pesan_error)): ?><div class="message-error"><?php echo $pesan_error; ?></div><?php endif; ?>
    <?php if (!empty($error_message)): ?><div class="message-error"><?php echo $error_message; ?></div><?php endif; ?>

    <section class="vendor-controls">
        <div class="search-form"><input type="text" id="vendorSearch" placeholder="Cari nama vendor..."></div>
        <div class="filter-form">
            <label for="categoryFilter">Filter Kategori:</label>
            <select id="categoryFilter">
                <option value="">Semua Kategori</option>
                <?php foreach ($vendor_categories as $category): ?>
                    <option value="<?php echo htmlspecialchars(strtolower($category['name'])); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </section>

    <div class="vendor-list-container">
        <?php if (empty($vendors)): ?>
            <div class="vendor-card"><p style="text-align:center; padding: 20px;">Belum ada vendor yang tersedia.</p></div>
        <?php else: ?>
            <?php foreach ($vendors as $vendor): ?>
                <div class="vendor-card" data-services='<?php echo htmlspecialchars($vendor['services']); ?>'>
                    <div class="vendor-card-summary">
                        <div class="vendor-logo"><?php echo strtoupper(substr($vendor['company_name'], 0, 1)); ?></div>
                        <div class="vendor-info">
                            <h3><?php echo htmlspecialchars($vendor['company_name']); ?></h3>
                            <div class="vendor-rating-summary">
                                <?php $avg_rating = round($vendor['average_rating'] ?? 0, 1); $total_reviews = $vendor['total_reviews'] ?? 0; ?>
                                <span class="stars"><?php echo str_repeat('★', round($avg_rating)) . str_repeat('☆', 5 - round($avg_rating)); ?></span>
                                <span><?php echo $avg_rating; ?> (<?php echo $total_reviews; ?> ulasan)</span>
                            </div>
                            <div class="vendor-tags">
                                <?php
                                $services = parseServices($vendor['services']);
                                foreach ($services as $service): 
                                    $displayName = getCategoryDisplayName($service, $vendor_categories);
                                ?>
                                    <span class="tag <?php echo strtolower(str_replace(' ', '', $displayName)); ?>"><?php echo htmlspecialchars($displayName); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="vendor-card-detail">
                        <div class="detail-grid">
                            <div class="detail-section">
                                <h4>Deskripsi</h4>
                                <p><?php echo nl2br(htmlspecialchars($vendor['description'])); ?></p>
                                
                                <?php $gallery_images = parseServices($vendor['gallery_images'] ?? '[]'); if (!empty($gallery_images)): ?>
                                <div class="gallery-section">
                                    <h4>Galeri Foto</h4>
                                    <div class="photo-gallery">
                                        <?php foreach ($gallery_images as $image): ?>
                                            <div class="photo-item"><a href="uploads/<?php echo htmlspecialchars($image); ?>" target="_blank"><img src="uploads/<?php echo htmlspecialchars($image); ?>" alt="Foto Vendor"></a></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <h4>Informasi Kontak</h4>
                                <div class="info-item"><svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg><span><a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $vendor['whatsapp']); ?>" target="_blank"><?php echo htmlspecialchars($vendor['whatsapp']); ?></a></span></div>
                                <?php if (!empty($vendor['social_media'])): ?><div class="info-item"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v-1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg><span><a href="<?php echo (strpos($vendor['social_media'], 'http') === 0) ? htmlspecialchars($vendor['social_media']) : 'https://' . htmlspecialchars($vendor['social_media']); ?>" target="_blank"><?php echo htmlspecialchars($vendor['social_media']); ?></a></span></div><?php endif; ?>
                                <div class="info-item"><svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg><span><?php echo htmlspecialchars($vendor['address']); ?></span></div>
                            </div>
                            
                            <div class="detail-section">
                                <?php if ($user_id): ?>
                                <form method="POST" action="user_dashboard.php?page=vendor" class="rating-form">
                                    <h4><?php echo $vendor['user_given_rating'] ? 'Update Rating Anda' : 'Berikan Rating Anda'; ?></h4>
                                    <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                    <div class="star-rating"><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" id="star-<?php echo $vendor['id'] . '-' . $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo ($vendor['user_given_rating'] == $i) ? 'checked' : ''; ?> required><label for="star-<?php echo $vendor['id'] . '-' . $i; ?>" title="<?php echo $i; ?> stars">★</label><?php endfor; ?></div>
                                    <textarea name="review_text" placeholder="Tulis ulasan Anda (opsional)..."><?php echo htmlspecialchars($vendor['user_given_review_text'] ?? ''); ?></textarea>
                                    <button type="submit" name="submit_rating" class="btn-submit-rating"><?php echo $vendor['user_given_rating'] ? 'Update Rating' : 'Kirim Rating'; ?></button>
                                </form>
                                <?php endif; ?>

                                <div class="reviews-section">
                                    <h4>Ulasan Pengguna</h4>
                                    <?php
                                    $stmt_reviews = $pdo->prepare("SELECT vr.rating, vr.review_text, u.username, vr.created_at, vr.user_id FROM vendor_ratings vr JOIN users u ON vr.user_id = u.id WHERE vr.vendor_profile_id = ? ORDER BY vr.created_at DESC LIMIT 5");
                                    $stmt_reviews->execute([$vendor['id']]);
                                    $reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);
                                    if(empty($reviews)):
                                    ?>
                                        <p>Belum ada ulasan untuk vendor ini.</p>
                                    <?php else: foreach($reviews as $review): ?>
                                        <div class="review-item <?php echo ($user_id && $review['user_id'] == $user_id) ? 'current-user-review' : ''; ?>">
                                            <div class="review-header">
                                                <span class="reviewer-name"><?php echo htmlspecialchars($review['username']); ?><?php echo ($user_id && $review['user_id'] == $user_id) ? ' (Anda)' : ''; ?></span>
                                                <span class="review-date"><?php echo date('d M Y', strtotime($review['created_at'])); ?></span>
                                            </div>
                                            <div class="review-stars"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></div>
                                            <p class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                        </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // PERBAIKAN: Menggunakan .closest() untuk mencari parent yang benar
    document.querySelectorAll('.vendor-card-summary').forEach(summary => {
        summary.addEventListener('click', function() {
            const detail = this.closest('.vendor-card').querySelector('.vendor-card-detail');
            if (detail) {
                const isVisible = detail.style.display === 'block';
                detail.style.display = isVisible ? 'none' : 'block';
            }
        });
    });

    // Skrip filter (tidak ada perubahan)
    function filterVendors() {
        const searchTerm = document.getElementById('vendorSearch').value.toLowerCase();
        const selectedCategory = document.getElementById('categoryFilter').value.toLowerCase();
        
        document.querySelectorAll('.vendor-card').forEach(card => {
            const companyName = card.querySelector('h3').textContent.toLowerCase();
            const servicesJson = card.dataset.services || '[]';
            let services = [];
            try { services = JSON.parse(servicesJson.replace(/'/g, '"')).map(s => s.toLowerCase()); } catch (e) {}
            
            const matchesSearch = companyName.includes(searchTerm);
            const matchesCategory = selectedCategory === '' || services.includes(selectedCategory);

            card.style.display = (matchesSearch && matchesCategory) ? 'block' : 'none';
        });
    }

    document.getElementById('vendorSearch').addEventListener('keyup', filterVendors);
    document.getElementById('categoryFilter').addEventListener('change', filterVendors);
});
</script>