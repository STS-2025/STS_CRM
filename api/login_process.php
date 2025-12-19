<?php
session_start();
require 'db.php';

date_default_timezone_set('Asia/Kolkata');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

// Get form inputs safely
$email    = trim($_POST['email']);
$password = trim($_POST['password']);
$role     = $_POST['role']; // admin or user

if (empty($email) || empty($password) || empty($role)) {
    echo "<script>alert('All fields are required');window.location='../index.php';</script>";
    exit();
}

// Secure query
$stmt = $conn->prepare("
    SELECT id, name, password, role 
    FROM users 
    WHERE email = ? AND role = ?
");
$stmt->bind_param("ss", $email, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Invalid email or role');window.location='../index.php';</script>";
    exit();
}

$user = $result->fetch_assoc();

// Verify password (hashed)
if ($password !== $user['password']) {
    echo "<script>alert('Invalid password');window.location='../index.php';</script>";
    exit();
}

// ✅ Login success
$_SESSION['user_id']   = $user['id'];
$_SESSION['role']      = $user['role'];
$_SESSION['user_name'] = $user['name'];

// ✅ Role-based redirect
if ($user['role'] === 'admin') {
    header("Location: ../dashboard.php");
} else {
    header("Location: ../user_dash.php");
}
exit();
