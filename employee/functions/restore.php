<?php
require_once "../../database.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Invalid ID'); window.history.back();</script>";
    exit();
}

$id = (int) $_GET['id'];

$database = new Database();
$conn = $database->getConnection();

try {
    $query = "UPDATE users SET deleted = 0 WHERE id = :id AND deleted = 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount()) {
        $message = "Employee successfully restored.";
    } else {
        $message = "No archived user found or already active.";
    }
} catch (PDOException $e) {
    $message = "Error restoring employee: " . $e->getMessage();
}
?>

<script>
    alert("<?= addslashes($message) ?>");
    window.location.href = "../../employee/forms/archive.php";
</script>
