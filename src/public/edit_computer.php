<?php
require_once '../functions.php';

$error = null;
$computer = null;

if (!isset($_GET['id'])) {
    header("Location: computers.php");
    exit;
}

$computer = get_computer_by_id($_GET['id']);

if (!$computer) {
    die("Computer not found.");
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $proc = $_POST['processor'];
    $ram = $_POST['ram'];
    $date = $_POST['purchase_date'];

    // Status handling could be added here if needed, but for now we stick to basic details
    // If status needs to be updated (e.g. out of repair), it might be better handled via specific actions, 
    // but the user asked to modify computers, so editing fields is primary.

    $stmt = $conn->prepare("UPDATE computers SET name = ?, processor = ?, ram = ?, purchase_date = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $proc, $ram, $date, $id);

    if ($stmt->execute()) {
        header("Location: computers.php");
        exit;
    } else {
        $error = "Failed to update computer.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Computer - Retro Loan</title>
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
            <h3>Edit Computer #<?php echo $computer['id']; ?></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $computer['id']; ?>">

                <div style="margin-bottom: 10px;">
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($computer['name']); ?>" required>
                </div>

                <div style="margin-bottom: 10px;">
                    <label>Processor</label>
                    <input type="text" name="processor" value="<?php echo htmlspecialchars($computer['processor']); ?>">
                </div>

                <div style="margin-bottom: 10px;">
                    <label>RAM</label>
                    <input type="text" name="ram" value="<?php echo htmlspecialchars($computer['ram']); ?>">
                </div>

                <div style="margin-bottom: 10px;">
                    <label>Purchase Date</label>
                    <input type="date" name="purchase_date" value="<?php echo $computer['purchase_date']; ?>" required>
                </div>

                <div style="margin-top: 15px;">
                    <button type="submit">Update Computer</button>
                    <a href="computers.php"><button type="button"
                            style="background-color: #6c757d; margin-left: 10px;">Cancel</button></a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>