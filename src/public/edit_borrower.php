<?php
require_once '../functions.php';

$error = null;
$borrower = null;

if (!isset($_GET['id'])) {
    header("Location: borrowers.php");
    exit;
}

$borrower = get_borrower_by_id($_GET['id']);

if (!$borrower) {
    die("Borrower not found.");
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $site = $_POST['site'];
    $referrer = $_POST['referrer'];

    $stmt = $conn->prepare("UPDATE borrowers SET name = ?, email = ?, site = ?, referrer = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $email, $site, $referrer, $id);

    if ($stmt->execute()) {
        header("Location: borrowers.php");
        exit;
    } else {
        $error = "Failed to update borrower.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Borrower - Retro Loan</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/svg+xml" href="logo.svg">
</head>

<body>
    <div class="scanlines"></div>
    <div class="container">
        <?php include "header.php"; ?>

        <?php if ($error): ?>
            <div class="card alert" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Edit Borrower #<?php echo $borrower['id']; ?></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $borrower['id']; ?>">

                <div style="margin-bottom: 10px;">
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($borrower['name']); ?>" required>
                </div>

                <div style="margin-bottom: 10px;">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($borrower['email']); ?>">
                </div>

                <div style="margin-bottom: 10px;">
                    <label>Site</label>
                    <input type="text" name="site" value="<?php echo htmlspecialchars($borrower['site']); ?>">
                </div>

                <div style="margin-bottom: 10px;">
                    <label>Referrer</label>
                    <input type="text" name="referrer" value="<?php echo htmlspecialchars($borrower['referrer']); ?>">
                </div>

                <div style="margin-top: 15px;">
                    <button type="submit">Update Borrower</button>
                    <a href="borrowers.php"><button type="button"
                            style="background-color: #6c757d; margin-left: 10px;">Cancel</button></a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>