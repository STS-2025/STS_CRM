<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0, 'emails' => [], 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// 3. SQL Query construction
$sql = "SELECT e.id, e.subject, e.received_at, l.id as lead_id, l.name as sender_name 
        FROM crm_emails e 
        INNER JOIN leads l ON e.sender_email = l.email 
        WHERE e.is_read = 0 ";

// Role based filter
if ($role !== 'admin') {
    
    $sql .= " AND l.owner_id = " . (int)$user_id;
}

$sql .= " ORDER BY e.received_at DESC";

// 4. Query execute
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
    exit;
}

$emails = [];
while($row = $result->fetch_assoc()) {
    $row['time_ago'] = date('M d, h:i A', strtotime($row['received_at']));
    $emails[] = $row;
}

header('Content-Type: application/json');
echo json_encode([
    'count' => count($emails),
    'emails' => $emails
]);