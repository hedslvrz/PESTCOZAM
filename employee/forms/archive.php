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

// Count archived employees
$totalArchived = count($employees);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Employees - PESTCOZAM</title>
    <link rel="stylesheet" href="../../CSS CODES/archive.css">
    <!-- Add Font Awesome for icons if used in your dashboard -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Add SweetAlert2 library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="page-header">
        <h1><i class="fas fa-archive"></i> PESTCOZAM Employee Archive</h1>
    </div>
    
    <div class="breadcrumb">
        <a href="../../HTML%20CODES/dashboard-admin.php"><i class="fas fa-home"></i> Dashboard</a> &gt; Archived Employees
    </div>
    
    <div class="container">
        <!-- Dashboard-style stats summary -->
        <div class="dashboard-stat">
            <h3>Archive Summary</h3>
            <p><strong><?= $totalArchived ?></strong> archived employee(s) found</p>
        </div>
        
        <div class="table-container">
            <h2><i class="fas fa-user-slash"></i> Archived Employees</h2>
            <a href="../../HTML%20CODES/dashboard-admin.php" class="home-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            
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
                                    onclick="return confirmRestore(event);"><i class="fas fa-undo-alt"></i> Restore</a>
                                    <a href="../../employee/functions/delete_archive.php?id=<?= urlencode($res['id'] ?? '') ?>" 
                                    onclick="return confirmDelete(event);"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="empty-state">
                                <i class="fas fa-info-circle"></i> No archived employees found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function confirmRestore(event) {
            event.preventDefault();
            var link = event.currentTarget.href;
            
            Swal.fire({
                title: 'Restore Employee',
                text: 'Are you sure you want to restore this employee?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1565c0',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, restore'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = link;
                }
            });
            return false;
        }
        
        function confirmDelete(event) {
            event.preventDefault();
            var link = event.currentTarget.href;
            
            Swal.fire({
                title: 'Delete Permanently',
                text: 'Are you sure you want to permanently delete this employee?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = link;
                }
            });
            return false;
        }
    </script>
    <script src="../../JS CODES/dashboard-admin.js"></script>
    <script src="../../JS CODES/forms-js/employee.js"></script>
</body>
</html>