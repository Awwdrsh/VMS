<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
protect();
include '../includes/header.php';
?>

<div class="controls-bar">
    <div class="search-wrapper">
        <input type="text" id="searchInput" class="search-bar" placeholder="Filter by visitor name...">
    </div>
    <div class="filter-wrapper">
        <div class="filter-group">
            <label>From:</label>
            <input type="date" id="fromDate" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="filter-group">
            <label>To:</label>
            <input type="date" id="toDate" value="<?= date('Y-m-d') ?>">
        </div>

        <div class="action-buttons" style="display: flex; gap: 0.5rem; margin-left: auto;">
            <a href="#" id="exportBtn" class="btn btn-success">
                📊 Export CSV
            </a>
            <button onclick="window.print()" class="btn btn-ghost">
                🖨️ Print
            </button>
        </div>
    </div>
</div>

<div id="logsContainer">
    <table>
        <thead>
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Purpose</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="logsTableBody">
            <!-- AJAX Results -->
        </tbody>
    </table>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const fromDate = document.getElementById('fromDate');
    const toDate = document.getElementById('toDate');
    const tableBody = document.getElementById('logsTableBody');
    const exportBtn = document.getElementById('exportBtn');

    function fetchLogs() {
        const q = searchInput.value;
        const from = fromDate.value;
        const to = toDate.value;

        // Update Export Link
        exportBtn.href = `./handler.php?action=export&q=${encodeURIComponent(q)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;

        fetch(`./handler.php?action=ajax_reports&q=${encodeURIComponent(q)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`)
            .then(res => {
                if (!res.ok) throw new Error("Request failed with status " + res.status);
                return res.text();
            })
            .then(html => {
                tableBody.innerHTML = html;
            })
            .catch(err => {
                console.error(err);
                tableBody.innerHTML = `<tr><td colspan="6" style="color:red; text-align:center;">Error loading data: ${err.message}</td></tr>`;
            });
    }

    searchInput.addEventListener('keyup', fetchLogs);
    fromDate.addEventListener('change', fetchLogs);
    toDate.addEventListener('change', fetchLogs);

    // Initial Load
    fetchLogs();
</script>

<?php include '../includes/footer.php'; ?>