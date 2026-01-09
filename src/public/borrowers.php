<?php
require_once '../functions.php';

if (isset($_GET['ajax_search'])) {
    $search = $_GET['ajax_search'];
    $results = get_borrowers($search);
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

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
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $conn->prepare("UPDATE borrowers SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}

$borrowers = get_borrowers();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Borrowers - Pandora's Box</title>
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
            <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin:0;">Registered Borrowers</h3>
                <input type="text" id="search_borrower" placeholder="Search..."
                    style="padding: 8px; width: 250px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Site</th>
                        <th>Referrer</th>
                        <th>Action</th>
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
                            <td>
                                <a href="edit_borrower.php?id=<?php echo $b['id']; ?>">
                                    <button
                                        style="background-color: #ffc107; color: black; padding: 5px 10px; border:none; cursor:pointer;">Edit</button>
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet emprunteur ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" style="background-color: #dc3545; color: white; padding: 5px 10px; border:none; cursor:pointer;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('search_borrower');
        const tbody = document.querySelector('tbody');

        function escapeHtml(text) {
            if (text == null) return '';
            return process(text.toString());
        }

        function process(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        searchInput.addEventListener('keyup', (e) => {
            const term = e.target.value;
            fetch('borrowers.php?ajax_search=' + encodeURIComponent(term))
                .then(response => response.json())
                .then(data => {
                    tbody.innerHTML = '';
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No borrowers found</td></tr>';
                        return;
                    }
                    data.forEach(b => {
                        let referrer = b.referrer ? b.referrer : '-';
                        // Handle potential nulls safely
                        let name = b.name || '';
                        let email = b.email || '';
                        let site = b.site || '';

                        let html = `<tr>
                            <td>#${b.id}</td>
                            <td>${escapeHtml(name)}</td>
                            <td>${escapeHtml(email)}</td>
                            <td>${escapeHtml(site)}</td>
                            <td>${escapeHtml(referrer)}</td>
                            <td>
                                <a href="edit_borrower.php?id=${b.id}">
                                    <button style="background-color: #ffc107; color: black; padding: 5px 10px; border:none; cursor:pointer;">Edit</button>
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet emprunteur ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="${b.id}">
                                    <button type="submit" style="background-color: #dc3545; color: white; padding: 5px 10px; border:none; cursor:pointer;">Delete</button>
                                </form>
                            </td>
                        </tr>`;
                        tbody.innerHTML += html;
                    });
                })
                .catch(err => console.error(err));
        });
    </script>
</body>

</html>