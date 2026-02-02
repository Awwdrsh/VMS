<?php
// includes/actions.php

/**
 * Handle Visitor Check-out
 */
function checkoutVisitor($pdo, $id)
{
    if ($id) {
        $stmt = $pdo->prepare("UPDATE Assessment_Visitors SET status='Signed Out', check_out_time=NOW() WHERE id=?");
        $stmt->execute([$id]);
    }
}

/**
 * Handle Visitor Deletion
 */
function deleteVisitor($pdo, $id)
{
    if ($id) {
        $pdo->prepare("DELETE FROM Assessment_Visitors WHERE id=?")->execute([$id]);
    }
}

/**
 * Handle Logout
 */
function logout()
{
    session_start();
    session_destroy();
}

/**
 * Generate Report AJAX HTML
 */
function getReportData($pdo, $q, $from, $to)
{
    $qParam = "%$q%";
    $sql = "SELECT * FROM Assessment_Visitors WHERE name LIKE ? AND DATE(check_in_time) BETWEEN ? AND ? ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$qParam, $from, $to]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($results) > 0) {
        foreach ($results as $v) {
            $img = $v['image_path'] ? "<img src='{$v['image_path']}' class='visitor-thumb'>" : "<div class='no-img-thumb'></div>";
            $out = $v['check_out_time'] ? date('H:i', strtotime($v['check_out_time'])) : '-';
            $statusClass = $v['status'] == 'Signed In' ? 'var(--success)' : '#b2bec3';

            echo "<tr>
                <td>$img</td>
                <td>" . esc($v['name']) . "</td>
                <td>" . esc($v['purpose']) . "</td>
                <td>" . date('H:i', strtotime($v['check_in_time'])) . "</td>
                <td>$out</td>
                <td><b style='color:$statusClass'>{$v['status']}</b></td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='6' style='text-align:center; color:#94a3b8; padding:2rem;'>No records found for this criteria.</td></tr>";
    }
}

/**
 * Export CSV Report
 */
function exportReport($pdo, $q, $from, $to)
{
    $qParam = "%$q%";
    $sql = "SELECT name, purpose, check_in_time, check_out_time, status, phone, email FROM Assessment_Visitors WHERE name LIKE ? AND DATE(check_in_time) BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$qParam, $from, $to]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "visitor_report_" . date('Ymd') . ".csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Purpose', 'Check In', 'Check Out', 'Status', 'Phone', 'Email']);

    foreach ($results as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

/**
 * Search Visitors for Dashboard
 */
function searchVisitors($pdo, $q)
{
    if (empty($q)) {
        // Return default view: Signed In
        $stmt = $pdo->query("SELECT * FROM Assessment_Visitors WHERE status = 'Signed In' ORDER BY check_in_time DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $qParam = "%$q%";
    // Search by name, purpose, or phone
    $sql = "SELECT * FROM Assessment_Visitors WHERE name LIKE ? OR purpose LIKE ? OR phone LIKE ? ORDER BY check_in_time DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$qParam, $qParam, $qParam]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
