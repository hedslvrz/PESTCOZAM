<?php
require_once "../../database.php";

$database = new Database();
$conn = $database->getConnection();

try {
    $query = "SELECT id, firstname, lastname, dob, email, mobile_number, role, status, 
                     sss_no, pagibig_no, philhealth_no, deleted_reason
              FROM users 
              WHERE deleted = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Query error.", "error" => $e->getMessage()]);
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archived Employees</title>
    <style>
        .table-container {
        overflow-x: auto;
        margin-bottom: 20px;
        background: white;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, 
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            white-space: nowrap;
        }

        thead th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .action-buttons a {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .action-buttons a:first-child {
            background: #e6f3ff;
            color: #144578;
        }

        .action-buttons a:last-child {
            background: #ffebee;
            color: #dc3545;
        }

        .action-buttons a:first-child:hover {
            background: #144578;
            color: white;
        }

        .action-buttons a:last-child:hover {
            background: #dc3545;
            color: white;
        }
</style>
    
</head>
<body>
    <div class="table-container">
        <h2>Archived Employees</h2>
        <a href="../../HTML%20CODES/dashboard-admin.php" class="home-link">Home</a>
        <table id="employeeTable">
            <thead>
                <tr>
                    <th>Employee No.</th>
                    <th>Name</th>
                    <th>Date of Birth</th>
                    <th>Email</th>
                    <th>Mobile Number</th>
                    <th>SSS No.</th>
                    <th>Pag-ibig No.</th>
                    <th>Phil Health No.</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Reason for Deletion</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($employees)): ?>
                    <?php foreach ($employees as $res): ?>
                        <tr data-role="<?= htmlspecialchars($res['role']) ?>" data-status="<?= htmlspecialchars($res['status']) ?>">
                            <td><?= 'EMP-'.str_pad($res['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($res['firstname'] . ' ' . $res['lastname']) ?></td>
                            <td><?= htmlspecialchars($res['dob'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($res['email'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($res['mobile_number'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($res['sss_no'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($res['pagibig_no'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($res['philhealth_no'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($res['role']) ?></td>
                            <td><?= htmlspecialchars($res['status']) ?></td>
                            <td><?= htmlspecialchars($res['deleted_reason'] ?? 'N/A') ?></td>
                            <td class="action-buttons">
                                <a href="../../employee/functions/restore.php?id=<?= urlencode($res['id']) ?>" 
                                onclick="return confirmRestore(event);">Restore</a>
                                <a href="../../employee/functions/delete_archive.php?id=<?= urlencode($res['id'] ?? '') ?>" 
                                onclick="return confirmDelete(event);">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="12">No archived employees found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
    <script>
        
        function confirmRestore(event) {
            event.preventDefault();
            var link = event.currentTarget.href;
            if (confirm("Are you sure you want to restore this employee?")) {
                window.location.href = link;
            }
        }
        function confirmDelete(event) {
            event.preventDefault();
            var link = event.currentTarget.href;
            if (confirm("Are you sure you want to permanently delete this employee?")) {
                window.location.href = link;
            }
        }
    </script>
    <script src="../../JS CODES/dashboard-admin.js"></script>
    <script src="../../JS CODES/forms-js/employee.js"></script>


</body>
</html>