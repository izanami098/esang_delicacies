<?php
$conn = new mysqli("localhost", "root", "", "esangdel_esang_db");

// Accept / Reject
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $requestId = $_POST['requestId'];

    $stmt = $conn->prepare("UPDATE INVOICE_MODIFICATION_REQUEST SET status = ?, adminResponseDate = NOW() WHERE requestId = ?");
    $stmt->bind_param("si", $action, $requestId);
    $stmt->execute();
    $stmt->close();

    $popupMessage = "Request #$requestId has been $action.";
}

// Get requests
$result = $conn->query("SELECT r.requestId, r.orderId, c.name AS cashierName, r.status, r.requestDate 
                        FROM INVOICE_MODIFICATION_REQUEST r
                        JOIN CASHIER c ON r.cashierId = c.empId
                        ORDER BY r.requestDate DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">

    <h2>Admin Dashboard - Invoice Modification Requests</h2>

    <table class="table table-bordered table-striped mt-4">
        <thead class="table-dark">
            <tr>
                <th>Request ID</th>
                <th>Order ID</th>
                <th>Cashier</th>
                <th>Status</th>
                <th>Request Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['requestId']; ?></td>
                <td><?php echo $row['orderId']; ?></td>
                <td><?php echo $row['cashierName']; ?></td>
                <td><?php echo ucfirst($row['status']); ?></td>
                <td><?php echo $row['requestDate']; ?></td>
                <td>
                    <?php if ($row['status'] == 'pending') { ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="requestId" value="<?php echo $row['requestId']; ?>">
                            <button type="submit" name="action" value="accepted" class="btn btn-success btn-sm">Accept</button>
                            <button type="submit" name="action" value="rejected" class="btn btn-danger btn-sm">Reject</button>
                        </form>
                    <?php } else { ?>
                        <span class="badge bg-secondary">Finalized</span>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <!-- Popup Modal -->
    <div class="modal fade" id="adminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Action Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php echo $popupMessage ?? "No recent action."; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($popupMessage)) { ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            let modal = new bootstrap.Modal(document.getElementById('adminModal'));
            modal.show();
        });
    </script>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>