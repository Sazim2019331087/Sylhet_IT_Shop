<?php
// 1. Start the session to log the user in
session_start();

// 2. Require the hardcoded admin and supplier details
require "admin_details.php";

// 3. Get the email and password the user *typed* in the form
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 4. Securely check the credentials on the server
// It compares the user's input ($email) with the server's variables ($admin_email)
if ($email === $admin_email && $password === $admin_password) {
    // --- LOGIN SUCCESS: ADMIN ---
    $_SESSION['role'] = 'admin';
    $_SESSION['email'] = $admin_email;
    $_SESSION['name'] = $admin_name;
    
    // Redirect to the admin dashboard
    header("Location: admin.php");
    exit;
    
} elseif ($email === $supplier_email && $password === $supplier_password) {
    // --- LOGIN SUCCESS: SUPPLIER ---
    $_SESSION['role'] = 'supplier';
    $_SESSION['email'] = $supplier_email;
    $_SESSION['name'] = "Supplier"; // Or $supplier_name if you add it to admin_details.php
    
    // Redirect to the supplier dashboard
    header("Location: supplier.php");
    exit;
    
} else {
    // --- LOGIN FAILED ---
    // Redirect back to the login page with an error flag
    header("Location: operational_login.php?error=1");
    exit;
}
?>
