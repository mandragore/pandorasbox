<?php
require_once '../functions.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = $_POST['name'];
        $proc = $_POST['processor'];
        $ram = $_POST['ram'];
        $date = $_POST['purchase_date'];

        $stmt = $conn->prepare("INSERT INTO computers (name, processor, ram, purchase_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $proc, $ram, $date);
        $stmt->execute();
    }
    // Delete action could be added here
}

// Filters
$filters = [];
if (isset($_GET['search_start']) && isset($_GET['search_end']) && !empty($_GET['search_start'])) {
    $filters['available_start'] = $_GET['search_start'];
    $filters['available_end'] = $_GET['search_end'];
}

$computers = get_computers($filters);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inventory - Retro Loan</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/svg+xml" href="logo.svg">
</head>

<body>
    <div class="scanlines"></div>
    <div class="container">
        <?php include "header.php"; ?>

        <div class="card">
            <h3>Search Availability</h3>
            <form method="GET">
                <div style="display:flex; gap: 10px; margin-bottom: 10px;">
                    <div style="flex:1">
                        <label>From: <span id="start_week_display"
                                style="font-weight:normal; color:#666; font-size:0.85em;"></span></label>
                        <input type="date" name="search_start" id="search_start"
                            value="<?php echo $_GET['search_start'] ?? ''; ?>">
                    </div>
                    <div style="flex:1">
                        <label>To: <span id="end_week_display"
                                style="font-weight:normal; color:#666; font-size:0.85em;"></span></label>
                        <input type="date" name="search_end" id="search_end"
                            value="<?php echo $_GET['search_end'] ?? ''; ?>">
                    </div>
                </div>
                <button type="submit">Check Availability</button>
                <a href="computers.php"><button type="button"
                        style="background-color: #6c757d; margin-top: 5px;">Reset</button></a>
            </form>

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

                document.getElementById('search_start').addEventListener('change', () => updateWeekDisplay('search_start', 'start_week_display'));
                document.getElementById('search_end').addEventListener('change', () => updateWeekDisplay('search_end', 'end_week_display'));

                // Initial run
                updateWeekDisplay('search_start', 'start_week_display');
                updateWeekDisplay('search_end', 'end_week_display');
            </script>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h3>Add New Computer</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <input type="text" name="name" placeholder="Computer Name" required>
                <input type="text" name="processor" placeholder="Processor">
                <input type="text" name="ram" placeholder="RAM">
                <input type="date" name="purchase_date" required>
                <button type="submit">ADD UNIT</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Specs (CPU/RAM)</th>
                    <th>Age</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($computers as $pc): ?>
                    <tr>
                        <td>#<?php echo $pc['id']; ?></td>
                        <td><?php echo htmlspecialchars($pc['name']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($pc['processor']); ?> /
                            <?php echo htmlspecialchars($pc['ram']); ?>
                        </td>
                        <td><?php echo calculate_age($pc['purchase_date']); ?></td>
                        <td><?php echo strtoupper($pc['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>