<?php
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $site = $_POST['site'];
        $referrer = $_POST['referrer'];

        $stmt = $conn->prepare("INSERT INTO borrowers (name, email, site, referrer) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $site, $referrer);
        $stmt->execute();
    }
}

$borrowers = get_borrowers();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Borrowers - Loan Manager</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/svg+xml" href="logo.svg">
</head>

<body>
    <div class="container">
        <?php include "header.php"; ?>

        <div class="card">
            <h3>Add New Borrower</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="name" placeholder="Full Name" required style="flex: 2;">
                    <input type="email" name="email" placeholder="Email Address" style="flex: 2;">
                </div>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="site" placeholder="Site" style="flex: 1;">
                    <input type="text" name="referrer" placeholder="Referrer / Manager" style="flex: 1;">
                </div>
                <button type="submit" style="margin-top: 10px;">Add Borrower</button>
            </form>
        </div>

        <div class="card">
            <h3>Registered Borrowers</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Site</th>
                        <th>Referrer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($borrowers as $b): ?>
                        <tr>
                            <td>#<?php echo $b['id']; ?></td>
                            <td><?php echo htmlspecialchars($b['name']); ?></td>
                            <td><?php echo htmlspecialchars($b['email']); ?></td>
                            <td><?php echo htmlspecialchars($b['site']); ?></td>
                            <td><?php echo htmlspecialchars($b['referrer'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>