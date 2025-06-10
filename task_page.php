<?php
// Pastikan variabel $user_id sudah ada dari user_dashboard.php
if (!isset($user_id)) {
    die("User ID tidak ditemukan.");
}
?>

<div class="task-list-container">
    <h3>Daftar Tugas Pernikahan</h3>
    <p>Gunakan daftar ini untuk mencatat semua hal yang perlu Anda persiapkan. Data disimpan di browser Anda.</p>

    <ul id="task-list" class="task-list-dynamic">
        </ul>

    <form id="add-task-form" class="add-task-form">
        <input type="text" id="new-task-input" placeholder="Tulis tugas baru (misal: Pesan cincin kawin)..." required>
        <button type="submit">+ Tambah</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Kunci unik untuk halaman Task agar tidak bentrok dengan Rundown
    const STORAGE_KEY = 'weddingPlanner_tasks_<?php echo $user_id; ?>';

    const taskList = document.getElementById('task-list');
    const addTaskForm = document.getElementById('add-task-form');
    const newTaskInput = document.getElementById('new-task-input');

    // Mengambil tugas dari localStorage
    function getTasks() {
        const tasks = localStorage.getItem(STORAGE_KEY);
        return tasks ? JSON.parse(tasks) : [];
    }

    // Menyimpan tugas ke localStorage
    function saveTasks(tasks) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(tasks));
    }

    // Menampilkan satu tugas di layar
    function renderTask(task) {
        const li = document.createElement('li');
        li.className = 'task-item-dynamic';
        li.dataset.taskId = task.id;

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'task-checkbox';
        checkbox.checked = task.completed;
        checkbox.addEventListener('change', () => {
            toggleTaskCompletion(task.id);
        });

        const textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.className = 'task-name';
        textInput.value = task.name;
        if (task.completed) {
            textInput.classList.add('completed');
        }
        textInput.addEventListener('blur', () => {
            updateTaskName(task.id, textInput.value);
        });
        textInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                textInput.blur();
            }
        });

        const deleteButton = document.createElement('button');
        deleteButton.className = 'delete-task-btn';
        deleteButton.textContent = 'Hapus';
        deleteButton.addEventListener('click', () => {
            deleteTask(task.id);
        });

        li.appendChild(checkbox);
        li.appendChild(textInput);
        li.appendChild(deleteButton);
        taskList.appendChild(li);
    }

    // Memuat dan menampilkan semua tugas saat halaman dibuka
    function loadAndRenderTasks() {
        taskList.innerHTML = '';
        const tasks = getTasks();
        if (tasks.length === 0) {
            const emptyMessage = document.createElement('li');
            emptyMessage.textContent = 'Belum ada tugas yang ditambahkan.';
            emptyMessage.style.padding = '15px 8px';
            emptyMessage.style.color = '#777';
            taskList.appendChild(emptyMessage);
        } else {
            tasks.forEach(task => renderTask(task));
        }
    }

    // Menambah tugas baru
    addTaskForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const taskName = newTaskInput.value.trim();
        if (taskName === '') return;

        const tasks = getTasks();
        const newTask = {
            id: Date.now(),
            name: taskName,
            completed: false
        };

        tasks.push(newTask);
        saveTasks(tasks);
        loadAndRenderTasks();
        newTaskInput.value = '';
    });

    // Mengubah status selesai/belum
    function toggleTaskCompletion(id) {
        const tasks = getTasks();
        const task = tasks.find(t => t.id === id);
        if (task) {
            task.completed = !task.completed;
            saveTasks(tasks);
            loadAndRenderTasks();
        }
    }

    // Mengubah nama tugas
    function updateTaskName(id, newName) {
        const tasks = getTasks();
        const task = tasks.find(t => t.id === id);
        if (task && task.name !== newName) {
            task.name = newName;
            saveTasks(tasks);
        }
    }

    // Menghapus tugas
    function deleteTask(id) {
        if (!confirm('Anda yakin ingin menghapus tugas ini?')) {
            return;
        }
        let tasks = getTasks();
        tasks = tasks.filter(t => t.id !== id);
        saveTasks(tasks);
        loadAndRenderTasks();
    }

    // Inisialisasi: Muat semua tugas saat skrip pertama kali dijalankan
    loadAndRenderTasks();
});
</script>