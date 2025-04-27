<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit;
}

require_once '../database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$firstname = $_POST['firstname'];
$middlename = $_POST['middlename']; // Added middlename
$lastname = $_POST['lastname'];
$email = $_POST['email'];
$mobile_number = $_POST['mobile_number'];
$dob = $_POST['dob'];

// Check if email already exists for other users
try {
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bindParam(1, $email);
    $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Email already exists - redirect with error
        header("Location: Profile.php?error=email_exists");
        exit;
    }

    // Handle profile picture upload
    $profile_pic_name = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        // Create directory if it doesn't exist
        $upload_dir = "../uploads/profile_pictures/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $profile_pic_name = $user_id . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $profile_pic_name;
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
            header("Location: Profile.php?error=invalid_file_type");
            exit;
        }
        
        // Limit file size to 2MB
        if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
            header("Location: Profile.php?error=file_too_large");
            exit;
        }
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            // File uploaded successfully
            
            // Delete previous profile picture if exists
            $stmt = $db->prepare("SELECT profile_pic FROM users WHERE id = ?");
            $stmt->bindParam(1, $user_id);
            $stmt->execute();
            $old_pic = $stmt->fetchColumn();
            
            if ($old_pic && file_exists($upload_dir . $old_pic)) {
                unlink($upload_dir . $old_pic);
            }
        } else {
            // Failed to upload file
            header("Location: Profile.php?error=upload_failed");
            exit;
        }
    }

    // Build the SQL query based on whether a profile picture was uploaded
    if ($profile_pic_name) {
        $query = "UPDATE users SET 
                  firstname = ?, 
                  middlename = ?,
                  lastname = ?, 
                  email = ?, 
                  mobile_number = ?, 
                  dob = ?,
                  profile_pic = ?
                  WHERE id = ?";
    } else {
        $query = "UPDATE users SET 
                  firstname = ?, 
                  middlename = ?,
                  lastname = ?, 
                  email = ?, 
                  mobile_number = ?, 
                  dob = ?
                  WHERE id = ?";
    }
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    $stmt->bindParam(1, $firstname);
    $stmt->bindParam(2, $middlename);
    $stmt->bindParam(3, $lastname);
    $stmt->bindParam(4, $email);
    $stmt->bindParam(5, $mobile_number);
    $stmt->bindParam(6, $dob);
    
    if ($profile_pic_name) {
        $stmt->bindParam(7, $profile_pic_name);
        $stmt->bindParam(8, $user_id, PDO::PARAM_INT);
    } else {
        $stmt->bindParam(7, $user_id, PDO::PARAM_INT);
    }
    
    if ($stmt->execute()) {
        // Update session variables
        $_SESSION['firstname'] = $firstname;
        $_SESSION['lastname'] = $lastname;
        
        // Update profile pic in session if uploaded
        if ($profile_pic_name) {
            $_SESSION['profile_pic'] = "../uploads/profile_pictures/" . $profile_pic_name;
        }
        
        // Redirect back to profile page with success message
        header("Location: Profile.php?success=profile_updated");
        exit;
    } else {
        // Database error - redirect with error
        header("Location: Profile.php?error=update_failed");
        exit;
    }
} catch(PDOException $e) {
    error_log("Error updating profile: " . $e->getMessage());
    header("Location: Profile.php?error=database_error");
    exit;
}
?>
