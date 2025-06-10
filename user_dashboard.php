<?php
ob_start();

include_once 'auth.php';
requireRole('user');

include_once 'config.php';
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    error_log("Koneksi ke database gagal: " . (isset($e) ? $e->getMessage() : "Unknown connection error"));
    die("Koneksi ke database gagal. Silakan periksa konfigurasi Anda.");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

if (!function_exists('formatCurrency')) {
    function formatCurrency($number, $prefix = 'Rp ') {
        return $prefix . number_format($number, 0, ',', '.');
    }
}
if (!function_exists('parseServices')) {
    function parseServices($servicesJson) {
        if (empty($servicesJson)) return [];
        $services = json_decode($servicesJson, true);
        return is_array($services) ? $services : [];
    }
}
if (!function_exists('getCategoryDisplayName')) {
    function getCategoryDisplayName($serviceName, $categories) {
        foreach ($categories as $category) {
            if (strtolower($category['name']) === strtolower($serviceName)) {
                return $category['name'];
            }
        }
        return $serviceName;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page = $_GET['page'] ?? null;
    
    if (isset($_POST['set_wedding_date']) && isset($_POST['wedding_date_input'])) {
        $new_wedding_date = trim($_POST['wedding_date_input']);
        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $new_wedding_date)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET wedding_date = ? WHERE id = ?");
                $stmt->execute([$new_wedding_date, $user_id]);
                header("Location: user_dashboard.php?page=dashboard&success=" . urlencode("Tanggal Hari H berhasil diperbarui!"));
                exit();
            } catch (PDOException $e) {
                header("Location: user_dashboard.php?page=dashboard&error=" . urlencode("Gagal memperbarui tanggal Hari H: " . $e->getMessage()));
                exit();
            }
        } else {
            header("Location: user_dashboard.php?page=dashboard&error=" . urlencode("Format tanggal tidak valid. GunakanYYYY-MM-DD."));
            exit();
        }
    }

    if ($page === 'budget') { include 'budget.php'; }
    if ($page === 'tamu') { include 'guest_page.php'; }
    if ($page === 'tugas') { include 'rundown_page.php'; }
    if ($page === 'vendor') { include 'vendor.php'; }
}

