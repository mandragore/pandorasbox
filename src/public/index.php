<?php
require_once '../functions.php';

$late_loans = get_late_loans();
$has_late = count($late_loans) > 0;
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$matrix = get_allocation_matrix($year);

// Stats
$active_result = $conn->query("SELECT COUNT(*) as c FROM loans WHERE is_returned = 0");
$active_count = $active_result->fetch_assoc()['c'];

$total_pcs_res = $conn->query("SELECT COUNT(*) as c FROM computers WHERE status = 'available'");
$total_pcs = $total_pcs_res->fetch_assoc()['c'];
$available_count = $total_pcs - $active_count;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - Loan Manager</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Specific tweaks for dashboard */
        .search-year {
            float: right;
            margin-top: -30px;
        }
    </style>
    <link rel="icon" type="image/svg+xml" href="logo.svg">
</head>

<body>
    <div class="container">
        <!-- Header -->
        <?php include "header.php"; ?>

        <!-- Alerts -->
        <?php if ($has_late): ?>
            <div class="card alert">
                <h3>⚠️ Late Returns Alert</h3>
                <p><?php echo count($late_loans); ?> loan(s) are overdue. Check the <a href="loans.php"
                        style="color:inherit; text-decoration:underline;">Loans</a> page.</p>
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="dashboard-grid">
            <div class="card">
                <h3>Active Loans</h3>
                <div class="stat-number"><?php echo $active_count; ?></div>
            </div>
            <div class="card">
                <h3>Available PCs</h3>
                <div class="stat-number"><?php echo $available_count; ?></div>
            </div>
            <div class="card" style="display: flex; align-items: center; justify-content: center;">
                <a href="loans.php"><button style="font-size: 1.1em; padding: 10px 20px;">+ New Reservation</button></a>
            </div>
        </div>

        <!-- Matrix -->
        <div class="card" style="margin-top: 1rem;">
            <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3>Resource Stick (<?php echo $year; ?>)</h3>
                <div class="search-year" style="margin: 0;">
                    <a href="?year=<?php echo $year - 1; ?>" style="text-decoration:none;">&laquo;
                        <?php echo $year - 1; ?></a>
                    <span style="margin: 0 10px; font-weight:bold;"><?php echo $year; ?></span>
                    <a href="?year=<?php echo $year + 1; ?>" style="text-decoration:none;"><?php echo $year + 1; ?>
                        &raquo;</a>
                </div>
            </div>

            <div class="matrix-container">
                <table class="matrix-table">
                    <thead>
                        <tr>
                            <th class="pc-col">Computer</th>
                            <?php for ($w = 1; $w <= 52; $w++): ?>
                                <th title="Week <?php echo $w; ?>"><?php echo $w; ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matrix as $pc): ?>
                            <tr>
                                <td class="pc-col" title="<?php echo $pc['processor']; ?>">
                                    <?php echo htmlspecialchars($pc['name']); ?>
                                </td>
                                <?php for ($w = 1; $w <= 52; $w++):
                                    $cell = $pc['weeks'][$w];
                                    $class = $cell ? 'cell-booked' : '';
                                    $borrowerName = $cell['borrower'] ?? 'Unknown';
                                    $title = $cell ? "Booked by: " . htmlspecialchars($borrowerName) : "Week $w: Free";
                                    $content = "";

                                    // Handle Logic: Show handle if it's the LAST week of this loan
                                    $is_end = false;
                                    if ($cell) {
                                        $next_w = $w + 1;
                                        $next_cell = $pc['weeks'][$next_w] ?? null;
                                        $is_end = ($next_cell === null || $next_cell['id'] !== $cell['id']);

                                        $content = "<a href='loans.php?edit_id=" . $cell['id'] . "' style='display:block; width:100%; height:100%; min-height:20px;' title='" . $title . "'>&nbsp;</a>";

                                        if ($is_end) {
                                            $content .= "<div class='resize-handle' data-loan-id='" . $cell['id'] . "' data-current-week='" . $w . "'></div>";
                                        }
                                    } else {
                                        // Empty cell - Click to book
                                        $dates = get_start_end_date($w, $year);
                                        $s_date = $dates['start'];
                                        $e_date = $dates['end'];
                                        $book_url = "loans.php?computer_id=" . $pc['id'] . "&start_date=" . $s_date . "&end_date=" . $e_date;
                                        $content = "<a href='" . $book_url . "' style='display:block; width:100%; height:100%; min-height:20px;' title='Book Week $w'>&nbsp;</a>";
                                    }
                                    ?>
                                    <td class="<?php echo $class; ?>" style="padding:0; position:relative;"
                                        data-week="<?php echo $w; ?>" data-year="<?php echo $year; ?>"><?php echo $content; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    let isDragging = false;
                    let currentLoanId = null;
                    let startWeek = null;
                    let targetWeek = null;
                    let draggedYear = null;

                    document.body.addEventListener('mousedown', (e) => {
                        if (e.target.classList.contains('resize-handle')) {
                            e.preventDefault();
                            isDragging = true;
                            currentLoanId = e.target.getAttribute('data-loan-id');
                            startWeek = parseInt(e.target.getAttribute('data-current-week'));
                            draggedYear = <?php echo $year; ?>;
                            document.body.style.cursor = 'e-resize';
                        }
                    });

                    document.body.addEventListener('mousemove', (e) => {
                        if (!isDragging) return;

                        // Find the cell under cursor
                        let cell = document.elementFromPoint(e.clientX, e.clientY);
                        if (cell && (cell.tagName === 'TD' || cell.closest('td'))) {
                            if (cell.tagName !== 'TD') cell = cell.closest('td');

                            // Check if it's a valid target (in the same row, same year)
                            let w = parseInt(cell.getAttribute('data-week'));

                            if (w && w > startWeek) {
                                targetWeek = w;
                                // Visual Feedback can be added here (e.g. highlight range)
                                document.querySelectorAll('.drag-highlight').forEach(el => el.classList.remove('drag-highlight'));
                                // Highlight from startWeek+1 to w in this ROW
                                let row = cell.parentElement;
                                let cells = row.querySelectorAll('td');
                                cells.forEach(c => {
                                    let cw = parseInt(c.getAttribute('data-week'));
                                    if (cw > startWeek && cw <= targetWeek) {
                                        c.classList.add('drag-highlight');
                                    }
                                });
                            }
                        }
                    });

                    document.body.addEventListener('mouseup', (e) => {
                        if (!isDragging) return;
                        isDragging = false;
                        document.body.style.cursor = 'default';
                        document.querySelectorAll('.drag-highlight').forEach(el => el.classList.remove('drag-highlight'));

                        if (targetWeek && targetWeek > startWeek) {
                            if (confirm('Extend loan #' + currentLoanId + ' to Week ' + targetWeek + '?')) {
                                // AJAX Post
                                let formData = new FormData();
                                formData.append('action', 'extend_drag');
                                formData.append('loan_id', currentLoanId);
                                formData.append('target_week', targetWeek);
                                formData.append('year', draggedYear);

                                fetch('loans.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.status === 'success') {
                                            location.reload();
                                        } else {
                                            alert('Error: ' + data.message);
                                        }
                                    })
                                    .catch(err => {
                                        console.error(err);
                                        alert('Request failed.');
                                    });
                            }
                        }

                        targetWeek = null;
                        currentLoanId = null;
                    });
                });
            </script>
            </tbody>
            </table>
        </div>
        <div style="margin-top: 10px; font-size: 0.8rem; color: #666;">
            <span style="display:inline-block; width: 12px; height: 12px; background: var(--primary-color);"></span>
            Booked &nbsp;
            <span style="display:inline-block; width: 12px; height: 12px; border: 1px solid #ccc;"></span> Available
        </div>
    </div>

    </div>
</body>

</html>