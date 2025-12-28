<?php
require_once '../functions.php';

$message = '';
$error = '';
$edit_loan = null;

// Handle Edit Mode Fetch
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $res = $conn->query("SELECT * FROM loans WHERE id = $edit_id");
    if ($res->num_rows > 0) {
        $edit_loan = $res->fetch_assoc();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && ($_POST['action'] === 'book' || $_POST['action'] === 'update')) {
        $computer_id = $_POST['computer_id'];
        $borrower_id = $_POST['borrower_id'];

        $start = '';
        $end = '';

        $start = $_POST['start_date'];
        $end = $_POST['end_date'];

        // Check conflicts (Exclude current loan if updating)
        $exclude_sql = "";
        if ($_POST['action'] === 'update') {
            $id = $_POST['loan_id'];
            $exclude_sql = " AND id != $id";
        }

        $check = $conn->query("SELECT * FROM loans WHERE computer_id = $computer_id AND is_returned = 0 AND 
            (start_date <= '$end' AND end_date >= '$start') $exclude_sql");

        if ($check->num_rows > 0) {
            $error = "Computer is already booked for this period!";
        } else {
            if ($_POST['action'] === 'book') {
                $stmt = $conn->prepare("INSERT INTO loans (computer_id, borrower_id, start_date, end_date) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $computer_id, $borrower_id, $start, $end);
            } else {
                $stmt = $conn->prepare("UPDATE loans SET computer_id=?, borrower_id=?, start_date=?, end_date=? WHERE id=?");
                $id = $_POST['loan_id'];
                $stmt->bind_param("isssi", $computer_id, $borrower_id, $start, $end, $id);
            }

            if ($stmt->execute()) {
                $message = ($_POST['action'] === 'book') ? "Loan recorded successfully!" : "Loan updated successfully!";
                // Clear edit mode
                $edit_loan = null;
                if ($_POST['action'] === 'update') {
                    // clean redirect to remove get param
                    header("Location: loans.php");
                    exit;
                }
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'return') {
        $loan_id = $_POST['loan_id'];
        $conn->query("UPDATE loans SET is_returned = 1 WHERE id = $loan_id");
        $message = "Computer returned.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'extend_drag') {
        $loan_id = $_POST['loan_id'];
        $year = $_POST['year'];
        $target_week = $_POST['target_week'];

        // Calculate new end date (Sunday of the target week)
        $dates = get_start_end_date($target_week, $year);
        $new_end_date = $dates['end'];

        // Validation: Ensure no overlap with OTHER loans
        // We know the current loan exists. We need to check if extending it overlaps others.
        // Get current start date
        $curr_res = $conn->query("SELECT start_date, computer_id FROM loans WHERE id = $loan_id");
        $curr_row = $curr_res->fetch_assoc();
        $start = $curr_row['start_date'];
        $computer_id = $curr_row['computer_id'];

        $check = $conn->query("SELECT * FROM loans WHERE computer_id = $computer_id AND is_returned = 0 AND id != $loan_id AND 
            (start_date <= '$new_end_date' AND end_date >= '$start')");

        if ($check->num_rows > 0) {
            // Error - Overlap
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Overlap detected']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE loans SET end_date = ? WHERE id = ?");
        $stmt->bind_param("si", $new_end_date, $loan_id);
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
            exit;
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'cancel_edit') {
        header("Location: loans.php");
        exit;
    }
}

$loans = $conn->query("SELECT l.*, c.name, b.name as borrower_name FROM loans l 
    JOIN computers c ON l.computer_id = c.id 
    LEFT JOIN borrowers b ON l.borrower_id = b.id
    WHERE l.is_returned = 0 ORDER BY l.start_date DESC")->fetch_all(MYSQLI_ASSOC);
$filters = [];
if (isset($_GET['start_date']) && !empty($_GET['start_date']))
    $filters['available_start'] = $_GET['start_date'];
if (isset($_GET['end_date']) && !empty($_GET['end_date']))
    $filters['available_end'] = $_GET['end_date'];

// Context: If editing, ignore the current loan so the machine is still "available" for itself
if (isset($_GET['edit_id'])) {
    $filters['ignore_loan_id'] = $_GET['edit_id'];
} elseif ($edit_loan) {
    $filters['ignore_loan_id'] = $edit_loan['id'];
}

$computers = get_computers($filters);
$borrowers = get_borrowers();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Loans - Pandora's Box</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/svg+xml" href="logo.svg">
</head>

<body>
    <div class="container">
        <?php include "header.php"; ?>

        <?php if ($message): ?>
            <div class="card" style="border-color: var(--success-color); color: var(--success-color); margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="card alert" style="margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card"
            style="<?php echo $edit_loan ? 'border-color: var(--primary-color); border-width: 2px;' : ''; ?>">
            <h3><?php echo $edit_loan ? 'Edit Reservation #' . $edit_loan['id'] : 'New Reservation'; ?></h3>

            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_loan ? 'update' : 'book'; ?>">
                <?php if ($edit_loan): ?>
                    <input type="hidden" name="loan_id" value="<?php echo $edit_loan['id']; ?>">
                <?php endif; ?>

                <h4>Timing (Select Dates)</h4>

                <div style="display:flex; gap: 10px; margin-bottom: 20px;">
                    <div style="flex:1">
                        <label>Start Date: <span id="start_week_display"
                                style="font-weight:normal; color:#666; font-size:0.85em;"></span></label>
                        <input type="date" name="start_date" id="start_date"
                            value="<?php echo $_GET['start_date'] ?? ($edit_loan ? $edit_loan['start_date'] : ''); ?>"
                            required>
                    </div>
                    <div style="flex:1">
                        <label>End Date: <span id="end_week_display"
                                style="font-weight:normal; color:#666; font-size:0.85em;"></span></label>
                        <input type="date" name="end_date" id="end_date"
                            value="<?php echo $_GET['end_date'] ?? ($edit_loan ? $edit_loan['end_date'] : ''); ?>"
                            required>
                    </div>
                </div>

                <script>
                    function getWeekNumber(d) {
                        d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
                        d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
                        var yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
                        var weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
                        return weekNo;
                    }

                    function updateWeekDisplay(inputId, displayId) {
                        const input = document.getElementById(inputId);
                        const display = document.getElementById(displayId);
                        if (input.value) {
                            const date = new Date(input.value);
                            const week = getWeekNumber(date);
                            display.textContent = '(Week ' + week + ')';
                        } else {
                            display.textContent = '';
                        }
                    }

                    function checkDates() {
                        const start = document.getElementById('start_date').value;
                        const end = document.getElementById('end_date').value;

                        // Get current params to see if we need to reload
                        const urlParams = new URLSearchParams(window.location.search);
                        const currentStart = urlParams.get('start_date');
                        const currentEnd = urlParams.get('end_date');

                        if (start && end && (start !== currentStart || end !== currentEnd)) {
                            if (end <= start) return;
                            // Reload with new params
                            urlParams.set('start_date', start);
                            urlParams.set('end_date', end);
                            window.location.search = urlParams.toString();
                        }
                    }

                    document.addEventListener('DOMContentLoaded', () => {
                        const startInput = document.getElementById('start_date');
                        const endInput = document.getElementById('end_date');

                        startInput.addEventListener('change', () => {
                            updateWeekDisplay('start_date', 'start_week_display');
                            checkDates();
                        });
                        endInput.addEventListener('change', () => {
                            updateWeekDisplay('end_date', 'end_week_display');
                            checkDates();
                        });

                        // Initial run
                        updateWeekDisplay('start_date', 'start_week_display');
                        updateWeekDisplay('end_date', 'end_week_display');
                    });
                </script>

                <div style="margin-bottom: 10px;">
                    <label>Borrower:</label><br>
                    <select name="borrower_id" required>
                        <option value="">-- Select Borrower --</option>
                        <?php foreach ($borrowers as $b):
                            $selected = ($edit_loan && $edit_loan['borrower_id'] == $b['id']) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $b['id']; ?>" <?php echo $selected; ?>><?php echo $b['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom: 10px;">
                    <label>Computer:</label><br>
                    <select name="computer_id" style="width: 100%;" required>
                        <option value="">-- Select Computer --</option>
                        <?php
                        $pre_comp = $_GET['computer_id'] ?? null;
                        foreach ($computers as $pc):
                            $selected = ($edit_loan && $edit_loan['computer_id'] == $pc['id']) || (!$edit_loan && $pre_comp == $pc['id']) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $pc['id']; ?>" <?php echo $selected; ?>><?php echo $pc['name']; ?>
                                (<?php echo $pc['processor']; ?>) -
                                <?php echo calculate_age($pc['purchase_date']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr style="border-color: var(--border-color); margin: 20px 0;">

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit"
                        style="flex: 2;"><?php echo $edit_loan ? 'UPDATE RESERVATION' : 'CONFIRM RESERVATION'; ?></button>
                    <?php if ($edit_loan): ?>
                        <a href="loans.php" style="flex: 1;"><button type="button"
                                style="width: 100%; background: #6c757d;">CANCEL</button></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <h3>Active Loans</h3>
        <table>
            <thead>
                <tr>
                    <th>Computer</th>
                    <th>Borrower</th>
                    <th>Period</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loans as $loan):
                    $is_late = (strtotime($loan['end_date']) < time());
                    $is_editing = ($edit_loan && $edit_loan['id'] == $loan['id']);
                    ?>
                    <tr style="<?php echo $is_editing ? 'background: rgba(0, 123, 255, 0.1);' : ''; ?>">
                        <td><?php echo htmlspecialchars($loan['name']); ?></td>
                        <td><?php echo htmlspecialchars($loan['borrower_name'] ?? 'Unknown'); ?></td>
                        <td>
                            <?php echo $loan['start_date']; ?> > <?php echo $loan['end_date']; ?>
                        </td>
                        <td>
                            <?php if ($is_late): ?>
                                <span class="late-badge">LATE</span>
                            <?php else: ?>
                                ACTIVE
                            <?php endif; ?>
                            <?php if ($is_editing): ?>
                                <span style="color: var(--primary-color); font-weight: bold; margin-left: 5px;">(EDITING)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="loans.php?edit_id=<?php echo $loan['id']; ?>" class="btn-action btn-edit">
                                ✏️ Edit
                            </a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="return">
                                <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                <button type="submit" class="btn-action btn-return">↩ Return</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>