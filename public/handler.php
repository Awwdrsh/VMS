<?php
// public/handler.php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/actions.php';

$action = $_GET['action'] ?? '';

if ($action === 'logout') {
    logout();
    header("Location: index.php");
    exit;
}

// All actions below require protection
protect();

switch ($action) {
    case 'checkout':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf'] ?? '')) {
            checkoutVisitor($pdo, $_POST['id'] ?? null);
        }
        header("Location: dashboard.php");
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf'] ?? '')) {
            deleteVisitor($pdo, $_POST['id'] ?? null);
        }
        header("Location: dashboard.php");
        break;

    case 'ajax_reports':
        getReportData($pdo, $_GET['q'] ?? '', $_GET['from'] ?? date('Y-m-d'), $_GET['to'] ?? date('Y-m-d'));
        break;

    case 'ajax_search':
        $results = searchVisitors($pdo, $_GET['q'] ?? '');
        header('Content-Type: application/json');
        echo json_encode($results);
        break;

    case 'export':
        exportReport($pdo, $_GET['q'] ?? '', $_GET['from'] ?? date('Y-m-d'), $_GET['to'] ?? date('Y-m-d'));
        break;

    default:
        header("Location: dashboard.php");
        break;
}
exit;
