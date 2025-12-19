<?php
include 'db.php';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
// leads table-oda connect panni lead_id-aiyum eduthukurom
$sql = "SELECT e.id, e.subject, e.received_at, l.id as lead_id, l.name as sender_name 
        FROM crm_emails e 
        INNER JOIN leads l ON e.sender_email = l.email 
        WHERE e.is_read = 0 ";

if ($role !== 'admin') {
    $sql .= " AND l.owner_id = $user_id ";
}

$sql .= " ORDER BY e.received_at DESC";
        
$result = $conn->query($sql);
$emails = [];
while($row = $result->fetch_assoc()) {
    $row['time_ago'] = date('M d, h:i A', strtotime($row['received_at']));
    $emails[] = $row;
}

echo json_encode([
    'count' => count($emails),
    'emails' => $emails
]);