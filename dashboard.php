<?php
session_start();
if(!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$patientsData = 'patients.json';
$patients = file_exists($patientsData)?json_decode(file_get_contents($patientsData), true):[];

//Add or Edit Patient Information
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $contact = trim($_POST['contact'] ?? '');

    if ($name && $age > 0 && $diagnosis && $contact) {
        if (isset($_POST['id']) && $_POST['id'] !== '') {
            //Edit Logic
            $id = $_POST['id'];
            foreach ($patients as &$p) {
                if ($p['id'] == $id) {
                    $p['name'] = $name;
                    $p['age'] = $age;
                    $p['diagnosis'] = $diagnosis;
                    $p['contact'] = $contact;
                    break;
                }
            }
        } else {
            //Add Logic
            $newId = uniqid();
            $patients[] = [
                'id' => $newId,
                'name' => $name,
                'age' => $age,
                'diagnosis' => $diagnosis,
                'contact' => $contact
            ];
        }

        file_put_contents($patientsData, json_encode(array_values($patients), JSON_PRETTY_PRINT));
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "All fields are required.";
    }
}

//Editing
$editMode = false;
$editData = [];
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    foreach ($patients as $p) {
        if ($p['id'] == $id) {
            $editData = $p;
            $editMode = true;
            break;
        }
    }
}

//Deleting
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $patients = array_filter($patients, fn($p) => $p['id'] != $id);
    file_put_contents($patientsData, json_encode(array_values($patients), JSON_PRETTY_PRINT));
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="user-row">
            <h1>Welcome, <?php echo $_SESSION["username"]?></h1>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        <h1>Patient Information Dashboard</h1>

        <!--Submit Form-->
        <div class="form-section">
            <h2 style="margin-bottom: 16px;"><?= $editMode ? 'Edit Patient' : 'Add New Patient' ?></h2>
            <?php if (isset($error)): ?>
                <div class="alert error" style="margin-bottom: 16px;"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" style="display: grid; gap: 12px;">
                <input type="hidden" name="id" value="<?= $editMode ? htmlspecialchars($editData['id']) : '' ?>">

            <div class="patient-form-row" 
             style="display: grid; 
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); 
                    gap: 12px; 
                    align-items: end;">
            
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required
                    value="<?= $editMode ? htmlspecialchars($editData['name']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" min="1" max="150" required
                    value="<?= $editMode ? htmlspecialchars($editData['age']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="diagnosis">Diagnosis</label>
                <input type="text" id="diagnosis" name="diagnosis" required
                    value="<?= $editMode ? htmlspecialchars($editData['diagnosis']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="contact">Contact</label>
                <input type="text" id="contact" name="contact" required
                    value="<?= $editMode ? htmlspecialchars($editData['contact']) : '' ?>"
                    placeholder="Phone/Email">
            </div>

            <div style="display: flex; flex-direction: column; justify-content: end; height: 100%;">
                <button type="submit" class="btn-submit" 
                        style="padding: 10px 16px; font-size: 15px; white-space: nowrap;">
                    <?= $editMode ? 'Update' : 'Add Patient' ?>
                </button>
            </div>
        </div>

        <?php if ($editMode): ?>
            <div style="text-align: center; margin-top: 12px;">
                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="link">Cancel Editing</a>
            </div>
        <?php endif; ?>
    </form>
</div>

        <!-- Patients Table-->
        <div>
            <h2>Registered Patients (<?= count($patients) ?>)</h2>
            <?php if (empty($patients)): ?>
                <p style="text-align: center; color: #777; margin-top: 20px;">No patients recorded yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="patients-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Diagnosis</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= (int)$p['age'] ?></td>
                                <td><?= htmlspecialchars($p['diagnosis']) ?></td>
                                <td><?= htmlspecialchars($p['contact']) ?></td>
                                <td class="action-links">
                                    <a href="?edit=<?= $p['id'] ?>" class="edit-link">Edit</a>
                                    <a href="?delete=<?= $p['id'] ?>" class="delete-link" onclick="return confirm('Delete this patient?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>