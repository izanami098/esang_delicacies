<?php
require_once __DIR__ . '/../_bootstrap.php';
json_headers_local();

$db = db();

$totalMenus = 0; $totalCustomers = 0; $totalSales = 0.0;

$q1 = $db->query('SELECT COUNT(*) FROM PRODUCT');
if ($q1) { [$totalMenus] = $q1->fetch_row(); }
$q2 = $db->query('SELECT COUNT(*) FROM CUSTOMER');
if ($q2) { [$totalCustomers] = $q2->fetch_row(); }
$q3 = $db->query('SELECT COALESCE(SUM(amount),0) FROM PAYMENTS');
if ($q3) { [$totalSales] = $q3->fetch_row(); }

// Order counts by status
$statuses = ['Processed','Completed','Pending'];
$orderCounts = [];
foreach ($statuses as $s) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM ORDERS WHERE orderStatus = ?');
    $stmt->bind_param('s', $s);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $orderCounts[$s] = (int)$cnt;
    $stmt->close();
}

echo json_encode([
    'ok' => true,
    'data' => [
        'totalMenus' => (int)$totalMenus,
        'totalCustomers' => (int)$totalCustomers,
        'totalSales' => (float)$totalSales,
        'orders' => $orderCounts,
    ],
]);
?>


