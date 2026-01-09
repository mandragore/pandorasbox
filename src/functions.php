<?php

// Basic Authentication
$valid_user = getenv('AUTH_USER');
$valid_pass = getenv('AUTH_PASS');

if (!$valid_user || !$valid_pass) {
    // Fallback or error if env not set
    // For safety, default to secure/random or just fail
    die('Configuration Error: Auth credentials not set.');
}

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== $valid_user || $_SERVER['PHP_AUTH_PW'] !== $valid_pass) {
    header('WWW-Authenticate: Basic realm="Pandore Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    die('Unauthorized Access');
}

require_once 'db.php';

function get_weeks_in_year($year)
{
    $date = new DateTime;
    $date->setISODate($year, 53);
    return ($date->format("W") === "53" ? 53 : 52);
}

function get_start_end_date($week, $year)
{
    $dto = new DateTime();
    $dto->setISODate($year, $week);
    $start = $dto->format('Y-m-d');
    $dto->modify('+6 days');
    $end = $dto->format('Y-m-d');
    return ['start' => $start, 'end' => $end];
}

function calculate_age($purchase_date)
{
    $dob = new DateTime($purchase_date);
    $now = new DateTime();
    $diff = $now->diff($dob);
    return $diff->y . " years, " . $diff->m . " months";
}

function get_computers($filters = [])
{
    global $conn;
    $sql = "SELECT * FROM computers WHERE 1=1";

    // Filtering logic to be added
    if (isset($filters['available_start']) && isset($filters['available_end'])) {
        $start = $filters['available_start'];
        $end = $filters['available_end'];

        $exclude_self = "";
        if (isset($filters['ignore_loan_id'])) {
            $lid = (int) $filters['ignore_loan_id'];
            $exclude_self = " AND id != $lid";
        }

        $sql .= " AND id NOT IN (
            SELECT computer_id FROM loans 
            WHERE (start_date <= '$end' AND end_date >= '$start') 
            AND is_returned = 0
            $exclude_self
        )";
    }

    $sql .= " AND deleted_at IS NULL ORDER BY name ASC";

    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_computer_by_id($id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM computers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function get_borrowers($search = '')
{
    global $conn;
    if ($search) {
        $term = "%" . $search . "%";
        $stmt = $conn->prepare("SELECT * FROM borrowers WHERE name LIKE ? AND deleted_at IS NULL ORDER BY name");
        $stmt->bind_param("s", $term);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT * FROM borrowers WHERE deleted_at IS NULL ORDER BY name");
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_borrower_by_id($id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM borrowers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function get_late_loans()
{
    global $conn;
    $today = date('Y-m-d');
    $sql = "SELECT l.*, c.name as computer_name, b.name as borrower_name
            FROM loans l 
            JOIN computers c ON l.computer_id = c.id
            LEFT JOIN borrowers b ON l.borrower_id = b.id
            WHERE l.end_date < '$today' AND l.is_returned = 0";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_allocation_matrix($year)
{
    global $conn;

    // Get all computers
    $computers = get_computers();
    $matrix = [];

    // Initialize matrix
    foreach ($computers as $pc) {
        $matrix[$pc['id']] = [
            'id' => $pc['id'],
            'name' => $pc['name'],
            'processor' => $pc['processor'],
            'weeks' => array_fill(1, 52, null) // Array of 52 weeks
        ];
    }

    // Get loans for this year
    $start_year = "$year-01-01";
    $end_year = "$year-12-31";

    $sql = "SELECT l.*, b.name as borrower_name FROM loans l 
            LEFT JOIN borrowers b ON l.borrower_id = b.id
            WHERE (start_date <= '$end_year' AND end_date >= '$start_year') 
            AND is_returned = 0";

    $result = $conn->query($sql);

    while ($loan = $result->fetch_assoc()) {
        $cid = $loan['computer_id'];
        if (!isset($matrix[$cid]))
            continue;

        $start_week = (int) date('W', strtotime($loan['start_date']));
        $end_week = (int) date('W', strtotime($loan['end_date']));
        $year_of_start = (int) date('o', strtotime($loan['start_date']));
        $year_of_end = (int) date('o', strtotime($loan['end_date']));

        // Simple handling for loans crossing years: wrap to edges
        if ($year_of_start < $year)
            $start_week = 1;
        if ($year_of_end > $year)
            $end_week = 53; // Use 53 to cover full year if needed, or 52

        for ($w = $start_week; $w <= $end_week; $w++) {
            if ($w >= 1 && $w <= 52) {
                // If multiple loans per week (rare but possible), we just show the last one found
                // or specific logic. We'll store an object.
                $matrix[$cid]['weeks'][$w] = [
                    'id' => $loan['id'],
                    'borrower' => $loan['borrower_name'] ?? $loan['borrower_name'], // Fallback if needed
                    'status' => 'booked'
                ];
            }
        }
    }

    return $matrix;
}
?>