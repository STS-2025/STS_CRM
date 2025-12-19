<?php
require 'db.php';
if (!isset($_GET['id'])) { header('Location: ../leads.php'); exit; }
$id = (int)$_GET['id'];
$stmt = $mysqli->prepare("DELETE FROM leads WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
header('Location: ../leads.php');
