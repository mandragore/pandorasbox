<?php
require_once '../functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $where = ["1=1"];

    // Filters
    if (isset($_GET['computer_id'])) {
        $cid = (int) $_GET['computer_id'];
        $where[] = "l.computer_id = $cid";
    }

    if (isset($_GET['borrower_id'])) {
        $bid = (int) $_GET['borrower_id'];
        $where[] = "l.borrower_id = $bid";
    }

    if (isset($_GET['start_date'])) {
        $sd = $conn->real_escape_string($_GET['start_date']);
        $where[] = "l.start_date >= '$sd'";
    }

    if (isset($_GET['end_date'])) {
        $ed = $conn->real_escape_string($_GET['end_date']);
        $where[] = "l.end_date <= '$ed'";
    }

    // Is Returned Filter
    // Default: Show all? Or active? User didn't specify, showing all is safer for an API, but maybe filtering active is better?
    // Let's allow a filter for it.
    if (isset($_GET['is_returned'])) {
        $ret = (int) $_GET['is_returned'];
        $where[] = "l.is_returned = $ret";
    }

    $where_sql = implode(' AND ', $where);

    $sql = "SELECT l.*, c.name as computer_name, b.name as borrower_name 
            FROM loans l 
            JOIN computers c ON l.computer_id = c.id
            LEFT JOIN borrowers b ON l.borrower_id = b.id
            WHERE $where_sql
            ORDER BY l.start_date DESC";

    $result = $conn->query($sql);

    if ($result) {
        $loans = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['status' => 'success', 'count' => count($loans), 'data' => $loans]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>