<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
protect();
$sort = $_GET['sort'] ?? 'check_in_time';
$order = $_GET['order'] ?? 'DESC';

$allowedSort = ['check_in_time', 'status'];
if (!in_array($sort, $allowedSort))
    $sort = 'check_in_time';
$order = $order === 'ASC' ? 'ASC' : 'DESC';

// Stats
$activeCount = $pdo->query("SELECT COUNT(*) FROM Assessment_Visitors WHERE status = 'Signed In'")->fetchColumn();
$todayCount = $pdo->query("SELECT COUNT(*) FROM Assessment_Visitors WHERE DATE(check_in_time) = CURDATE()")->fetchColumn();

// Main List: only Active - REMOVED (Client-side fetch instead)
include '../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Active Visitors</h3>
        <p><?= $activeCount ?></p>
    </div>
    <div class="stat-card">
        <h3>Today's Total</h3>
        <p><?= $todayCount ?></p>
    </div>
</div>

<div class="controls-bar">
    <div class="search-wrapper">
        <input type="text" id="searchInput" class="search-bar" placeholder="Live search by name...">
    </div>
    <div class="filter-wrapper">
        <div class="view-controls">
            <button id="listViewBtn" class="btn btn-sm active" onclick="switchView('list')">List</button>
            <button id="cardViewBtn" class="btn btn-sm" onclick="switchView('card')">Cards</button>
        </div>
    </div>
</div>

<!-- LIST VIEW -->
<div id="listView" class="view-section">
    <table>
        <thead>
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Purpose</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="visitorTableBody">
            <!-- Content loaded via JS -->
        </tbody>
    </table>
</div>

<!-- CARD VIEW -->
<div id="cardView" class="view-section" style="display:none;">
    <div class="card-grid">
        <!-- Content loaded via JS -->
    </div>
</div>

<script>
    const listView = document.getElementById('listView');
    const cardView = document.getElementById('cardView');
    const listBtn = document.getElementById('listViewBtn');
    const cardBtn = document.getElementById('cardViewBtn');
    const searchInput = document.getElementById('searchInput');

    let typingTimer;

    // Search Event
    searchInput.addEventListener('input', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(fetchVisitors, 300);
    });

    // Initial Load
    document.addEventListener('DOMContentLoaded', fetchVisitors);

    function fetchVisitors() {
        const q = searchInput.value;
        fetch(`./handler.php?action=ajax_search&q=${encodeURIComponent(q)}`)
            .then(res => {
                if (!res.ok) throw new Error("Network response was not ok " + res.status);
                return res.json();
            })
            .then(data => {
                renderTable(data);
                renderCards(data);
            })
            .catch(error => {
                console.error('Error fetching visitors:', error);
            });
    }

    function renderTable(data) {
        const tbody = document.getElementById('visitorTableBody');
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:1rem;">No visitors found.</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(v => {
            const img = v.image_path ? `<img src="${v.image_path}" alt="Visitor" class="visitor-thumb">` : '<div class="no-img-thumb"></div>';
            const checkOutTime = v.check_out_time ? formatTime(v.check_out_time) : '-';
            const statusColor = v.status === 'Signed In' ? 'var(--success)' : '#999';

            let actions = '';
            if (v.status === 'Signed In') {
                actions += `<form action="handler.php?action=checkout" method="POST" style="display:inline">
                          <input type="hidden" name="csrf" value="<?php echo generateCsrf(); ?>">
                          <input type="hidden" name="id" value="${v.id}">
                          <button type="submit" class="btn btn-warning btn-sm">Out</button>
                        </form> `;
            }
            actions += `<a href="visitor.php?id=${v.id}" class="btn btn-primary btn-sm">Edit</a> `;
            actions += `<form action="handler.php?action=delete" method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
                          <input type="hidden" name="csrf" value="<?php echo generateCsrf(); ?>">
                          <input type="hidden" name="id" value="${v.id}">
                          <button type="submit" class="btn btn-danger btn-sm">X</button>
                        </form>`;

            return `
            <tr class="visitor-row">
                <td>${img}</td>
                <td>${esc(v.name)}</td>
                <td>${esc(v.purpose)}</td>
                <td>${formatTime(v.check_in_time)}</td>
                <td>${checkOutTime}</td>
                <td><b style="color:${statusColor}">${v.status}</b></td>
                <td>${actions}</td>
            </tr>`;
        }).join('');
    }

    function renderCards(data) {
        const grid = document.querySelector('.card-grid');
        if (data.length === 0) {
            grid.innerHTML = '<p style="text-align:center; width:100%; padding:2rem;">No visitors found.</p>';
            return;
        }

        grid.innerHTML = data.map(v => {
            const img = v.image_path ? `<img src="${v.image_path}" alt="Visitor" class="card-img">` : '<div class="card-no-img"></div>';
            const statusClass = v.status === 'Signed In' ? 'status-in' : 'status-out';
            const checkOutTime = v.check_out_time ? `<span><small>Out:</small> ${formatTime(v.check_out_time)}</span>` : '';

            let checkOutBtn = '';
            if (v.status === 'Signed In') {
                checkOutBtn = `<form action="handler.php?action=checkout" method="POST" style="width:100%; margin-bottom:0.5rem;">
                          <input type="hidden" name="csrf" value="<?php echo generateCsrf(); ?>">
                          <input type="hidden" name="id" value="${v.id}">
                          <button type="submit" class="btn btn-warning btn-block">Check Out</button>
                        </form>`;
            }

            return `
            <div class="visitor-card">
                <div class="card-header">
                    ${img}
                    <span class="status-badge ${statusClass}">${v.status}</span>
                </div>
                <div class="card-body">
                    <h3>${esc(v.name)}</h3>
                    <p class="role">${esc(v.purpose)}</p>
                    <div class="meta" style="flex-direction:column; align-items:flex-start;">
                        <span><small>In:</small> ${formatTime(v.check_in_time)}</span>
                        ${checkOutTime}
                    </div>
                </div>
                <div class="card-actions">
                    ${checkOutBtn}
                    <div class="action-row">
                        <a href="visitor.php?id=${v.id}" class="btn btn-primary btn-ghost">Edit</a>
                         <form action="handler.php?action=delete" method="POST" style="display:inline; flex:1;" onsubmit="return confirm('Delete?')">
                            <input type="hidden" name="csrf" value="<?php echo generateCsrf(); ?>">
                            <input type="hidden" name="id" value="${v.id}">
                            <button type="submit" class="btn btn-danger btn-ghost" style="width:100%">Delete</button>
                        </form>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    function esc(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatTime(sqlTime) {
        // Simple parser for YYYY-MM-DD HH:MM:SS to HH:MM
        if (!sqlTime) return '';
        // fallback if replace fails (Safari/older browsers might need distinct parsing, but chrome/standard is fine)
        // or just substring if we trust SQL format
        return sqlTime.substring(11, 16);
    }

    // View Toggle Logic
    function switchView(view) {
        if (view === 'card') {
            listView.style.display = 'none';
            cardView.style.display = 'block';
            listBtn.classList.remove('active');
            cardBtn.classList.add('active');
            localStorage.setItem('visitorView', 'card');
        } else {
            listView.style.display = 'block';
            cardView.style.display = 'none';
            listBtn.classList.add('active');
            cardBtn.classList.remove('active');
            localStorage.setItem('visitorView', 'list');
        }
    }

    // Load Preference
    if (localStorage.getItem('visitorView') === 'card') {
        switchView('card');
    }
</script>

<?php include '../includes/footer.php'; ?>