$pesan_sukses = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$pesan_error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Planner Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="user_dashboard.css">
    
    <?php if ($current_page === 'budget'): ?><link rel="stylesheet" href="user_budget.css"><?php endif; ?>
    <?php if ($current_page === 'tamu'): ?><link rel="stylesheet" href="guest_page.css"><?php endif; ?>
    <?php if ($current_page === 'tugas'): ?><link rel="stylesheet" href="rundown_page.css"><?php endif; ?>
    <?php if ($current_page === 'vendor'): ?><link rel="stylesheet" href="vendor_page.css"><?php endif; ?>
    <?php if ($current_page === 'task'): ?><link rel="stylesheet" href="task_page.css"><?php endif; ?>
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <div>
                <div class="sidebar-header"><h1>weder</h1></div>
                <ul class="nav-menu">
                    <li class="nav-item"> <a href="?page=dashboard" class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">Dashboard</a> </li>
                    <li class="nav-item"> <a href="?page=budget" class="nav-link <?php echo $current_page == 'budget' ? 'active' : ''; ?>">Budget Planner</a> </li>
                    <li class="nav-item"> <a href="?page=tamu" class="nav-link <?php echo $current_page == 'tamu' ? 'active' : ''; ?>">Guest</a> </li>
                    <li class="nav-item"> <a href="?page=tugas" class="nav-link <?php echo $current_page == 'tugas' ? 'active' : ''; ?>">Rundown</a> </li>
                    <li class="nav-item"> <a href="?page=vendor" class="nav-link <?php echo $current_page == 'vendor' ? 'active' : ''; ?>">Vendor</a> </li>
                    <li class="nav-item"> <a href="?page=task" class="nav-link <?php echo $current_page == 'task' ? 'active' : ''; ?>">Task</a> </li>
                </ul>
            </div>
            <div class="sidebar-footer"><a href="logout.php" class="logout-button">Logout</a></div>
        </nav>

        <main class="main-content">
            <header class="header">
                <h2>
                    <?php
                    switch($current_page) {
                        case 'profile': echo 'Profile Pengguna'; break;
                        case 'budget': echo 'Budget Planner'; break;
                        case 'tamu': echo 'Daftar Tamu'; break;
                        case 'tugas': echo 'Rundown'; break;
                        case 'task': echo 'Daftar Tugas'; break;
                        case 'vendor': echo 'Daftar Vendor'; break;
                        default: echo 'Dashboard';
                    }
                    ?>
                </h2>
                <div class="user-profile-actions">
                    <span class="user-greeting-text">Halo, Kadek</span>
                    <a href="?page=profile" class="avatar-profile-link" title="Lihat Profil"><div class="avatar-icon"></div></a>
                </div>
            </header>

            <div class="content-area">
                <?php
                switch($current_page) {
                    case 'profile':
                        echo "<p>Konten Profile Pengguna.</p>"; break;
                    case 'budget':
                        include 'budget.php'; break;
                    case 'tamu':
                        include 'guest_page.php'; break;
                    case 'tugas':
                        include 'rundown_page.php'; break;
                    case 'task':
                        include 'task_page.php'; break;
                    case 'vendor':
                        include 'vendor.php'; break;
                    default:
                        $dashboard_vendors = [];
                        $dashboard_total_anggaran = 0;
                        $total_tamu_dashboard = 0;
                        $tamu_hadir_dashboard = 0;
                        $wedding_date_from_db = null; 

                        try {
                            $stmt_vendor = $pdo->prepare("SELECT id, company_name, services FROM vendor_profiles WHERE status = 'approved' ORDER BY id DESC LIMIT 4");
                            $stmt_vendor->execute();
                            $dashboard_vendors = $stmt_vendor->fetchAll(PDO::FETCH_ASSOC);

                            $stmt_user_data = $pdo->prepare("SELECT target_anggaran_manual, wedding_date FROM users WHERE id = ?");
                            $stmt_user_data->execute([$user_id]);
                            $user_data = $stmt_user_data->fetch(PDO::FETCH_ASSOC);
                            
                            if ($user_data) {
                                $dashboard_total_anggaran = $user_data['target_anggaran_manual'] ?? 0;
                                $wedding_date_from_db = $user_data['wedding_date'];
                            }

                            $stmt_guests_counts = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) as hadir FROM guests WHERE user_id = ?");
                            $stmt_guests_counts->execute([$user_id]);
                            $guests_counts_data = $stmt_guests_counts->fetch(PDO::FETCH_ASSOC);

                            if ($guests_counts_data) {
                                $total_tamu_dashboard = $guests_counts_data['total'];
                                $tamu_hadir_dashboard = $guests_counts_data['hadir'];
                            }

                        } catch (PDOException $e) {
                            error_log("Error fetching dashboard data: " . $e->getMessage());
                            $pesan_error = "Gagal memuat data dashboard.";
                        }
                        
                        $wedding_date_js = $wedding_date_from_db ? $wedding_date_from_db : '';
                ?>
                        <?php if (!empty($pesan_sukses)): ?>
                            <div class="message-success"><?php echo $pesan_sukses; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($pesan_error)): ?>
                            <div class="message-error"><?php echo $pesan_error; ?></div>
                        <?php endif; ?>

                        <section class="overview-cards">
                            <div class="card event-countdown">
                                <div class="date-display-wrapper">
                                    <div class="date" id="weddingDateDisplay">
                                        <?php
                                        if ($wedding_date_from_db) {
                                            $date_obj = new DateTime($wedding_date_from_db);
                                            echo '<span class="day">' . $date_obj->format('d') . '</span>';
                                            echo '<span class="month">' . $date_obj->format('M') . '</span>';
                                            echo '<span class="year">' . $date_obj->format('Y') . '</span>';
                                        } else {
                                            echo '<span class="day">--</span>';
                                            echo '<span class="month">Bln</span>';
                                            echo '<span class="year">Thn</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="countdown">
                                    <span class="days-left" id="daysLeft">--</span>
                                    <span class="label" id="countdownLabel">Hari Menuju Hari H!</span>
                                </div>
                                <button type="button" class="btn-set-wedding-date" id="openWeddingDateModal">
                                    Atur Hari H
                                </button>
                            </div>

                            <div class="card total-budget"><div class="icon-wrapper"><span class="icon">$</span></div><div class="details"><span class="label">Total Anggaran</span><span class="amount"><?php echo formatCurrency($dashboard_total_anggaran); ?></span></div></div>
                            <div class="card guests-invited"><div class="icon-wrapper"><span class="icon people-icon"></span></div><div class="details"><span class="label">Tamu Hadir</span><span class="count"><?php echo $tamu_hadir_dashboard; ?>/<?php echo $total_tamu_dashboard; ?></span></div></div>

                        </section>
                        
                        <section class="sections-container">
                            <div class="vendor-section">
                                <h3>Vendor Terbaru</h3>
                                <div class="vendor-list">
                                    <?php if (empty($dashboard_vendors)): ?>
                                        <p>Belum ada vendor.</p>
                                    <?php else: foreach ($dashboard_vendors as $vendor): ?>
                                        <a href="user_dashboard.php?page=vendor&highlight_vendor=<?php echo $vendor['id']; ?>" class="vendor-item-link">
                                            <div class="vendor-item">
                                                <span class="vendor-name"><?php echo htmlspecialchars($vendor['company_name']); ?></span>
                                                <div class="tags">
                                                    <?php $services_to_show = array_slice(parseServices($vendor['services']), 0, 2);
                                                    foreach ($services_to_show as $tag): 
                                                        $tag_class = strtolower(str_replace(' ', '', $tag)); ?>
                                                        <span class="tag <?php echo $tag_class; ?>"><?php echo htmlspecialchars($tag); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                            
                            <div class="task-section">
                                <h3>Task</h3>
                                <div class="task-list" id="dashboard-task-list">
                                    </div>
                            </div>
                        </section>
                <?php
                        break;
                }
                ?>
            </div>
        </main>
    </div>

    <div id="weddingDateModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Atur Tanggal Hari H</h2>
            <form method="POST" action="user_dashboard.php?page=dashboard" class="modal-form">
                <label for="modalWeddingDateInput">Pilih Tanggal Pernikahan:</label>
                <input type="date" id="modalWeddingDateInput" name="wedding_date_input" value="<?php echo htmlspecialchars($wedding_date_from_db); ?>" required>
                <button type="submit" name="set_wedding_date" class="btn-primary">Simpan Tanggal</button>
            </form>
        </div>
    </div>

    <?php if($current_page === 'dashboard'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const taskListContainer = document.getElementById('dashboard-task-list');
            if (taskListContainer) {
                const STORAGE_KEY = 'weddingPlanner_tasks_<?php echo $user_id; ?>';

                function getTasks() {
                    const tasks = localStorage.getItem(STORAGE_KEY);
                    return tasks ? JSON.parse(tasks) : [];
                }

                function saveTasks(tasks) {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(tasks));
                }

                function renderDashboardTasks() {
                    const tasks = getTasks();
                    const tasksToShow = tasks.slice(0, 5);
                    taskListContainer.innerHTML = '';

                    if (tasksToShow.length === 0) {
                        taskListContainer.innerHTML = '<p style="color: #777; font-size: 0.9em;">Belum ada tugas.</p>';
                        return;
                    }

                    tasksToShow.forEach(task => {
                        const taskItem = document.createElement('div');
                        taskItem.className = 'task-item';

                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.id = `dashboard-task-${task.id}`;
                        checkbox.checked = task.completed;
                        
                        checkbox.addEventListener('change', function() {
                            const allTasks = getTasks();
                            const taskToUpdate = allTasks.find(t => t.id === task.id);
                            if (taskToUpdate) {
                                taskToUpdate.completed = this.checked;
                                saveTasks(allTasks);
                                renderDashboardTasks();
                            }
                        });
                        
                        const label = document.createElement('label');
                        label.htmlFor = `dashboard-task-${task.id}`;
                        label.textContent = task.name;
                        if(task.completed){
                            label.style.textDecoration = 'line-through';
                            label.style.color = '#999';
                        }

                        taskItem.appendChild(checkbox);
                        taskItem.appendChild(label);
                        taskListContainer.appendChild(taskItem);
                    });
                }

                renderDashboardTasks();
                
                window.addEventListener('storage', function(e) {
                    if (e.key === STORAGE_KEY) {
                        renderDashboardTasks();
                    }
                });
            }

            let weddingDateString = '<?php echo $wedding_date_js; ?>';
            const daysLeftSpan = document.getElementById('daysLeft');
            const countdownLabelSpan = document.getElementById('countdownLabel');
            const weddingDateDisplay = document.getElementById('weddingDateDisplay');

            let countdownInterval;

            function updateCountdown() {
                if (!weddingDateString) {
                    daysLeftSpan.textContent = '--';
                    countdownLabelSpan.textContent = 'Atur Tanggal Hari H';
                    weddingDateDisplay.innerHTML = '<span class="day">--</span><span class="month">Bln</span><span class="year">Thn</span>';
                    clearInterval(countdownInterval);
                    return;
                }

                const weddingDate = new Date(weddingDateString + "T00:00:00");
                const now = new Date();
                const timeDifference = weddingDate.getTime() - now.getTime();

                const days = Math.floor(timeDifference / (1000 * 60 * 60 * 24));

                if (timeDifference <= 0) {
                    daysLeftSpan.textContent = '0';
                    countdownLabelSpan.textContent = 'Selamat Hari H!';
                    clearInterval(countdownInterval);
                } else {
                    daysLeftSpan.textContent = days;
                    countdownLabelSpan.textContent = `Hari Menuju Hari H!`;
                }

                const dateOptions = { day: 'numeric', month: 'short', year: 'numeric' };
                const dateParts = weddingDate.toLocaleDateString('id-ID', dateOptions).split(' ');
                
                weddingDateDisplay.innerHTML = `
                    <span class="day">${dateParts[0]}</span>
                    <span class="month">${dateParts[1].replace('.', '')}</span>
                    <span class="year">${dateParts[2]}</span>
                `;
            }

            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000 * 60);

            const weddingDateModal = document.getElementById('weddingDateModal');
            const openWeddingDateModalBtn = document.getElementById('openWeddingDateModal');
            const closeButton = document.querySelector('.close-button');
            const modalWeddingDateInput = document.getElementById('modalWeddingDateInput');

            openWeddingDateModalBtn.onclick = function() {
                weddingDateModal.classList.add('is-visible'); // Add class to show modal
                if (weddingDateString) {
                    modalWeddingDateInput.value = weddingDateString;
                }
            }

            closeButton.onclick = function() {
                weddingDateModal.classList.remove('is-visible'); // Remove class to hide modal
            }

            window.onclick = function(event) {
                if (event.target == weddingDateModal) {
                    weddingDateModal.classList.remove('is-visible'); // Remove class to hide modal
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
<?php ob_end_flush(); ?>